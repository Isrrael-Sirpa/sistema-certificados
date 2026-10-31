<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\Setting;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class MultiTypeActivityAndScoreCertificateTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected CertificateType $aprobacionType;

    protected CertificateType $asistenciaType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('is_active', true)->first();
        $this->aprobacionType = CertificateType::where('code', 'aprobacion')->first()
            ?? CertificateType::create([
                'code' => 'aprobacion',
                'name' => 'Certificado de Aprobación',
                'description' => 'Acreditación con nota',
                'validity_mode' => 'permanent',
                'has_score' => true,
                'is_active' => true,
            ]);
        $this->aprobacionType->update(['has_score' => true, 'validity_mode' => 'permanent']);

        $this->asistenciaType = CertificateType::where('code', 'asistencia')->first()
            ?? CertificateType::create([
                'code' => 'asistencia',
                'name' => 'Certificado de Asistencia',
                'description' => 'Asistencia general',
                'validity_mode' => 'temporal',
                'default_validity_years' => 3,
                'has_score' => false,
                'is_active' => true,
            ]);
    }

    public function test_activity_can_be_created_with_certificate_type_and_validity_scheme(): void
    {
        $response = $this->actingAs($this->user)->post(route('activities.store'), [
            'name' => 'Taller Avanzado de Machine Learning 2026',
            'activity_type' => 'Taller',
            'certificate_type_id' => $this->aprobacionType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'academic_hours' => 30,
            'status' => 'activa',
            'validity_mode' => 'permanent',
            'show_score_on_pdf' => 1,
            'show_score_on_portal' => 1,
        ]);

        $response->assertRedirect();

        $activity = Activity::where('name', 'Taller Avanzado de Machine Learning 2026')->first();
        $this->assertNotNull($activity);
        $this->assertEquals($this->aprobacionType->id, $activity->certificate_type_id);

        $config = $activity->certificateConfig;
        $this->assertNotNull($config);
        $this->assertEquals('permanent', $config->validity_mode);
        $this->assertTrue($config->show_score_on_pdf);
        $this->assertTrue($config->show_score_on_portal);
    }

    public function test_activity_roster_enroll_detects_and_saves_scores_from_file(): void
    {
        $activity = Activity::create([
            'name' => 'Curso de Robótica e IoT',
            'activity_type' => 'Curso',
            'certificate_type_id' => $this->aprobacionType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'academic_hours' => 40,
            'status' => 'activa',
        ]);

        $csvContent = implode("\n", [
            'CI,NOMBRES,APELLIDOS,CARRERA,CORREO,NOTA,MENCION',
            '10020030 LP,Carlos,Mamani Flores,Informática,carlos@umsa.bo,92.5,92.5/100 - Aprobado con Distinción',
            '10020031 LP,Laura,Choque Quispe,Química,laura@umsa.bo,88,88/100',
        ]);

        $file = UploadedFile::fake()->createWithContent('nomina_estudiantes_notas.csv', $csvContent);

        $response = $this->actingAs($this->user)->post(
            route('activities.roster.enroll', $activity),
            ['file' => $file]
        );

        $response->assertRedirect();

        $activity->refresh();
        $this->assertCount(2, $activity->participants);

        $carlos = $activity->participants->firstWhere('document_number', '10020030 LP');
        $this->assertNotNull($carlos);
        $this->assertEquals(92.5, (float) $carlos->pivot->score);
        $this->assertEquals('92.5/100 - Aprobado con Distinción', $carlos->pivot->score_label);
        $this->assertEquals('inscrito', $carlos->pivot->status);

        $laura = $activity->participants->firstWhere('document_number', '10020031 LP');
        $this->assertNotNull($laura);
        $this->assertEquals(88.0, (float) $laura->pivot->score);
    }

    public function test_activity_bulk_issuance_creates_permanent_certificates_with_scores(): void
    {
        $activity = Activity::create([
            'name' => 'Seminario de Tecnologías Abiertas',
            'activity_type' => 'Seminario',
            'certificate_type_id' => $this->aprobacionType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'academic_hours' => 20,
            'status' => 'activa',
        ]);

        $activity->certificateConfig()->create([
            'validity_mode' => 'permanent',
            'show_score_on_pdf' => true,
            'show_score_on_portal' => true,
        ]);

        $ben = Beneficiary::create([
            'first_name' => 'Alvaro',
            'last_name' => 'Gomez',
            'document_type' => 'CI',
            'document_number' => '55667788 LP',
            'email' => 'alvaro@umsa.bo',
        ]);

        $activity->participants()->attach($ben->id, [
            'role' => 'Participante',
            'score' => 95.0,
            'score_label' => '95/100 - Sobresaliente',
            'status' => 'inscrito',
        ]);

        $response = $this->actingAs($this->user)->post(
            route('activities.participants.issue-certificates', $activity),
            [
                'certificate_type_id' => $this->aprobacionType->id,
                'issue_date' => now()->toDateString(),
            ]
        );

        $response->assertRedirect();

        $cert = Certificate::where('beneficiary_id', $ben->id)
            ->where('activity_id', $activity->id)
            ->first();

        $this->assertNotNull($cert);
        $this->assertTrue($cert->isPermanent());
        $this->assertNull($cert->expiration_date);
        $this->assertEquals('permanent', $cert->validity_type);
        $this->assertEquals(95.0, (float) $cert->score);
        $this->assertEquals('95/100 - Sobresaliente', $cert->score_label);

        // Snapshot must have frozen score and permanent validity
        $this->assertTrue($cert->snapshot_data['is_permanent'] ?? false);
        $this->assertEquals(95.0, (float) ($cert->snapshot_data['score'] ?? 0));
    }

    public function test_participant_score_can_be_updated_in_activity(): void
    {
        $activity = Activity::create([
            'name' => 'Taller de Diseño 3D',
            'activity_type' => 'Taller',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'academic_hours' => 10,
            'status' => 'activa',
        ]);

        $ben = Beneficiary::create([
            'first_name' => 'Raul',
            'last_name' => 'Condori',
            'document_type' => 'CI',
            'document_number' => '44556677 LP',
            'email' => 'raul@umsa.bo',
        ]);

        $activity->participants()->attach($ben->id, [
            'role' => 'Participante',
            'score' => 70.0,
            'score_label' => 'Aprobado',
            'status' => 'inscrito',
        ]);

        $response = $this->actingAs($this->user)->patch(
            route('activities.participants.update', [$activity, $ben]),
            [
                'role' => 'Participante Destacado',
                'score' => 85.5,
                'score_label' => '85.5/100 - Distinción',
            ]
        );

        $response->assertRedirect();

        $participant = $activity->fresh()->participants->firstWhere('id', $ben->id);
        $this->assertEquals('Participante Destacado', $participant->pivot->role);
        $this->assertEquals(85.5, (float) $participant->pivot->score);
        $this->assertEquals('85.5/100 - Distinción', $participant->pivot->score_label);
    }

    public function test_public_verification_displays_score_and_permanent_validity(): void
    {
        $activity = Activity::create([
            'name' => 'Diplomado en Ciberseguridad',
            'activity_type' => 'Diplomado',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(6)->toDateString(),
            'academic_hours' => 200,
            'status' => 'activa',
        ]);

        $ben = Beneficiary::create([
            'first_name' => 'Elena',
            'last_name' => 'Ramos',
            'document_type' => 'CI',
            'document_number' => '33445566 LP',
            'email' => 'elena@umsa.bo',
        ]);

        $cert = app(CertificateService::class)->issueCertificate([
            'beneficiary_id' => $ben->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $this->aprobacionType->id,
            'issue_date' => now()->toDateString(),
            'validity_type' => 'permanent',
            'score' => 98.0,
            'score_label' => '98/100 - Máximo Honor',
        ], $this->user);

        $response = $this->get(route('verification.show', ['token' => $cert->verification_token]));

        $response->assertStatus(200);
        $response->assertSee('Elena Ramos');
        $response->assertSee('Diplomado en Ciberseguridad');
        $response->assertSee('Permanente');
        $response->assertSee('98');
        $response->assertSee('Máximo Honor');
    }

    public function test_activity_bulk_issuance_defaults_to_activity_configured_type_and_validity(): void
    {
        $activity = Activity::create([
            'name' => 'Coloquio de Informática 2026',
            'activity_type' => 'Seminario',
            'certificate_type_id' => $this->aprobacionType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'academic_hours' => 20,
            'status' => 'activa',
        ]);

        $activity->certificateConfig()->create([
            'validity_mode' => 'permanent',
            'show_score_on_pdf' => true,
            'show_score_on_portal' => true,
        ]);

        $ben = Beneficiary::create([
            'first_name' => 'Mario',
            'last_name' => 'Vargas',
            'document_type' => 'CI',
            'document_number' => '77889900 LP',
            'email' => 'mario@umsa.bo',
        ]);

        $activity->participants()->attach($ben->id, [
            'role' => 'Participante',
            'score' => 90.0,
            'score_label' => '90 pts',
            'status' => 'inscrito',
        ]);

        // Post without certificate_type_id (emulating the simplified confirmation modal)
        $response = $this->actingAs($this->user)->post(
            route('activities.participants.issue-certificates', $activity),
            [
                'issue_date' => now()->toDateString(),
            ]
        );

        $response->assertRedirect();

        $cert = Certificate::where('beneficiary_id', $ben->id)
            ->where('activity_id', $activity->id)
            ->first();

        $this->assertNotNull($cert);
        $this->assertEquals($this->aprobacionType->id, $cert->certificate_type_id);
        $this->assertTrue($cert->isPermanent());
        $this->assertNull($cert->expiration_date);
    }

    public function test_activity_view_renders_confirmation_summary_for_configured_type(): void
    {
        $activity = Activity::create([
            'name' => 'Taller de Python Avanzado',
            'activity_type' => 'Taller',
            'certificate_type_id' => $this->aprobacionType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'academic_hours' => 20,
            'status' => 'activa',
        ]);

        $activity->certificateConfig()->create([
            'validity_mode' => 'permanent',
            'show_score_on_pdf' => true,
            'show_score_on_portal' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('activities.show', $activity));

        $response->assertStatus(200);
        $response->assertSee('Certificación Configurada');
        $response->assertSee('Vigencia Vitalicia');
        $response->assertSee('Certificado de Aprobación');
        $response->assertSee('type="hidden" name="certificate_type_id"', false);
    }

    public function test_temporal_activity_types_issue_with_expiration_date(): void
    {
        $activity = Activity::create([
            'name' => 'Conferencia Internacional de Ciencias',
            'activity_type' => 'Conferencia',
            'certificate_type_id' => $this->asistenciaType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'academic_hours' => 10,
            'status' => 'activa',
        ]);

        $activity->certificateConfig()->create([
            'validity_mode' => 'years',
            'validity_years' => 3,
        ]);

        $ben = Beneficiary::create([
            'first_name' => 'Patricia',
            'last_name' => 'Quispe',
            'document_type' => 'CI',
            'document_number' => '99887766 LP',
            'email' => 'patricia@umsa.bo',
        ]);

        $activity->participants()->attach($ben->id, [
            'role' => 'Asistente',
            'status' => 'inscrito',
        ]);

        $issueDate = now()->toDateString();
        $response = $this->actingAs($this->user)->post(
            route('activities.participants.issue-certificates', $activity),
            [
                'issue_date' => $issueDate,
            ]
        );

        $response->assertRedirect();

        $cert = Certificate::where('beneficiary_id', $ben->id)
            ->where('activity_id', $activity->id)
            ->first();

        $this->assertNotNull($cert);
        $this->assertFalse($cert->isPermanent());
        $this->assertNotNull($cert->expiration_date);
        $this->assertEquals(now()->addYears(3)->toDateString(), $cert->expiration_date->toDateString());
    }

    public function test_standalone_certificate_creation_redirects_to_activities_and_ui_is_unified(): void
    {
        $response = $this->actingAs($this->user)->get(route('certificates.create'));
        $response->assertRedirect(route('activities.index'));
        $response->assertSessionHas('info');

        $activity = Activity::create([
            'name' => 'Taller de Innovación Abierta',
            'activity_type' => 'Taller',
            'certificate_type_id' => $this->asistenciaType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(1)->toDateString(),
            'academic_hours' => 10,
            'status' => 'activa',
        ]);

        $showResponse = $this->actingAs($this->user)->get(route('activities.show', $activity));
        $showResponse->assertStatus(200);
        $showResponse->assertDontSee('+ Emitir Individual');
        $showResponse->assertSee('Nueva Actividad');
        $showResponse->assertSee('Volver a Actividades');
    }

    public function test_settings_verification_portal_options_can_be_updated(): void
    {
        $response = $this->actingAs($this->user)->post(route('settings.update'), [
            'tab_verification_portal' => '1',
            'portal_verify_title' => 'Portal de Validación Central UMSA',
            'portal_verify_subtitle' => 'Validación de méritos y calificaciones oficiales',
            'portal_verify_show_score_badge' => '1',
            'portal_verify_show_type_badge' => '1',
            'portal_verify_show_validity_badge' => '1',
            'portal_verify_show_types_card' => '1',
            'portal_verify_footer_note' => 'Validación legal respaldada por la Facultad de Tecnología.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('settings', [
            'key' => 'portal_verify_title',
            'value' => 'Portal de Validación Central UMSA',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'portal_verify_show_score_badge',
            'value' => '1',
        ]);
        $this->assertDatabaseHas('settings', [
            'key' => 'portal_verify_footer_note',
            'value' => 'Validación legal respaldada por la Facultad de Tecnología.',
        ]);
    }

    public function test_certificate_type_creation_update_and_toggle_via_settings(): void
    {
        $createResponse = $this->actingAs($this->user)->post(route('settings.certificate-types.store'), [
            'name' => 'Certificado de Excelencia Docente',
            'code' => 'excelencia_docente',
            'description' => 'Acreditación especial por rendimiento académico destacado',
            'validity_mode' => 'permanent',
            'has_score' => '1',
            'score_scale' => 'Sobre 100 puntos',
            'min_passing_score' => 90,
            'icon' => '🌟',
            'badge_color' => 'amber',
        ]);

        $createResponse->assertRedirect(route('settings.index', ['tab' => 'certificates']));

        $newType = CertificateType::where('code', 'excelencia_docente')->first();
        $this->assertNotNull($newType);
        $this->assertEquals('Certificado de Excelencia Docente', $newType->name);
        $this->assertEquals('permanent', $newType->validity_mode);
        $this->assertTrue($newType->has_score);
        $this->assertEquals(90, $newType->min_passing_score);
        $this->assertTrue($newType->is_active);

        // Update policies
        $updateResponse = $this->actingAs($this->user)->put(route('settings.certificate-types.update', $newType), [
            'name' => 'Certificado de Excelencia Docente UMSA',
            'description' => 'Actualizado con periodo de vigencia temporal',
            'validity_mode' => 'years',
            'default_validity_years' => 5,
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect(route('settings.index', ['tab' => 'certificates']));
        $newType->refresh();
        $this->assertEquals('Certificado de Excelencia Docente UMSA', $newType->name);
        $this->assertEquals('years', $newType->validity_mode);
        $this->assertEquals(5, $newType->default_validity_years);

        // Toggle status
        $toggleResponse = $this->actingAs($this->user)->patch(route('settings.certificate-types.toggle', $newType));
        $toggleResponse->assertRedirect(route('settings.index', ['tab' => 'certificates']));
        $newType->refresh();
        $this->assertFalse($newType->is_active);
    }

    public function test_public_verification_view_respects_settings(): void
    {
        $activity = Activity::create([
            'name' => 'Diplomado en Inteligencia Artificial',
            'activity_type' => 'Diplomado',
            'certificate_type_id' => $this->aprobacionType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(30)->toDateString(),
            'academic_hours' => 120,
            'status' => 'activa',
        ]);

        $ben = Beneficiary::create([
            'first_name' => 'Alejandro',
            'last_name' => 'Ramos',
            'document_type' => 'CI',
            'document_number' => '55443322 LP',
            'email' => 'alejandro@umsa.bo',
        ]);

        $service = app(CertificateService::class);
        $cert = $service->issueCertificate([
            'beneficiary_id' => $ben->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $this->aprobacionType->id,
            'issue_date' => now()->toDateString(),
            'score' => 97.5,
            'score_label' => '97.5/100 - Distinción Máxima',
        ], $this->user);

        // Default verification view shows score and type
        $response = $this->get(route('verification.show', $cert->verification_token));
        $response->assertStatus(200);
        $response->assertSee('97.5/100 - Distinción Máxima');
        $response->assertSee('Certificado de Aprobación');

        // Turn off score badge in settings
        Setting::updateOrCreate(
            ['key' => 'portal_verify_show_score_badge'],
            ['value' => '0', 'group' => 'verification_portal', 'display_name' => 'Mostrar Score', 'type' => 'boolean']
        );

        $responseNoScore = $this->get(route('verification.show', $cert->verification_token));
        $responseNoScore->assertStatus(200);
        $responseNoScore->assertDontSee('Calificación Académica Acreditada');

        // Verify search page shows types card
        $searchResponse = $this->get(route('verification.index'));
        $searchResponse->assertStatus(200);
        $searchResponse->assertSee('Tipos de Certificación Institucional');
    }

    public function test_failing_students_are_detected_and_omitted_from_certificate_issuance(): void
    {
        $activity = Activity::create([
            'name' => 'Materia de Algoritmos Complejos',
            'activity_type' => 'Curso',
            'certificate_type_id' => $this->aprobacionType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(20)->toDateString(),
            'academic_hours' => 60,
            'status' => 'activa',
        ]);

        $approvedStudent = Beneficiary::create([
            'first_name' => 'Gonzalo',
            'last_name' => 'Condori',
            'document_type' => 'CI',
            'document_number' => '11223344 LP',
            'email' => 'gonzalo@umsa.bo',
        ]);

        $failingStudent = Beneficiary::create([
            'first_name' => 'Raul',
            'last_name' => 'Mamani',
            'document_type' => 'CI',
            'document_number' => '22334455 LP',
            'email' => 'raul@umsa.bo',
        ]);

        $unscoredStudent = Beneficiary::create([
            'first_name' => 'Sonia',
            'last_name' => 'Paredes',
            'document_type' => 'CI',
            'document_number' => '33445566 LP',
            'email' => 'sonia@umsa.bo',
        ]);

        // Attach participants
        $activity->participants()->attach($approvedStudent->id, [
            'role' => 'Participante',
            'score' => 85.0,
            'score_label' => '85/100',
            'status' => 'inscrito',
        ]);

        $activity->participants()->attach($failingStudent->id, [
            'role' => 'Participante',
            'score' => 45.0,
            'score_label' => '45/100',
            'status' => 'reprobado',
        ]);

        $activity->participants()->attach($unscoredStudent->id, [
            'role' => 'Participante',
            'score' => null,
            'status' => 'inscrito',
        ]);

        // View shows correct breakdown
        $viewResponse = $this->actingAs($this->user)->get(route('activities.show', $activity));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('1 aprobados pendientes');
        $viewResponse->assertSee('1 reprobados (&lt; 51 pts)', false);
        $viewResponse->assertSee('REPROBADO (No califica)');

        // Issue certificates to roster
        $issueResponse = $this->actingAs($this->user)->post(
            route('activities.participants.issue-certificates', $activity),
            [
                'issue_date' => now()->toDateString(),
            ]
        );

        $issueResponse->assertRedirect();
        $issueResponse->assertSessionHas('success');

        // Verify Gonzalo got certificate
        $gonzaloCert = Certificate::where('beneficiary_id', $approvedStudent->id)
            ->where('activity_id', $activity->id)
            ->first();
        $this->assertNotNull($gonzaloCert);
        $this->assertEquals(85.0, (float) $gonzaloCert->score);

        // Verify Raul (45 pts) did NOT get certificate
        $raulCert = Certificate::where('beneficiary_id', $failingStudent->id)
            ->where('activity_id', $activity->id)
            ->first();
        $this->assertNull($raulCert);

        // Verify Sonia (no score) did NOT get certificate
        $soniaCert = Certificate::where('beneficiary_id', $unscoredStudent->id)
            ->where('activity_id', $activity->id)
            ->first();
        $this->assertNull($soniaCert);

        // Raul's status in pivot remains 'reprobado'
        $failingPivot = $activity->participants()->where('beneficiaries.id', $failingStudent->id)->first()->pivot;
        $this->assertEquals('reprobado', $failingPivot->status);
    }

    public function test_cannot_issue_certificate_with_failing_score_directly(): void
    {
        $activity = Activity::create([
            'name' => 'Diplomado en Robótica',
            'activity_type' => 'Diplomado',
            'certificate_type_id' => $this->aprobacionType->id,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'academic_hours' => 50,
            'status' => 'activa',
        ]);

        $ben = Beneficiary::create([
            'first_name' => 'Boris',
            'last_name' => 'Mendoza',
            'document_type' => 'CI',
            'document_number' => '44556677 LP',
            'email' => 'boris@umsa.bo',
        ]);

        $service = app(CertificateService::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La nota mínima reglamentaria de aprobación es de 51 pts');

        $service->issueCertificate([
            'beneficiary_id' => $ben->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $this->aprobacionType->id,
            'issue_date' => now()->toDateString(),
            'score' => 40.0,
        ], $this->user);
    }

    public function test_roster_bulk_issuance_uses_each_students_activity_grade_in_the_template(): void
    {
        $this->assertBulkIssuanceUsesIndividualGrades('activities.participants.issue-certificates');
    }

    public function test_alternative_bulk_issuance_uses_each_students_activity_grade_in_the_template(): void
    {
        $this->assertBulkIssuanceUsesIndividualGrades('activities.batch-issue');
    }

    private function assertBulkIssuanceUsesIndividualGrades(string $routeName): void
    {
        $this->aprobacionType->update(['min_passing_score' => 80]);
        $template = CertificateTemplate::firstOrFail();
        $schema = $template->configuration;
        $schema['body_text'] = 'Nota individual: {{certificado.puntaje}} | Mención: {{certificado.mencion}}';
        $schema['elements'] = [[
            'id' => 'individual_grade', 'name' => 'Calificación del estudiante', 'type' => 'text',
            'content' => '{{beneficiario.nombre}} | Nota individual: {{certificado.puntaje}} | Mención: {{certificado.mencion}}',
            'visibility_rule' => 'with_score', 'x' => 10, 'y' => 45, 'width' => 80, 'height' => 10,
            'styles' => ['font_size' => 14],
        ]];
        $template->update(['configuration' => $schema]);
        $activity = Activity::create([
            'name' => 'Evaluación con notas individuales', 'activity_type' => 'Curso',
            'certificate_type_id' => $this->aprobacionType->id, 'status' => 'activa',
            'start_date' => '2026-10-01', 'end_date' => '2026-10-20', 'academic_hours' => 40,
        ]);
        $activity->certificateConfig()->create([
            'certificate_template_id' => $template->id, 'validity_mode' => 'permanent', 'show_score_on_pdf' => true,
        ]);
        $otherActivity = Activity::create([
            'name' => 'Otra evaluación de los mismos estudiantes', 'activity_type' => 'Curso',
            'certificate_type_id' => $this->aprobacionType->id, 'status' => 'activa',
            'start_date' => '2026-09-01', 'end_date' => '2026-09-20', 'academic_hours' => 40,
        ]);
        $students = [];
        foreach ([['Ana', 82.5, 'Buen rendimiento'], ['Bruno', 96.5, 'Sobresaliente'], ['Carla', 0, 'Reprobado'], ['Diego', null, null]] as $index => [$name, $score, $label]) {
            $student = Beneficiary::create(['first_name' => $name, 'last_name' => 'Prueba Notas', 'document_type' => 'CI', 'document_number' => '930000'.$index]);
            $activity->participants()->attach($student->id, ['role' => 'Participante', 'status' => 'inscrito', 'score' => $score, 'score_label' => $label]);
            $otherActivity->participants()->attach($student->id, ['score' => 60, 'score_label' => 'Nota de otra actividad', 'status' => 'inscrito']);
            $students[] = $student;
        }
        $payload = [
            'beneficiary_ids' => array_map(fn (Beneficiary $student): int => $student->id, $students),
            'certificate_type_id' => $this->aprobacionType->id, 'certificate_template_id' => $template->id,
            'issue_date' => '2026-10-29', 'score' => 100, 'score_label' => 'Nota genérica que no debe utilizarse',
        ];

        $this->actingAs($this->user)->post(route($routeName, $activity), $payload)
            ->assertRedirect()->assertSessionHas('success');

        $certificates = $activity->certificates()->with('activity.certificateConfig')->get()->keyBy('beneficiary_id');
        $this->assertCount(2, $certificates);
        foreach ([[0, 82.5, 'Buen rendimiento'], [1, 96.5, 'Sobresaliente']] as [$index, $score, $label]) {
            $certificate = $certificates->get($students[$index]->id);
            $this->assertNotNull($certificate);
            $this->assertSame($score, (float) $certificate->score);
            $this->assertSame($label, $certificate->score_label);
            $this->assertSame($score, (float) $certificate->snapshot_data['score']);
            $this->assertSame($label, $certificate->snapshot_data['score_label']);
            $element = collect($certificate->snapshot_data['design_schema']['elements'])->firstWhere('id', 'individual_grade');
            $this->assertStringContainsString('Nota individual: '.$score, $element['resolved_content']);
            $this->assertStringContainsString('Mención: '.$label, $element['resolved_content']);
            $this->assertStringContainsString('Nota individual: '.$score, $certificate->snapshot_data['resolved_body_text']);
            $html = view('certificates.templates.engine', ['certificate' => $certificate, 'schema' => $certificate->snapshot_data['design_schema']])->render();
            $this->assertStringContainsString('Nota individual: '.$score, $html);
            $this->assertStringContainsString('Mención: '.$label, $html);
            $this->assertStringNotContainsString('Nota genérica que no debe utilizarse', $html);
            $this->assertStringNotContainsString('Nota individual: 60', $html);
            $this->assertStringNotContainsString('Nota individual: '.($index === 0 ? '96.5' : '82.5'), $html);
        }
        $this->assertDatabaseMissing('certificates', ['activity_id' => $activity->id, 'beneficiary_id' => $students[2]->id]);
        $this->assertDatabaseMissing('certificates', ['activity_id' => $activity->id, 'beneficiary_id' => $students[3]->id]);
        $this->assertDatabaseMissing('certificates', ['activity_id' => $otherActivity->id]);

        $snapshot = $certificates->get($students[0]->id)->snapshot_data;
        $activity->participants()->updateExistingPivot($students[0]->id, ['score' => 99, 'score_label' => 'Corrección posterior']);
        $this->post(route($routeName, $activity), $payload)->assertRedirect()->assertSessionHas('warning');
        $this->assertSame(2, $activity->certificates()->count());
        $this->assertSame($snapshot, $certificates->get($students[0]->id)->fresh()->snapshot_data);
    }
}
