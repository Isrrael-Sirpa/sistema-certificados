<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\CertificateVerification;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\BeneficiaryImportService;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PendingSystemFixesTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationalUnit $ownUnit;

    private OrganizationalUnit $otherUnit;

    private User $jefe;

    private User $admin;

    private Activity $activity;

    private CertificateTemplate $template;

    private CertificateType $type;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->ownUnit = OrganizationalUnit::create(['name' => 'Carrera de Química Industrial', 'code' => 'QUI', 'type' => 'carrera']);
        $this->otherUnit = OrganizationalUnit::create(['name' => 'Carrera de Mecánica Industrial', 'code' => 'MEC', 'type' => 'carrera']);
        $jefeRole = Role::create(['name' => Role::JEFE_CARRERA, 'display_name' => 'Jefe']);
        $adminRole = Role::create(['name' => Role::ADMIN, 'display_name' => 'Administrador']);
        $this->jefe = User::factory()->create(['role_id' => $jefeRole->id, 'organizational_unit_id' => $this->ownUnit->id]);
        $this->admin = User::factory()->create(['role_id' => $adminRole->id]);
        $this->type = CertificateType::create(['name' => 'Aprobación', 'code' => 'APROB', 'slug' => 'aprobacion', 'has_score' => true, 'min_passing_score' => 75, 'validity_mode' => 'permanent', 'is_active' => true]);
        $this->template = CertificateTemplate::create(['name' => 'Plantilla Institucional', 'slug' => 'institucional', 'category' => 'institucional', 'orientation' => 'landscape', 'current_version' => 1, 'is_active' => true, 'is_default' => true]);
        $this->activity = Activity::create(['name' => 'Evento de Química', 'activity_type' => 'Taller', 'start_date' => now(), 'end_date' => now(), 'academic_hours' => 20, 'status' => 'activa', 'organizational_unit_id' => $this->ownUnit->id, 'certificate_type_id' => $this->type->id, 'min_passing_score' => 75, 'target_audience_mode' => Activity::AUDIENCE_OWN_UNIT]);
    }

    public function test_general_import_assigns_known_career_and_keeps_scoped_records_visible(): void
    {
        $file = UploadedFile::fake()->createWithContent('padron.csv', "CI,NOMBRES,APELLIDOS,CARRERA\n980001,Ana,Importada,QUI\n980002,Sin,Identificar,Desconocida");
        $this->actingAs($this->jefe)->post(route('beneficiaries.import.preview'), ['file' => $file])->assertRedirect();
        $preview = session('beneficiary_bulk_import_data');
        $this->assertSame(1, $preview['summary']['ready']);
        $this->assertSame('pending', $preview['rows'][1]['data']['career_status']);
        $this->assertSame('error', $preview['rows'][1]['status']);

        $this->actingAs($this->jefe)->post(route('beneficiaries.import.store'))->assertSessionHas('success');
        $this->assertDatabaseHas('beneficiaries', ['document_number' => '980001', 'primary_organizational_unit_id' => $this->ownUnit->id]);
        $this->assertDatabaseMissing('beneficiaries', ['document_number' => '980002']);
        $this->actingAs($this->jefe)->get(route('beneficiaries.index'))->assertOk()->assertSee('980001');
    }

    public function test_general_import_rechecks_career_at_confirmation(): void
    {
        $rows = [['status' => 'ready', 'data' => ['document_number' => '980003', 'first_name' => 'Otra', 'last_name' => 'Carrera', 'institution_or_department' => 'MEC']]];
        $result = app(BeneficiaryImportService::class)->executeImport($rows, $this->jefe);
        $this->assertSame(0, $result['created_count']);
        $this->assertSame(1, $result['pending_career_count']);
        $this->assertDatabaseMissing('beneficiaries', ['document_number' => '980003']);
    }

    public function test_administrator_can_identify_imported_pending_careers_in_registry(): void
    {
        $file = UploadedFile::fake()->createWithContent('padron.csv', "CI,NOMBRES,APELLIDOS,CARRERA\n980004,Sin,Identificar,Desconocida");
        $preview = app(BeneficiaryImportService::class)->parseAndPreview($file, $this->admin);
        $this->assertSame('pending', $preview['rows'][0]['data']['career_status']);
        app(BeneficiaryImportService::class)->executeImport($preview['rows'], $this->admin);
        $this->assertDatabaseHas('beneficiaries', ['document_number' => '980004', 'primary_organizational_unit_id' => null]);
        $this->actingAs($this->admin)->get(route('beneficiaries.index'))->assertOk()->assertSee('Carrera pendiente de identificación');
    }

    public function test_changing_event_scope_updates_roster_and_preserves_issued_grade(): void
    {
        $issued = $this->issue('980005');
        $this->activity->participants()->attach($issued->beneficiary_id, ['score' => 85, 'status' => 'certificado', 'career_eligible' => true]);
        $student = $this->student('980006', $this->otherUnit);
        $this->activity->participants()->attach($student, ['score' => 90, 'status' => 'observado', 'career_eligible' => false, 'ineligibility_reason' => 'No autorizado']);
        $data = ['name' => $this->activity->name, 'activity_type' => 'Taller', 'start_date' => now()->toDateString(), 'end_date' => now()->toDateString(), 'academic_hours' => 20, 'status' => 'activa', 'certificate_type_id' => $this->type->id, 'target_audience_mode' => 'specific_units', 'allowed_unit_ids' => [$this->otherUnit->id]];
        $this->actingAs($this->jefe)->put(route('activities.update', $this->activity), $data)->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'career_eligible' => true, 'ineligibility_reason' => null, 'status' => 'inscrito', 'score' => 90]);
        $this->actingAs($this->jefe)->get(route('activities.show', $this->activity))->assertOk()->assertSee('Emitir a');
        $data['target_audience_mode'] = 'own_unit';
        $this->actingAs($this->jefe)->put(route('activities.update', $this->activity), $data)->assertSessionHas('success');
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'career_eligible' => false, 'status' => 'observado', 'score' => 90]);
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $issued->beneficiary_id, 'score' => 85, 'status' => 'certificado']);
        $this->assertSame($issued->snapshot_data, $issued->fresh()->snapshot_data);
    }

    public function test_current_report_includes_permanent_and_excludes_revoked_expired_and_foreign(): void
    {
        $permanent = $this->issue('980007');
        $this->issue('980008')->update(['revoked_at' => now(), 'status' => Certificate::STATUS_REVOCADO]);
        $this->issue('980009')->update(['expiration_date' => now()->subDay(), 'validity_type' => 'years']);
        $this->issue('980010', $this->otherUnit);

        $response = $this->actingAs($this->jefe)->get(route('reports.index', ['status' => 'VIGENTE']))->assertOk();
        $this->assertSame([$permanent->id], $response->viewData('certificates')->modelKeys());
        $response->assertViewHas('totalVigentes', 1);
    }

    public function test_dashboard_scopes_verifications_and_admin_keeps_faculty_totals(): void
    {
        $own = $this->issue('980011');
        $other = $this->issue('980012', $this->otherUnit);
        foreach ([$own, $other] as $certificate) {
            CertificateVerification::create(['certificate_id' => $certificate->id, 'verification_method' => 'manual', 'verified_at' => now()]);
        }
        $this->actingAs($this->jefe)->get(route('dashboard'))->assertOk()->assertViewHas('totalVerifications', 1)
            ->assertViewHas('recentVerifications', fn ($records) => $records->pluck('certificate_id')->all() === [$own->id]);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertViewHas('totalVerifications', 2);
    }

    public static function unitTypes(): array
    {
        return [['carrera', 'Carrera'], ['departamento', 'Departamento'], ['instituto', 'Instituto'], ['administrativo', 'Unidad administrativa']];
    }

    #[DataProvider('unitTypes')]
    public function test_existing_units_accept_long_codes_and_supported_types(string $type, string $label): void
    {
        $this->otherUnit->update(['code' => 'electronica_telecomunicaciones', 'type' => $type]);
        $this->actingAs($this->admin)->put(route('carreras-departamentos.update', $this->otherUnit), ['name' => $this->otherUnit->name, 'code' => $this->otherUnit->code, 'type' => $type, 'responsible_name' => 'Nueva autoridad'])
            ->assertSessionHasNoErrors()->assertSessionHas('success');
        $this->assertSame('Nueva autoridad', $this->otherUnit->fresh()->responsible_name);
        $this->assertSame($label, $this->otherUnit->fresh()->type_label);
        $this->actingAs($this->admin)->get(route('carreras-departamentos.index'))->assertOk()->assertSee($label);
    }

    public function test_unit_validation_rejects_invalid_types_and_codes_over_database_limit(): void
    {
        $this->actingAs($this->admin)->post(route('carreras-departamentos.store'), ['name' => 'Inválida', 'code' => str_repeat('x', 51), 'type' => 'otro'])->assertSessionHasErrors(['code', 'type']);
        $this->assertDatabaseMissing('organizational_units', ['name' => 'Inválida']);
    }

    public function test_units_can_be_paused_but_have_no_broken_delete_action(): void
    {
        $certificate = $this->issue('980013');
        $this->actingAs($this->admin)->delete(route('carreras-departamentos.index').'/'.$this->ownUnit->id)->assertStatus(405);
        $this->actingAs($this->admin)->patch(route('carreras-departamentos.toggle', $this->ownUnit))->assertSessionHas('success');
        $this->assertFalse($this->ownUnit->fresh()->is_active);
        $this->assertDatabaseHas('certificates', ['id' => $certificate->id]);
    }

    public function test_template_catalog_only_offers_actions_allowed_for_each_role(): void
    {
        $this->template->update(['is_default' => false]);
        $this->actingAs($this->jefe)->get(route('templates.index'))->assertOk()
            ->assertDontSee(route('templates.toggle-status', $this->template), false)
            ->assertDontSee(route('templates.set-default', $this->template), false)
            ->assertSee('Duplícala para personalizarla');
        $this->template->update(['organizational_unit_id' => $this->ownUnit->id]);
        $this->actingAs($this->jefe)->get(route('templates.index'))->assertOk()
            ->assertSee(route('templates.toggle-status', $this->template), false)
            ->assertDontSee(route('templates.set-default', $this->template), false);
        $this->actingAs($this->admin)->get(route('templates.index'))->assertOk()->assertSee(route('templates.set-default', $this->template), false);
    }

    public function test_emission_summary_shows_actual_threshold_and_each_omission_reason(): void
    {
        $failed = $this->student('980014', $this->ownUnit);
        $unscored = $this->student('980015', $this->ownUnit);
        $foreign = $this->student('980016', $this->otherUnit);
        $this->activity->participants()->attach($failed, ['score' => 60, 'career_eligible' => true]);
        $this->activity->participants()->attach($unscored, ['score' => null, 'career_eligible' => true]);
        $this->activity->participants()->attach($foreign, ['score' => 90, 'career_eligible' => false]);
        $this->actingAs($this->jefe)->post(route('activities.participants.issue-certificates', $this->activity), ['issue_date' => now()->toDateString()])
            ->assertSessionHas('warning', fn ($message) => str_contains($message, '75 pts') && ! str_contains($message, '51 pts') && str_contains($message, 'Sin calificación: 1') && str_contains($message, 'identificación: 1'));
        $this->assertDatabaseCount('certificates', 0);
    }

    private function student(string $document, OrganizationalUnit $unit): Beneficiary
    {
        return Beneficiary::create(['document_type' => 'CI', 'document_number' => $document, 'first_name' => 'Estudiante '.$document, 'last_name' => 'Prueba', 'primary_organizational_unit_id' => $unit->id]);
    }

    private function issue(string $document, ?OrganizationalUnit $unit = null): Certificate
    {
        $unit ??= $this->ownUnit;
        $activity = $unit->is($this->ownUnit) ? $this->activity : Activity::create(['name' => 'Otro evento', 'activity_type' => 'Taller', 'start_date' => now(), 'end_date' => now(), 'organizational_unit_id' => $unit->id, 'certificate_type_id' => $this->type->id, 'min_passing_score' => 75, 'status' => 'activa']);

        return app(CertificateService::class)->issueCertificate(['activity_id' => $activity->id, 'beneficiary_id' => $this->student($document, $unit)->id, 'certificate_type_id' => $this->type->id, 'certificate_template_id' => $this->template->id, 'issue_date' => now()->toDateString(), 'score' => 85], $this->admin);

        $response->assertStatus(200);
    }
}
