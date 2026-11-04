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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AcademicRecordProtectionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Activity $activity;

    private Beneficiary $student;

    private OrganizationalUnit $ownUnit;

    private OrganizationalUnit $otherUnit;

    private CertificateType $type;

    private CertificateTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['name' => Role::JEFE_CARRERA, 'display_name' => 'Jefe']);
        $this->ownUnit = OrganizationalUnit::create(['name' => 'Carrera de Química Industrial', 'code' => 'QUI', 'type' => 'carrera', 'is_active' => true]);
        $this->otherUnit = OrganizationalUnit::create(['name' => 'Carrera de Mecánica Industrial', 'code' => 'MEC', 'type' => 'carrera', 'is_active' => true]);
        $this->user = User::factory()->create(['role_id' => $role->id, 'organizational_unit_id' => $this->ownUnit->id, 'is_active' => true]);
        $this->type = CertificateType::create(['name' => 'Participación', 'code' => 'PART', 'slug' => 'participacion', 'has_score' => false, 'validity_mode' => 'permanent', 'is_active' => true]);
        $this->template = CertificateTemplate::create(['name' => 'Base', 'slug' => 'base', 'category' => 'institucional', 'orientation' => 'landscape', 'current_version' => 1, 'is_active' => true, 'is_default' => true, 'configuration' => ['body_text' => '{{actividad.resolucion}} {{acreditacion.resolucion}}']]);
        $this->activity = Activity::create(['name' => 'Evento Química', 'activity_type' => 'Taller', 'start_date' => '2026-11-01', 'end_date' => '2026-11-01', 'organizational_unit_id' => $this->ownUnit->id, 'target_audience_mode' => Activity::AUDIENCE_OWN_UNIT, 'certificate_type_id' => $this->type->id, 'status' => 'activa']);
        $this->student = Beneficiary::create(['first_name' => 'Ana', 'last_name' => 'Prueba', 'document_type' => 'CI', 'document_number' => '910001', 'primary_organizational_unit_id' => $this->ownUnit->id]);
        $this->activity->participants()->attach($this->student, ['score' => 85, 'score_label' => 'Distinción', 'status' => 'inscrito', 'career_eligible' => true]);
    }

    public static function foreignActions(): array
    {
        return [
            'nota jefe' => ['PATCH', 'activities.participants.update', Role::JEFE_CARRERA],
            'nota secretaria' => ['PATCH', 'activities.participants.update', Role::SECRETARIA],
            'quitar participante' => ['DELETE', 'activities.participants.remove', Role::JEFE_CARRERA],
            'agregar participante' => ['POST', 'activities.participants.add-manual', Role::JEFE_CARRERA],
            'cargar Excel' => ['POST', 'activities.roster.enroll', Role::JEFE_CARRERA],
            'previsualizar nómina' => ['POST', 'activities.roster.preview', Role::JEFE_CARRERA],
            'confirmar nómina' => ['POST', 'activities.roster.confirm', Role::JEFE_CARRERA],
            'emitir nómina' => ['POST', 'activities.participants.issue-certificates', Role::SECRETARIA],
            'emitir selección' => ['POST', 'activities.batch-issue', Role::JEFE_CARRERA],
            'cambiar configuración' => ['POST', 'activities.certificate-config', Role::JEFE_CARRERA],
        ];
    }

    #[DataProvider('foreignActions')]
    public function test_rejects_foreign_activity_actions_without_changes(string $method, string $route, string $roleName): void
    {
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $roleName]);
        $this->user->update(['role_id' => $role->id, 'organizational_unit_id' => $this->otherUnit->id]);
        $parameters = str_contains($route, 'participants.update') || str_contains($route, 'participants.remove')
            ? [$this->activity, $this->student] : [$this->activity];

        $this->actingAs($this->user)->call($method, route($route, $parameters), ['score' => 99, 'beneficiary_id' => $this->student->id])->assertForbidden();

        $this->assertDatabaseHas('activity_participants', ['activity_id' => $this->activity->id, 'beneficiary_id' => $this->student->id, 'score' => 85]);
        $this->assertDatabaseCount('certificates', 0);
        $this->assertDatabaseCount('activity_certificate_configs', 0);
    }

    public function test_foreign_beneficiary_cannot_be_edited_or_deleted(): void
    {
        $this->user->update(['organizational_unit_id' => $this->otherUnit->id]);
        $this->actingAs($this->user)->put(route('beneficiaries.update', $this->student), ['first_name' => 'Otro'])->assertForbidden();
        $this->actingAs($this->user)->delete(route('beneficiaries.destroy', $this->student))->assertForbidden();
        $this->assertDatabaseHas('beneficiaries', ['id' => $this->student->id, 'first_name' => 'Ana']);
    }

    public function test_individual_issuance_and_draft_require_own_activity(): void
    {
        $this->user->update(['organizational_unit_id' => $this->otherUnit->id]);
        foreach (['certificates.store', 'certificates.preview-draft'] as $route) {
            $this->actingAs($this->user)->post(route($route), $this->issuanceData())->assertForbidden();
        }
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_activity_input_cannot_bypass_beneficiary_ownership(): void
    {
        $this->student->update(['primary_organizational_unit_id' => $this->otherUnit->id]);
        $this->actingAs($this->user)->put(route('beneficiaries.update', $this->student), [
            'activity_id' => $this->activity->id,
            'document_type' => 'CI',
            'document_number' => $this->student->document_number,
            'first_name' => 'Modificado',
            'last_name' => 'Prueba',
        ])->assertForbidden();
        $this->assertDatabaseHas('beneficiaries', ['id' => $this->student->id, 'first_name' => 'Ana']);
    }

    public function test_own_note_can_change_before_issuance(): void
    {
        $this->actingAs($this->user)->patch(route('activities.participants.update', [$this->activity, $this->student]), ['score' => 90])->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $this->activity->id, 'beneficiary_id' => $this->student->id, 'score' => 90]);
    }

    public function test_issued_note_cannot_change_from_form_or_reimport(): void
    {
        $certificate = $this->issue();
        $snapshot = $certificate->snapshot_data;
        $this->actingAs($this->user)->patch(route('activities.participants.update', [$this->activity, $this->student]), ['score' => 99, 'score_label' => 'Otra'])->assertRedirect()->assertSessionHas('error');

        $csv = "CI,NOMBRES,APELLIDOS,CARRERA,NOTA\n910001,Ana,Prueba,QUI,99";
        $this->actingAs($this->user)->post(route('activities.roster.enroll', $this->activity), ['file' => UploadedFile::fake()->createWithContent('nomina.csv', $csv)])->assertRedirect()->assertSessionHas('success');

        $this->assertDatabaseHas('activity_participants', ['activity_id' => $this->activity->id, 'beneficiary_id' => $this->student->id, 'score' => 85, 'score_label' => 'Distinción']);
        $this->assertSame($snapshot, $certificate->fresh()->snapshot_data);
    }

    public function test_other_career_can_download_issued_pdf(): void
    {
        $certificate = $this->issue();
        $this->user->update(['organizational_unit_id' => $this->otherUnit->id]);

        $response = $this->actingAs($this->user)->get(route('certificates.download', $certificate))->assertOk();
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public static function resolutions(): array
    {
        return ['con resolución' => ['RF-567/2026', 'RF-567/2026 Acreditado bajo RF-567/2026'], 'sin resolución' => [null, ' ']];
    }

    #[DataProvider('resolutions')]
    public function test_snapshot_uses_optional_real_resolution_and_stays_frozen(?string $resolution, string $expected): void
    {
        $this->activity->update(['resolution_number' => $resolution]);
        $certificate = $this->issue();
        $this->assertSame($expected, $certificate->snapshot_data['resolved_body_text']);
        $this->activity->update(['resolution_number' => 'RF-CAMBIADA']);
        $this->assertSame($expected, $certificate->fresh()->snapshot_data['resolved_body_text']);
    }

    public function test_unknown_career_is_not_assigned_to_importer_or_certified(): void
    {
        $rows = [['status' => 'new_student', 'data' => ['document_number' => '910009', 'first_name' => 'Sin', 'last_name' => 'Carrera', 'institution_or_department' => 'Desconocida', 'score' => 90]]];
        $service = app(BeneficiaryImportService::class);
        $service->enrollParticipantsFromRows($rows, $this->activity, $this->user);
        $student = Beneficiary::where('document_number', '910009')->firstOrFail();
        $this->assertNull($student->primary_organizational_unit_id);
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'career_eligible' => false, 'status' => 'observado']);
        $result = $service->issueCertificatesToActivityParticipants($this->activity, $this->type->id, $this->template->id, '2026-11-02', $this->user, app(CertificateService::class), [$student->id]);
        $this->assertSame(0, $result['issued_count']);
        $this->assertSame(1, $result['ineligible_count']);
    }

    public function test_selected_careers_allow_foreign_participants_but_not_unlisted_careers(): void
    {
        $thirdUnit = OrganizationalUnit::create(['name' => 'Topografía', 'code' => 'TOP', 'type' => 'carrera']);
        $this->activity->update(['target_audience_mode' => Activity::AUDIENCE_SPECIFIC_UNITS]);
        $this->activity->allowedUnits()->sync([$this->ownUnit->id, $this->otherUnit->id]);
        $this->student->update(['primary_organizational_unit_id' => $this->otherUnit->id]);
        $this->actingAs($this->user)->post(route('certificates.store'), $this->issuanceData())->assertRedirect()->assertSessionHas('success');
        $this->assertDatabaseCount('certificates', 1);
        $this->assertFalse($this->activity->isCareerAllowed($thirdUnit->id));
    }

    public function test_directors_and_secretaries_require_unit_assignment(): void
    {
        $adminRole = Role::create(['name' => Role::ADMIN, 'display_name' => 'Administrador']);
        $this->user->update(['role_id' => $adminRole->id]);
        $jefeRole = Role::where('name', Role::JEFE_CARRERA)->firstOrFail();
        $this->actingAs($this->user)->post(route('users.store'), ['name' => 'Sin unidad', 'email' => 'sinunidad@example.test', 'role_id' => $jefeRole->id, 'password' => 'TestSeguro123', 'password_confirmation' => 'TestSeguro123'])->assertSessionHasErrors('organizational_unit_id');
        $this->assertDatabaseMissing('users', ['email' => 'sinunidad@example.test']);
    }

    public function test_legacy_resolution_remains_available(): void
    {
        $this->activity->certificateConfig()->create(['custom_fields_schema' => ['resolucion' => 'RF-LEGADA']]);
        $certificate = $this->issue();
        $this->assertSame('RF-LEGADA Acreditado bajo RF-LEGADA', $certificate->snapshot_data['resolved_body_text']);
    }

    public function test_resolution_can_be_cleared_without_restoring_legacy_text(): void
    {
        $this->activity->update(['resolution_number' => 'RF-ANTERIOR']);
        $this->activity->certificateConfig()->create(['custom_fields_schema' => ['resolucion' => 'RF-ANTERIOR']]);
        $this->actingAs($this->user)->post(route('activities.certificate-config', $this->activity), ['resolution_number' => null])->assertSessionHas('success');
        $this->activity->refresh();
        $this->assertNull($this->activity->resolution_number);
        $this->assertSame(' ', $this->issue()->snapshot_data['resolved_body_text']);
    }

    public function test_bulk_confirmation_keeps_observed_students_without_emitting(): void
    {
        $rows = [['status' => 'ineligible_career', 'data' => ['document_number' => '910009', 'first_name' => 'Sin', 'last_name' => 'Carrera', 'institution_or_department' => 'Desconocida']]];
        $result = app(BeneficiaryImportService::class)->executeActivityBulkIssuance($rows, $this->activity, $this->type->id, $this->template->id, '2026-11-02', $this->user, app(CertificateService::class));
        $this->assertSame(0, $result['issued_count']);
        $student = Beneficiary::where('document_number', '910009')->firstOrFail();
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'status' => 'observado', 'career_eligible' => false]);
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_account_without_unit_cannot_manage_events(): void
    {
        $this->user->update(['organizational_unit_id' => null]);
        $this->actingAs($this->user)->get(route('activities.create'))->assertForbidden();
    }

    /** @return array<string, int|string> */
    private function issuanceData(): array
    {
        return ['beneficiary_id' => $this->student->id, 'activity_id' => $this->activity->id, 'certificate_type_id' => $this->type->id, 'certificate_template_id' => $this->template->id, 'issue_date' => '2026-11-02', 'score' => 85, 'score_label' => 'Distinción'];
    }

    private function issue(): Certificate
    {
        return app(CertificateService::class)->issueCertificate($this->issuanceData(), $this->user);
    }
}
