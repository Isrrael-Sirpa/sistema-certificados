<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\CertificateVerification;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class CertificateScoreAndQuerySafetyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private OrganizationalUnit $unit;

    private OrganizationalUnit $otherUnit;

    private Activity $activity;

    private CertificateType $type;

    private CertificateTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->unit = OrganizationalUnit::create(['name' => 'Química Industrial', 'code' => 'QUI', 'type' => 'carrera', 'is_active' => true]);
        $this->otherUnit = OrganizationalUnit::create(['name' => 'Mecánica Industrial', 'code' => 'MEC', 'type' => 'carrera', 'is_active' => true]);
        $role = Role::create(['name' => Role::ADMIN, 'display_name' => 'Administrador']);
        $this->admin = User::factory()->create(['role_id' => $role->id, 'organizational_unit_id' => null, 'is_active' => true]);
        $this->type = CertificateType::create(['name' => 'Aprobación', 'code' => 'APROB', 'has_score' => true, 'min_passing_score' => 51, 'validity_mode' => 'permanent', 'is_active' => true]);
        $this->template = CertificateTemplate::create(['name' => 'Plantilla', 'slug' => 'plantilla', 'category' => 'institucional', 'orientation' => 'landscape', 'current_version' => 1, 'is_active' => true, 'is_default' => true, 'configuration' => ['body_text' => '{{beneficiario.nombre}}']]);
        $this->activity = $this->makeActivity($this->unit);
    }

    public static function scores(): array
    {
        return [
            'cero' => [60.0, 0.0, false],
            'debajo del mínimo' => [70.0, 69.99, false],
            'igual al mínimo' => [80.0, 80.0, true],
            'mínimo por debajo de 51' => [40.5, 40.5, true],
            'por encima del mínimo' => [90.0, 100.0, true],
            'sin nota' => [70.0, null, false],
        ];
    }

    #[DataProvider('scores')]
    public function test_individual_issuance_respects_boundary_and_keeps_nonpassing_students(float $minimum, ?float $score, bool $issued): void
    {
        $this->activity->update(['min_passing_score' => $minimum]);
        $student = $this->makeStudent('100001', $this->unit);
        $response = $this->actingAs($this->admin)->post(route('certificates.store'), $this->issuanceData($student, $score))->assertRedirect();
        if ($issued) {
            $response->assertSessionHas('success');
            $this->assertDatabaseHas('certificates', ['beneficiary_id' => $student->id, 'score' => $score]);
        } else {
            $response->assertSessionHas('error');
            $this->assertDatabaseCount('certificates', 0);
            $this->assertDatabaseHas('activity_participants', ['activity_id' => $this->activity->id, 'beneficiary_id' => $student->id, 'score' => $score, 'status' => $score === null ? 'observado' : 'reprobado']);
            $this->actingAs($this->admin)->get(route('activities.show', $this->activity))->assertOk()->assertSee($student->document_number);
        }
    }

    public function test_excel_issues_only_passing_students_and_retains_everyone_in_roster(): void
    {
        $csv = "CI,NOMBRES,APELLIDOS,CARRERA,NOTA\n100010,Uno,Reprobado,QUI,50\n100011,Dos,Aprobado,QUI,51\n100012,Tres,Aprobado,QUI,100\n100013,Cuatro,Sinnota,QUI,\n100014,Cinco,Reprobado,QUI,0";
        $this->actingAs($this->admin)->post(route('activities.roster.enroll', $this->activity), ['file' => UploadedFile::fake()->createWithContent('nomina.csv', $csv)])->assertSessionHas('success');
        $this->actingAs($this->admin)->post(route('activities.participants.issue-certificates', $this->activity), ['issue_date' => now()->toDateString()])->assertSessionHas('success');
        $this->assertDatabaseCount('activity_participants', 5);
        $this->assertDatabaseCount('certificates', 2);
        $this->assertSame([51.0, 100.0], Certificate::orderBy('score')->pluck('score')->map(fn ($score) => (float) $score)->all());
        foreach (['100010', '100013', '100014'] as $document) {
            $student = Beneficiary::where('document_number', $document)->firstOrFail();
            $this->assertDatabaseMissing('certificates', ['beneficiary_id' => $student->id]);
            $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id]);
        }
        $this->actingAs($this->admin)->get(route('activities.show', $this->activity))->assertOk()->assertSee('100010')->assertSee('100013')->assertSee('100014');
    }

    public function test_certificate_type_reference_does_not_override_the_activity_minimum(): void
    {
        $this->type->update(['min_passing_score' => 60]);
        $this->activity->update(['min_passing_score' => 75]);
        $student = $this->makeStudent('100020', $this->unit);
        $this->actingAs($this->admin)->post(route('certificates.store'), $this->issuanceData($student, 70))->assertSessionHas('error');
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_scored_activity_requires_its_own_minimum_when_created(): void
    {
        $data = [
            'name' => 'Evento con criterio propio', 'activity_type' => 'Taller', 'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(), 'academic_hours' => 20, 'status' => 'activa', 'requires_payment' => false,
            'certificate_type_id' => $this->type->id, 'organizational_unit_id' => $this->unit->id,
        ];
        $this->actingAs($this->admin)->post(route('activities.store'), $data)->assertSessionHasErrors('min_passing_score');
        $this->post(route('activities.store'), $data + ['min_passing_score' => '70.25'])->assertSessionHas('success');

        $this->assertDatabaseHas('activities', ['name' => $data['name'], 'min_passing_score' => 70.25]);
    }

    public function test_individual_issuance_uses_the_grade_registered_in_the_roster(): void
    {
        $this->activity->update(['min_passing_score' => 70]);
        $student = $this->makeStudent('100024', $this->unit);
        $this->activity->participants()->attach($student, ['score' => 69, 'career_eligible' => true, 'status' => 'reprobado']);

        $this->actingAs($this->admin)->post(route('certificates.store'), $this->issuanceData($student, 90))->assertSessionHas('error');

        $this->assertDatabaseCount('certificates', 0);
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'score' => 69]);
    }

    public function test_import_confirmation_uses_the_current_minimum_and_keeps_it_in_the_issued_certificate(): void
    {
        $this->activity->update(['min_passing_score' => 80]);
        $file = UploadedFile::fake()->createWithContent('nomina.csv', "CI,NOMBRES,APELLIDOS,CARRERA,NOTA\n100022,Uno,Límite,QUI,70\n100023,Dos,Reprobado,QUI,69");
        $this->actingAs($this->admin)->postJson(route('activities.roster.preview', $this->activity), ['file' => $file])
            ->assertOk()->assertJsonPath('rows.0.status', 'failing_student');

        $this->post(route('activities.certificate-config', $this->activity), ['min_passing_score' => 70])->assertSessionHas('success');
        $this->postJson(route('activities.roster.confirm', $this->activity), ['issue_date' => now()->toDateString()])
            ->assertOk()->assertJsonPath('result.issued_count', 1)->assertJsonPath('result.failing_count', 1);

        $certificate = Certificate::sole();
        $this->assertEquals(70, $certificate->snapshot_data['min_passing_score']);
        $this->post(route('activities.certificate-config', $this->activity), ['min_passing_score' => 90])->assertSessionHas('success');
        $this->get(route('verification.show', $certificate->verification_token))->assertOk()->assertSee('Evaluación Oficial Aprobada');
    }

    public function test_service_cannot_issue_scored_certificate_without_grade(): void
    {
        $student = $this->makeStudent('100021', $this->unit);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Debe registrar una calificación');
        app(CertificateService::class)->issueCertificate($this->issuanceData($student, null), $this->admin);
    }

    public static function dashboardRoles(): array
    {
        return [
            'jefe de carrera' => [Role::JEFE_CARRERA, 'carrera', 1],
            'secretaria de carrera' => [Role::SECRETARIA, 'carrera', 1],
            'jefe de departamento' => [Role::JEFE_CARRERA, 'departamento', 1],
            'secretaria de departamento' => [Role::SECRETARIA, 'departamento', 1],
            'superadministrador' => [Role::ADMIN, 'departamento', 2],
        ];
    }

    #[DataProvider('dashboardRoles')]
    public function test_dashboard_scope_for_careers_departments_and_superadministrator(string $roleName, string $unitType, int $expected): void
    {
        $this->unit->update(['type' => $unitType]);
        $role = Role::firstOrCreate(['name' => $roleName], ['display_name' => $roleName]);
        $user = User::factory()->create(['role_id' => $role->id, 'organizational_unit_id' => $this->unit->id, 'is_active' => true]);
        $own = $this->makeCertificate('100030');
        $otherActivity = $this->makeActivity($this->otherUnit);
        $other = $this->makeCertificate('100031', $otherActivity);
        foreach ([$own, $other] as $certificate) {
            CertificateVerification::create(['certificate_id' => $certificate->id, 'verification_method' => 'manual', 'verified_at' => now()]);
        }

        $response = $this->actingAs($user)->get(route('dashboard'))->assertOk()
            ->assertViewHas('totalCertificates', $expected)->assertViewHas('totalActivities', $expected)
            ->assertViewHas('totalBeneficiaries', $expected)->assertViewHas('totalVerifications', $expected);
        $this->assertCount($expected, $response->viewData('recentCertificates'));
        $this->assertCount($expected, $response->viewData('recentVerifications'));
        if ($roleName !== Role::ADMIN) {
            $this->assertSame([$own->id], $response->viewData('recentCertificates')->modelKeys());
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_lists_do_not_load_large_certificate_payloads_and_pdf_keeps_frozen_data(): void
    {
        $certificate = $this->makeCertificate('100040');
        $snapshot = $certificate->snapshot_data;
        $snapshot['large_payload'] = str_repeat('x', 2 * 1024 * 1024);
        $certificate->update(['snapshot_data' => $snapshot, 'custom_fields_data' => ['payload' => str_repeat('y', 1024 * 1024)]]);
        $beneficiary = $this->actingAs($this->admin)->get(route('beneficiaries.show', $certificate->beneficiary))->assertOk()->viewData('beneficiary');
        $this->assertLightCertificate($beneficiary->certificates->first());
        $this->assertLightCertificate($this->get(route('certificates.index'))->assertOk()->viewData('certificates')->first());
        $this->assertLightCertificate($this->get(route('reports.index'))->assertOk()->viewData('certificates')->first());
        $this->assertLightCertificate($this->get(route('activities.show', $this->activity))->assertOk()->viewData('activity')->certificates->first());
        $this->assertLightCertificate($this->get(route('dashboard'))->assertOk()->viewData('recentCertificates')->first());
        $this->assertSame($snapshot, $certificate->fresh()->snapshot_data);
        $this->assertStringStartsWith('%PDF', $this->get(route('certificates.download', $certificate))->assertOk()->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_template_catalog_retains_designs_without_loading_version_history(): void
    {
        $schema = ['large_payload' => str_repeat('x', 1024 * 1024), 'body_text' => 'Plantilla de prueba'];
        $this->template->update(['configuration' => $schema]);
        $this->template->versions()->create(['version_number' => 1, 'name' => 'Inicial', 'design_schema' => $schema]);
        $template = $this->actingAs($this->admin)->get(route('templates.index'))->assertOk()->viewData('templates')->first();
        $this->assertSame($schema, $template->configuration);
        $this->assertFalse($template->relationLoaded('versions'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_audit_list_does_not_load_document_snapshots(): void
    {
        AuditLog::create(['action' => 'test_snapshot', 'description' => 'Prueba de consulta ligera', 'new_values' => ['payload' => str_repeat('x', 2 * 1024 * 1024)]]);
        $log = $this->actingAs($this->admin)->get(route('audit.index'))->assertOk()->viewData('logs')->first();
        $this->assertSame('Prueba de consulta ligera', $log->description);
        $this->assertArrayNotHasKey('new_values', $log->getAttributes());
    }

    private function assertLightCertificate(Certificate $certificate): void
    {
        $this->assertArrayNotHasKey('snapshot_data', $certificate->getAttributes());
        $this->assertArrayNotHasKey('custom_fields_data', $certificate->getAttributes());
        $this->assertNotNull($certificate->certificate_code);
    }

    private function makeActivity(OrganizationalUnit $unit): Activity
    {
        return Activity::create(['name' => 'Evento '.$unit->code, 'activity_type' => 'Taller', 'start_date' => now(), 'end_date' => now(), 'academic_hours' => 20, 'status' => 'activa', 'organizational_unit_id' => $unit->id, 'certificate_type_id' => $this->type->id, 'min_passing_score' => 51, 'target_audience_mode' => 'own_unit']);
    }

    private function makeStudent(string $document, OrganizationalUnit $unit): Beneficiary
    {
        return Beneficiary::create(['first_name' => 'Estudiante '.$document, 'last_name' => 'Prueba', 'document_type' => 'CI', 'document_number' => $document, 'primary_organizational_unit_id' => $unit->id]);
    }

    private function makeCertificate(string $document, ?Activity $activity = null): Certificate
    {
        $activity ??= $this->activity;
        $student = $this->makeStudent($document, $activity->organizationalUnit);
        $data = $this->issuanceData($student, 85);
        $data['activity_id'] = $activity->id;

        return app(CertificateService::class)->issueCertificate($data, $this->admin);
    }

    /** @return array<string, int|float|string|null> */
    private function issuanceData(Beneficiary $student, ?float $score): array
    {
        return ['beneficiary_id' => $student->id, 'activity_id' => $this->activity->id, 'certificate_type_id' => $this->type->id, 'certificate_template_id' => $this->template->id, 'issue_date' => now()->toDateString(), 'score' => $score];

        $response->assertStatus(200);
    }
}
