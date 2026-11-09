<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\BeneficiaryImportService;
use App\Services\CertificateService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CertificateGradeCorrectionTest extends TestCase
{
    use RefreshDatabase;

    public static function nonAdministrativeRoles(): array
    {
        return ['jefatura' => [Role::JEFE_CARRERA], 'secretaría' => [Role::SECRETARIA], 'operación' => [Role::OPERATOR]];
    }

    #[DataProvider('nonAdministrativeRoles')]
    public function test_only_superadministrator_can_revoke_from_http(string $roleName): void
    {
        $case = $this->scenario($roleName);
        $certificate = $this->issue($case);
        $this->actingAs($case['user'])->post(route('certificates.revoke', $certificate), ['revocation_reason' => 'Corrección de nota solicitada'])->assertForbidden();
        $this->assertFalse($certificate->fresh()->isRevoked());
    }

    public function test_revocation_service_also_rejects_a_nonadministrator(): void
    {
        $case = $this->scenario();
        $certificate = $this->issue($case);
        $this->expectException(AuthorizationException::class);
        app(CertificateService::class)->revokeCertificate($certificate, $case['user'], 'Corrección solicitada');
    }

    public static function academicRoles(): array
    {
        return [...self::nonAdministrativeRoles(), 'superadministración' => [Role::ADMIN]];
    }

    #[DataProvider('academicRoles')]
    public function test_grade_is_locked_for_every_role_until_revocation(string $roleName): void
    {
        $case = $this->scenario($roleName);
        $this->issue($case);
        $this->actingAs($case['user'])->patch(route('activities.participants.update', [$case['activity'], $case['student']]), ['score' => 99])->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $case['student']->id, 'score' => 85]);
    }

    public static function correctionSources(): array
    {
        return ['formulario' => ['form'], 'cargar planilla' => ['enroll'], 'confirmar planilla' => ['confirm'], 'vista previa anterior a la anulación' => ['stale']];
    }

    #[DataProvider('correctionSources')]
    public function test_admin_revocation_allows_correction_and_new_issuance_without_changing_history(string $source): void
    {
        $case = $this->scenario();
        $certificate = $this->issue($case);
        $snapshot = $certificate->snapshot_data;
        $this->revoke($case, $certificate);

        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $case['student']->id, 'status' => 'inscrito']);
        $this->actingAs($case['user'])->get(route('activities.show', $case['activity']))->assertOk()->assertSee('PENDIENTE DE EMISIÓN')->assertSee('Editar Calificación / Rol');
        $this->actingAs($case['user']);

        if ($source === 'form') {
            $this->patch(route('activities.participants.update', [$case['activity'], $case['student']]), ['score' => 90, 'score_label' => 'Nota corregida'])->assertRedirect()->assertSessionHas('success');
        } elseif ($source === 'enroll') {
            $this->post(route('activities.roster.enroll', $case['activity']), ['file' => UploadedFile::fake()->createWithContent('correccion.csv', "CI,NOMBRES,APELLIDOS,CARRERA,NOTA\n910001,Ana,Prueba,QUI,90")])->assertRedirect()->assertSessionHas('success');
        } else {
            $this->confirm($case, 90, null, $source === 'stale' ? 'already_certified' : 'existing_student')->assertOk()->assertJsonPath('result.issued_count', 1);
        }

        if (! in_array($source, ['confirm', 'stale'], true)) {
            $this->post(route('activities.participants.issue-certificates', $case['activity']), ['certificate_type_id' => $case['type']->id, 'issue_date' => '2026-11-06'])->assertRedirect()->assertSessionHas('success');
        }

        $this->assertDatabaseCount('certificates', 2);
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $case['student']->id, 'score' => 90, 'status' => 'certificado']);
        $newCertificate = Certificate::latest('id')->firstOrFail();
        $this->assertSame(90.0, $newCertificate->score);
        $this->assertNotSame($certificate->certificate_code, $newCertificate->certificate_code);
        $this->assertSame($snapshot, $certificate->fresh()->snapshot_data);
        $this->assertSame(85.0, $certificate->fresh()->score);
        $this->assertTrue($certificate->fresh()->isRevoked());
        $this->assertSame($case['admin']->id, $certificate->fresh()->revoked_by_user_id);
        $this->get(route('verification.show', $certificate->verification_token))->assertOk()->assertSee('REVOCADO');
    }

    public function test_importing_another_certificate_type_cannot_replace_a_live_grade(): void
    {
        $case = $this->scenario();
        $this->issue($case);
        $otherType = $this->anotherType();
        $this->actingAs($case['user']);
        $this->confirm($case, 99, $otherType)->assertOk()->assertJsonPath('result.issued_count', 1);
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $case['student']->id, 'score' => 85]);
        $this->assertSame(85.0, Certificate::latest('id')->firstOrFail()->score);
    }

    public function test_preview_preserves_the_locked_grade_for_another_certificate_type(): void
    {
        $case = $this->scenario();
        $this->issue($case);
        $otherType = $this->anotherType();
        $preview = app(BeneficiaryImportService::class)->parseAndPreviewForActivity(
            UploadedFile::fake()->createWithContent('otra-planilla.csv', "CI,NOMBRES,APELLIDOS,CARRERA,NOTA\n910001,Ana,Prueba,QUI,99"),
            $case['activity'], $otherType->id
        );
        $this->assertSame(85.0, $preview['rows'][0]['data']['score']);
        $this->assertSame('Nota original', $preview['rows'][0]['data']['score_label']);
    }

    public function test_duplicate_rows_cannot_replace_a_failing_grade_with_a_passing_grade(): void
    {
        $case = $this->scenario();
        $row = ['status' => 'failing_student', 'data' => ['document_number' => '910001', 'first_name' => 'Ana', 'last_name' => 'Prueba', 'score' => 60]];
        $duplicate = ['status' => 'already_certified', 'data' => [...$row['data'], 'score' => 99]];
        $this->actingAs($case['user'])->postJson(route('activities.roster.confirm', $case['activity']), [
            'issue_date' => '2026-11-06', 'rows' => [$row, $duplicate],
        ])->assertOk()->assertJsonPath('result.issued_count', 0)->assertJsonPath('result.duplicate_count', 1);
        $this->assertDatabaseCount('certificates', 0);
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $case['student']->id, 'score' => 60]);
    }

    public function test_grade_stays_locked_until_all_certificates_for_the_participant_are_revoked(): void
    {
        $case = $this->scenario();
        $first = $this->issue($case);
        $second = $this->issue($case, $this->anotherType());
        $this->revoke($case, $first);
        $this->actingAs($case['user'])->patch(route('activities.participants.update', [$case['activity'], $case['student']]), ['score' => 90])->assertSessionHas('error');
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $case['student']->id, 'score' => 85]);
        $this->revoke($case, $second);
        $this->actingAs($case['user'])->patch(route('activities.participants.update', [$case['activity'], $case['student']]), ['score' => 90])->assertSessionHas('success');
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $case['student']->id, 'score' => 90]);
    }

    public function test_corrected_failing_grade_remains_on_roster_without_a_new_certificate(): void
    {
        $case = $this->scenario();
        $this->revoke($case, $this->issue($case));
        $this->actingAs($case['user']);
        $this->confirm($case, 60)->assertOk()->assertJsonPath('result.issued_count', 0)->assertJsonPath('result.failing_count', 1);
        $this->assertDatabaseCount('certificates', 1);
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $case['student']->id, 'score' => 60, 'status' => 'reprobado']);
    }

    public function test_revocation_preserves_manual_payment_and_pending_still_prevents_reissuance(): void
    {
        $case = $this->scenario(Role::SECRETARIA, true);
        $this->revoke($case, $this->issue($case));
        $this->actingAs($case['user'])->patch(route('activities.participants.update', [$case['activity'], $case['student']]), ['score' => 90])->assertSessionHas('success');
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $case['student']->id, 'payment_confirmed' => true]);
        $this->patch(route('activities.participants.payment', [$case['activity'], $case['student']]), ['payment_confirmed' => false])->assertSessionHas('success');
        $this->confirm($case, 90)->assertOk()->assertJsonPath('result.issued_count', 0)->assertJsonPath('result.payment_pending_count', 1);
        $this->patch(route('activities.participants.payment', [$case['activity'], $case['student']]), ['payment_confirmed' => true])->assertSessionHas('success');
        $this->confirm($case, 90)->assertOk()->assertJsonPath('result.issued_count', 1);
    }

    public function test_organizer_and_invited_careers_participate_while_others_stay_observed_and_can_be_removed(): void
    {
        $case = $this->scenario();
        $guestUnit = OrganizationalUnit::create(['name' => 'Mecánica Industrial', 'code' => 'MEC', 'type' => 'carrera']);
        $outsideUnit = OrganizationalUnit::create(['name' => 'Topografía', 'code' => 'TOP', 'type' => 'carrera']);
        $case['activity']->update(['target_audience_mode' => Activity::AUDIENCE_SPECIFIC_UNITS]);
        $case['activity']->allowedUnits()->sync([$guestUnit->id]);
        $guest = Beneficiary::create(['document_type' => 'CI', 'document_number' => '910002', 'first_name' => 'Luis', 'last_name' => 'Invitado', 'primary_organizational_unit_id' => $guestUnit->id]);
        $outside = Beneficiary::create(['document_type' => 'CI', 'document_number' => '910003', 'first_name' => 'Elena', 'last_name' => 'Observada', 'primary_organizational_unit_id' => $outsideUnit->id]);
        $rows = [];
        foreach ([$case['student'], $guest, $outside] as $student) {
            $rows[] = ['status' => 'existing_student', 'data' => ['document_number' => $student->document_number, 'first_name' => $student->first_name, 'last_name' => $student->last_name, 'score' => 90]];
        }
        $this->actingAs($case['user'])->postJson(route('activities.roster.confirm', $case['activity']), ['issue_date' => '2026-11-06', 'rows' => $rows])->assertOk()->assertJsonPath('result.issued_count', 2)->assertJsonPath('result.ineligible_count', 1);
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $outside->id, 'status' => 'observado', 'career_eligible' => false]);
        $this->assertDatabaseMissing('certificates', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $outside->id]);
        $this->delete(route('activities.participants.remove', [$case['activity'], $outside]))->assertSessionHas('success');
        $this->assertDatabaseMissing('activity_participants', ['activity_id' => $case['activity']->id, 'beneficiary_id' => $outside->id]);
        $this->assertDatabaseHas('beneficiaries', ['id' => $outside->id]);
    }

    /** @return array{admin: User, user: User, unit: OrganizationalUnit, type: CertificateType, template: CertificateTemplate, activity: Activity, student: Beneficiary} */
    private function scenario(string $roleName = Role::SECRETARIA, bool $paid = false): array
    {
        $this->travelTo(now()->setDate(2026, 11, 6)->startOfDay());
        $adminRole = Role::create(['name' => Role::ADMIN, 'display_name' => 'Superadministrador']);
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $roleName]);
        $unit = OrganizationalUnit::create(['name' => 'Química Industrial', 'code' => 'QUI', 'type' => 'carrera']);
        $admin = User::factory()->create(['role_id' => $adminRole->id]);
        $user = User::factory()->create(['role_id' => $role->id, 'organizational_unit_id' => $unit->id]);
        $type = CertificateType::create(['name' => 'Aprobación de prueba', 'code' => 'APROB', 'has_score' => true, 'validity_mode' => 'permanent', 'is_active' => true]);
        $template = CertificateTemplate::create(['name' => 'Base de prueba', 'slug' => 'base', 'category' => 'institucional', 'orientation' => 'landscape', 'current_version' => 1, 'is_active' => true, 'is_default' => true, 'configuration' => ['body_text' => '{{beneficiario.nombre}}']]);
        $activity = Activity::create(['name' => 'Curso de prueba', 'activity_type' => 'Curso', 'start_date' => '2026-11-01', 'end_date' => '2026-11-05', 'organizational_unit_id' => $unit->id, 'certificate_type_id' => $type->id, 'min_passing_score' => 70, 'target_audience_mode' => Activity::AUDIENCE_OWN_UNIT, 'status' => 'activa', 'requires_payment' => $paid, 'cost_amount' => $paid ? 50 : 0]);
        $student = Beneficiary::create(['document_type' => 'CI', 'document_number' => '910001', 'first_name' => 'Ana', 'last_name' => 'Prueba', 'primary_organizational_unit_id' => $unit->id]);
        $activity->participants()->attach($student, ['score' => 85, 'score_label' => 'Nota original', 'status' => 'inscrito', 'career_eligible' => true, 'payment_confirmed' => $paid]);

        return compact('admin', 'user', 'unit', 'type', 'template', 'activity', 'student');
    }

    private function issue(array $case, ?CertificateType $type = null): Certificate
    {
        $this->actingAs($case['user'])->post(route('activities.participants.issue-certificates', $case['activity']), [
            'certificate_type_id' => ($type ?? $case['type'])->id,
            'certificate_template_id' => $case['template']->id,
            'issue_date' => '2026-11-06',
        ])->assertRedirect()->assertSessionHas('success');

        return Certificate::latest('id')->firstOrFail();
    }

    private function revoke(array $case, Certificate $certificate): void
    {
        $this->actingAs($case['admin'])->post(route('certificates.revoke', $certificate), ['revocation_reason' => 'Anulación autorizada para corregir la nota'])->assertRedirect()->assertSessionHas('warning');
    }

    private function anotherType(): CertificateType
    {
        return CertificateType::create(['name' => 'Calificación complementaria', 'code' => 'COMPL', 'has_score' => true, 'validity_mode' => 'permanent', 'is_active' => true]);
    }

    private function confirm(array $case, float $score, ?CertificateType $type = null, string $status = 'existing_student'): TestResponse
    {
        return $this->postJson(route('activities.roster.confirm', $case['activity']), ['certificate_type_id' => ($type ?? $case['type'])->id, 'issue_date' => '2026-11-06', 'rows' => [['status' => $status, 'data' => ['document_number' => $case['student']->document_number, 'first_name' => 'Ana', 'last_name' => 'Prueba', 'institution_or_department' => 'QUI', 'score' => $score]]]]);
    }
}
