<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\OrganizationalUnit;
use App\Models\Role;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ActivityCostConfirmationTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationalUnit $unit;

    private OrganizationalUnit $otherUnit;

    private User $user;

    private CertificateType $type;

    private CertificateTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->freezeTime();
        $this->unit = OrganizationalUnit::create(['name' => 'Química Industrial', 'code' => 'QUI', 'type' => 'carrera', 'is_active' => true]);
        $this->otherUnit = OrganizationalUnit::create(['name' => 'Mecánica Industrial', 'code' => 'MEC', 'type' => 'carrera', 'is_active' => true]);
        $this->user = $this->makeUser(Role::JEFE_CARRERA, $this->unit);
        $this->type = CertificateType::create(['name' => 'Aprobación', 'code' => 'aprobacion', 'has_score' => true, 'min_passing_score' => 51, 'validity_mode' => 'permanent', 'is_active' => true]);
        $this->template = CertificateTemplate::create(['name' => 'Institucional', 'slug' => 'institucional', 'category' => 'institucional', 'orientation' => 'landscape', 'configuration' => ['elements' => []], 'current_version' => 1, 'is_active' => true, 'is_default' => true]);
    }

    public function test_creation_requires_choice_and_saves_only_fixed_activity_amount(): void
    {
        $this->actingAs($this->user)->get(route('activities.create'))->assertOk()->assertSee('Condiciones de costo de la actividad');
        $this->post(route('activities.store'), $this->activityData())->assertSessionHasErrors('requires_payment');
        $this->post(route('activities.store'), [...$this->activityData(), 'requires_payment' => '1', 'cost_amount' => '50.25'])->assertSessionHas('success');
        $activity = Activity::firstOrFail();
        $this->assertTrue($activity->requires_payment);
        $this->assertSame('50.25', $activity->cost_amount);
        $this->get(route('activities.edit', $activity))->assertOk()->assertSee('Bs 50.25')->assertDontSee('name="cost_amount"', false);
        $this->post(route('activities.store'), [...$this->activityData(), 'name' => 'Evento gratuito', 'requires_payment' => '0', 'cost_amount' => '100'])->assertSessionHas('success');
        $this->assertDatabaseHas('activities', ['name' => 'Evento gratuito', 'requires_payment' => false, 'cost_amount' => 0]);
    }

    public static function invalidAmounts(): array
    {
        return ['sin monto' => [null], 'cero' => ['0'], 'negativo' => ['-1'], 'más de dos decimales' => ['50.001'], 'texto' => ['gratis'], 'sobre límite' => ['100000000']];
    }

    #[DataProvider('invalidAmounts')]
    public function test_paid_activity_rejects_invalid_amounts(?string $amount): void
    {
        $this->actingAs($this->user)->post(route('activities.store'), [...$this->activityData(), 'requires_payment' => '1', 'cost_amount' => $amount])->assertSessionHasErrors('cost_amount');
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_price_and_mode_cannot_change_even_for_administrator(): void
    {
        $activity = $this->makeActivity();
        $this->actingAs($this->makeUser(Role::ADMIN, $this->unit));
        $this->put(route('activities.update', $activity), [...$this->activityData(), 'cost_amount' => '60'])->assertSessionHasErrors('cost_amount');
        $this->put(route('activities.update', $activity), [...$this->activityData(), 'requires_payment' => false])->assertSessionHasErrors('cost_amount');
        $this->put(route('activities.update', $activity), [...$this->activityData(), 'name' => 'Nombre corregido'])->assertSessionHas('success');
        $this->assertDatabaseHas('activities', ['id' => $activity->id, 'name' => 'Nombre corregido', 'requires_payment' => true, 'cost_amount' => 50]);
        $free = $this->makeActivity(false);
        $this->put(route('activities.update', $free), [...$this->activityData(), 'requires_payment' => true, 'cost_amount' => '50'])->assertSessionHasErrors('cost_amount');
        $this->assertFalse($free->fresh()->requires_payment);
    }

    public function test_model_protects_fixed_price(): void
    {
        $activity = $this->makeActivity();
        $this->expectException(ValidationException::class);
        $activity->update(['cost_amount' => '55']);
    }

    public static function confirmationRoles(): array
    {
        return [[Role::ADMIN], [Role::JEFE_CARRERA], [Role::SECRETARIA], [Role::OPERATOR]];
    }

    #[DataProvider('confirmationRoles')]
    public function test_manual_confirmation_is_audited_and_can_return_to_pending_before_issuance(string $role): void
    {
        $activity = $this->makeActivity();
        $student = $this->makeParticipant($activity, '950001');
        $actor = $this->makeUser($role, $this->unit);
        $route = route('activities.participants.payment', [$activity, $student]);
        $this->actingAs($actor)->patch($route, ['payment_confirmed' => '1'])->assertSessionHas('success');
        $this->assertDatabaseHas('activity_participants', ['activity_id' => $activity->id, 'beneficiary_id' => $student->id, 'payment_confirmed' => true]);
        $log = AuditLog::where('action', 'participant_payment_confirmation_updated')->firstOrFail();
        $this->assertSame($actor->id, $log->user_id);
        $this->assertFalse($log->old_values['payment_confirmed']);
        $this->assertTrue($log->new_values['payment_confirmed']);
        $this->patch($route, ['payment_confirmed' => '0'])->assertSessionHas('success');
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'payment_confirmed' => false]);
    }

    public function test_foreign_units_and_unregistered_participants_cannot_change_confirmation(): void
    {
        $activity = $this->makeActivity();
        $student = $this->makeParticipant($activity, '950002');
        $route = route('activities.participants.payment', [$activity, $student]);
        $this->actingAs($this->makeUser(Role::SECRETARIA, $this->otherUnit))->patch($route, ['payment_confirmed' => true])->assertForbidden();
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'payment_confirmed' => false]);
        $activity->participants()->detach($student);
        $this->actingAs($this->user)->patch($route, ['payment_confirmed' => true])->assertNotFound();
    }

    public function test_free_activity_keeps_issuance_without_confirmation_controls(): void
    {
        $activity = $this->makeActivity(false);
        $student = $this->makeParticipant($activity, '950003');
        $this->actingAs($this->user)->get(route('activities.show', $activity))->assertOk()->assertDontSee('Filtrar confirmaciones')->assertDontSee('Marcar Completado');
        $this->patch(route('activities.participants.payment', [$activity, $student]), ['payment_confirmed' => true])->assertSessionHasErrors('payment_confirmed');
        $certificate = app(CertificateService::class)->issueCertificate($this->certificateData($activity, $student), $this->user);
        $this->assertSame($student->id, $certificate->beneficiary_id);
    }

    public function test_individual_issuance_rejects_pending_even_when_request_claims_completed(): void
    {
        $activity = $this->makeActivity();
        $student = $this->makeParticipant($activity, '950004');
        $this->actingAs($this->user)->post(route('certificates.store'), [...$this->certificateData($activity, $student), 'payment_confirmed' => true])->assertSessionHasErrors('payment_confirmed');
        $this->assertDatabaseCount('certificates', 0);
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'payment_confirmed' => false]);
        $this->patch(route('activities.participants.payment', [$activity, $student]), ['payment_confirmed' => true])->assertSessionHas('success');
        $this->post(route('certificates.store'), $this->certificateData($activity, $student))->assertSessionHas('success');
        $this->assertDatabaseCount('certificates', 1);
        $this->patch(route('activities.participants.payment', [$activity, $student]), ['payment_confirmed' => false])->assertSessionHas('error');
        $this->delete(route('activities.participants.remove', [$activity, $student]))->assertSessionHas('error');
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'payment_confirmed' => true]);
    }

    public function test_mass_issuance_skips_pending_and_academic_restrictions_then_issues_later_without_duplicates(): void
    {
        $activity = $this->makeActivity();
        $approved = $this->makeParticipant($activity, '950010', true);
        $pending = $this->makeParticipant($activity, '950011');
        $failed = $this->makeParticipant($activity, '950012', true, 50);
        $foreign = $this->makeParticipant($activity, '950013', true, 85, $this->otherUnit);
        $this->actingAs($this->user)->get(route('activities.show', $activity))->assertOk()->assertSee('Confirmación del encargado')->assertSee('Emitir a Habilitados (1)');
        $route = route('activities.participants.issue-certificates', $activity);
        $this->post($route, ['issue_date' => now()->toDateString()])->assertSessionHas('success');
        $this->assertDatabaseHas('certificates', ['beneficiary_id' => $approved->id]);
        foreach ([$pending, $failed, $foreign] as $student) {
            $this->assertDatabaseMissing('certificates', ['beneficiary_id' => $student->id]);
        }
        $this->assertDatabaseCount('activity_participants', 4);
        $this->patch(route('activities.participants.payment', [$activity, $pending]), ['payment_confirmed' => true])->assertSessionHas('success');
        $this->post($route, ['issue_date' => now()->toDateString()])->assertSessionHas('success');
        $this->post($route, ['issue_date' => now()->toDateString()])->assertSessionHas('warning');
        $this->assertDatabaseCount('certificates', 2);
    }

    public function test_alternate_batch_route_also_skips_pending(): void
    {
        $activity = $this->makeActivity();
        $approved = $this->makeParticipant($activity, '950020', true);
        $pending = $this->makeParticipant($activity, '950021');
        $this->actingAs($this->user)->post(route('activities.batch-issue', $activity), ['beneficiary_ids' => [$approved->id, $pending->id], 'issue_date' => now()->toDateString()])->assertSessionHas('success');
        $this->assertDatabaseCount('certificates', 1);
        $this->assertDatabaseMissing('certificates', ['beneficiary_id' => $pending->id]);
    }

    public function test_reimport_preserves_confirmation_and_uploaded_columns_cannot_confirm_it(): void
    {
        $activity = $this->makeActivity();
        $student = $this->makeParticipant($activity, '950030', true);
        $csv = "CI,NOMBRES,APELLIDOS,CARRERA,NOTA,PAGO\n950030,Ana,Prueba,QUI,90,Pendiente\n950031,Luis,Nuevo,QUI,85,Completado";
        $this->actingAs($this->user)->post(route('activities.roster.enroll', $activity), ['file' => UploadedFile::fake()->createWithContent('nomina.csv', $csv)])->assertSessionHas('success');
        $new = Beneficiary::where('document_number', '950031')->firstOrFail();
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'payment_confirmed' => true, 'score' => 90]);
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $new->id, 'payment_confirmed' => false]);
    }

    public function test_excel_confirmation_enrolls_pending_without_issuing_and_cannot_inject_confirmation(): void
    {
        $activity = $this->makeActivity();
        $row = ['status' => 'new_student', 'data' => ['document_number' => '950040', 'first_name' => 'Rosa', 'last_name' => 'Prueba', 'institution_or_department' => 'QUI', 'score' => 90, 'payment_confirmed' => true]];
        $this->actingAs($this->user)->postJson(route('activities.roster.confirm', $activity), ['rows' => [$row], 'issue_date' => now()->toDateString()])->assertOk()->assertJsonPath('result.issued_count', 0)->assertJsonPath('result.payment_pending_count', 1);
        $student = Beneficiary::where('document_number', '950040')->firstOrFail();
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'payment_confirmed' => false]);
        $this->assertDatabaseCount('certificates', 0);
    }

    public function test_excel_preview_reports_pending_as_importable_but_not_ready_to_certify(): void
    {
        $activity = $this->makeActivity();
        $student = $this->makeParticipant($activity, '950041');
        $csv = "CI,NOMBRES,APELLIDOS,CARRERA,NOTA\n950041,Ana,Prueba,QUI,85";
        $this->actingAs($this->user)->postJson(route('activities.roster.preview', $activity), ['file' => UploadedFile::fake()->createWithContent('nomina.csv', $csv)])
            ->assertOk()->assertJsonPath('summary.ready_to_certify', 0)->assertJsonPath('summary.payment_pending', 1)->assertJsonPath('can_import', true);
        $this->patch(route('activities.participants.payment', [$activity, $student]), ['payment_confirmed' => true])->assertSessionHas('success');
        $this->postJson(route('activities.roster.preview', $activity), ['file' => UploadedFile::fake()->createWithContent('nomina.csv', $csv)])
            ->assertOk()->assertJsonPath('summary.ready_to_certify', 1)->assertJsonPath('rows.0.payment_confirmed', true);
    }

    public function test_service_rejects_paid_activity_without_a_confirmed_roster_entry(): void
    {
        $activity = $this->makeActivity();
        $student = Beneficiary::create(['document_type' => 'CI', 'document_number' => '950042', 'first_name' => 'Ana', 'last_name' => 'Prueba', 'primary_organizational_unit_id' => $this->unit->id]);
        $this->expectException(ValidationException::class);
        app(CertificateService::class)->issueCertificate($this->certificateData($activity, $student), $this->user);
    }

    public function test_authorized_other_career_can_be_confirmed_and_pending_can_be_removed_with_audit(): void
    {
        $activity = $this->makeActivity(true, ['target_audience_mode' => 'specific_units']);
        $activity->allowedUnits()->attach($this->otherUnit);
        $student = $this->makeParticipant($activity, '950050', false, 85, $this->otherUnit);
        $this->actingAs($this->user)->patch(route('activities.participants.payment', [$activity, $student]), ['payment_confirmed' => true])->assertSessionHas('success');
        $this->post(route('certificates.store'), $this->certificateData($activity, $student))->assertSessionHas('success');
        $pending = $this->makeParticipant($activity, '950051');
        $this->delete(route('activities.participants.remove', [$activity, $pending]))->assertSessionHas('success');
        $this->assertDatabaseMissing('activity_participants', ['beneficiary_id' => $pending->id]);
        $this->assertDatabaseHas('beneficiaries', ['id' => $pending->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'activity_participant_removed', 'user_id' => $this->user->id]);
    }

    public function test_upgrade_defaults_existing_activity_to_free_and_preserves_certificates(): void
    {
        $activity = $this->makeActivity(false);
        $student = $this->makeParticipant($activity, '950060');
        $certificate = app(CertificateService::class)->issueCertificate($this->certificateData($activity, $student), $this->user);
        $snapshot = $certificate->snapshot_data;
        $migration = require database_path('migrations/2026_11_05_025327_add_manual_payment_confirmation_to_activities.php');
        $migration->down();
        $migration->up();
        $this->assertFalse($activity->fresh()->requires_payment);
        $this->assertSame('0.00', $activity->fresh()->cost_amount);
        $this->assertSame($snapshot, $certificate->fresh()->snapshot_data);
        $this->assertDatabaseHas('activity_participants', ['beneficiary_id' => $student->id, 'payment_confirmed' => false]);
    }

    public function test_roster_filter_and_editor_accept_names_and_labels_with_quotes_and_newlines(): void
    {
        $activity = $this->makeActivity();
        $student = $this->makeParticipant($activity, '950061');
        $student->update(['first_name' => "Ana D'Ávila \"A\""]);
        $activity->participants()->updateExistingPivot($student->id, ['score_label' => "Nota \"A\"\nConfirmada"]);
        $html = $this->actingAs($this->user)->get(route('activities.show', $activity))->assertOk()->getContent();
        $previous = libxml_use_internal_errors(true);
        $document = new \DOMDocument;
        $document->loadHTML('<?xml encoding="UTF-8">'.$html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($document);
        $handler = $xpath->query('//*[@title="Editar Calificación / Rol"]')->item(0)->getAttribute('x-on:click');
        $this->assertNotSame('', $handler);
        $root = $xpath->query('//div[@x-data and contains(@x-data, "paymentFilter")]')->item(0)->getAttribute('x-data');
        $filter = $xpath->query('//tr[@x-show]')->item(0)->getAttribute('x-show');
        $script = <<<'JS'
let input = '';
process.stdin.on('data', chunk => input += chunk);
process.stdin.on('end', () => {
    const data = JSON.parse(input);
    const state = new Function('return (' + data.root + ')')();
    const result = new Function('let editingParticipant, editParticipantModal; ' + data.handler + '; return {editingParticipant, editParticipantModal};')();
    const show = new Function('paymentFilter', 'return (' + data.filter + ')');
    result.defaultFilter = state.paymentFilter;
    result.matches = [show('all'), show('pending'), show('completed')];
    process.stdout.write(JSON.stringify(result));
});
JS;
        $process = new Process(['node', '-e', $script], base_path(), null, json_encode(['handler' => $handler, 'root' => $root, 'filter' => $filter], JSON_THROW_ON_ERROR));
        $process->mustRun();
        $result = json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($result['editParticipantModal']);
        $this->assertSame('all', $result['defaultFilter']);
        $this->assertSame([true, true, false], $result['matches']);
        $this->assertSame($student->full_title_name, $result['editingParticipant']['name']);
        $this->assertSame("Nota \"A\"\nConfirmada", $result['editingParticipant']['score_label']);
    }

    /** @return array<string, mixed> */
    private function activityData(): array
    {
        return ['name' => 'Seminario de Química', 'activity_type' => 'Seminario', 'start_date' => now()->toDateString(), 'end_date' => now()->addWeek()->toDateString(), 'academic_hours' => 40, 'status' => 'activa', 'certificate_type_id' => $this->type->id, 'min_passing_score' => 51];
    }

    /** @param array<string, mixed> $attributes */
    private function makeActivity(bool $paid = true, array $attributes = []): Activity
    {
        return Activity::create([...$this->activityData(), 'organizational_unit_id' => $this->unit->id, 'target_audience_mode' => 'own_unit', 'requires_payment' => $paid, 'cost_amount' => $paid ? '50.00' : '0.00', ...$attributes]);
    }

    private function makeUser(string $name, OrganizationalUnit $unit): User
    {
        $role = Role::firstOrCreate(['name' => $name], ['display_name' => $name]);

        return User::factory()->create(['role_id' => $role->id, 'organizational_unit_id' => $unit->id, 'is_active' => true]);
    }

    private function makeParticipant(Activity $activity, string $document, bool $confirmed = false, ?float $score = 85, ?OrganizationalUnit $unit = null): Beneficiary
    {
        $unit ??= $this->unit;
        $student = Beneficiary::create(['document_type' => 'CI', 'document_number' => $document, 'first_name' => 'Ana', 'last_name' => 'Prueba', 'primary_organizational_unit_id' => $unit->id]);
        $activity->participants()->attach($student, ['role' => 'Participante', 'status' => 'inscrito', 'score' => $score, 'career_eligible' => $activity->isCareerAllowed($unit->id), 'payment_confirmed' => $confirmed]);

        return $student;
    }

    /** @return array<string, int|float|string> */
    private function certificateData(Activity $activity, Beneficiary $student): array
    {
        return ['activity_id' => $activity->id, 'beneficiary_id' => $student->id, 'certificate_type_id' => $this->type->id, 'certificate_template_id' => $this->template->id, 'score' => 85, 'issue_date' => now()->toDateString()];
    }
}
