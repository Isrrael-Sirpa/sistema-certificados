<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ActivityRosterParticipantEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Activity $activity;

    protected CertificateType $certType;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->user = User::where('is_active', true)->first();
        $this->activity = Activity::first() ?? Activity::create([
            'name' => 'Congreso Nacional de Robótica 2026',
            'activity_type' => 'Congreso',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'academic_hours' => 30,
            'status' => 'activa',
        ]);
        $this->certType = CertificateType::first();
    }

    public function test_can_enroll_participants_from_excel_csv_file_into_activity_roster(): void
    {
        $existingBen = Beneficiary::first();
        $newDoc = '11223344 LP';

        $csv = implode("\n", [
            'CI,NOMBRES,APELLIDOS,CORREO,CARRERA',
            "{$existingBen->document_number},{$existingBen->first_name},{$existingBen->last_name},{$existingBen->email},Ingeniería",
            "{$newDoc},Marcos Antonio,Quisbert Mamani,marcos.quisbert@umsa.bo,Informática",
        ]);

        $file = UploadedFile::fake()->createWithContent('nomina_previa.csv', $csv);

        $response = $this->actingAs($this->user)
            ->post(route('activities.roster.enroll', $this->activity), [
                'file' => $file,
            ]);

        $response->assertRedirect(route('activities.show', $this->activity));
        $response->assertSessionHas('success');

        // Verificar que el nuevo estudiante fue creado en Beneficiary
        $newBen = Beneficiary::where('document_number', $newDoc)->first();
        $this->assertNotNull($newBen);
        $this->assertEquals('Marcos Antonio', $newBen->first_name);

        // Verificar que ambos están inscritos en la nómina de la actividad con status 'inscrito'
        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $this->activity->id,
            'beneficiary_id' => $existingBen->id,
            'status' => 'inscrito',
        ]);

        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $this->activity->id,
            'beneficiary_id' => $newBen->id,
            'status' => 'inscrito',
        ]);

        // Asegurar que NO se emitieron certificados aún
        $this->assertEquals(0, $this->activity->certificates()->count());
    }

    public function test_can_add_participant_manually_from_padron(): void
    {
        $ben = Beneficiary::first();

        $response = $this->actingAs($this->user)
            ->post(route('activities.participants.add-manual', $this->activity), [
                'beneficiary_id' => $ben->id,
                'role' => 'Expositor',
            ]);

        $response->assertRedirect(route('activities.show', $this->activity));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $this->activity->id,
            'beneficiary_id' => $ben->id,
            'role' => 'Expositor',
            'status' => 'inscrito',
        ]);

        // Si intentamos agregarlo de nuevo, redirige con info o warning sin duplicar
        $dupResponse = $this->actingAs($this->user)
            ->post(route('activities.participants.add-manual', $this->activity), [
                'beneficiary_id' => $ben->id,
            ]);

        $dupResponse->assertRedirect(route('activities.show', $this->activity));
        $this->assertEquals(1, $this->activity->participants()->where('beneficiary_id', $ben->id)->count());
    }

    public function test_can_remove_participant_from_activity_roster(): void
    {
        $ben = Beneficiary::first();
        $this->activity->participants()->attach($ben->id, [
            'role' => 'Participante',
            'status' => 'inscrito',
        ]);

        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $this->activity->id,
            'beneficiary_id' => $ben->id,
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('activities.participants.remove', [$this->activity, $ben]));

        $response->assertRedirect(route('activities.show', $this->activity));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('activity_participants', [
            'activity_id' => $this->activity->id,
            'beneficiary_id' => $ben->id,
        ]);
    }

    public function test_can_issue_certificates_to_enrolled_participants(): void
    {
        $ben1 = Beneficiary::first();
        $ben2 = Beneficiary::skip(1)->first();

        $this->activity->participants()->attach($ben1->id, [
            'role' => 'Participante',
            'status' => 'inscrito',
        ]);
        $this->activity->participants()->attach($ben2->id, [
            'role' => 'Expositor',
            'status' => 'inscrito',
        ]);

        $template = CertificateTemplate::first();

        $response = $this->actingAs($this->user)
            ->post(route('activities.participants.issue-certificates', $this->activity), [
                'certificate_type_id' => $this->certType->id,
                'issue_date' => now()->toDateString(),
                'certificate_template_id' => $template?->id,
            ]);

        $response->assertRedirect(route('activities.show', $this->activity));
        $response->assertSessionHas('success');

        // Ambos deben tener ahora certificado emitido y status 'certificado'
        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $this->activity->id,
            'beneficiary_id' => $ben1->id,
            'status' => 'certificado',
        ]);

        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $this->activity->id,
            'beneficiary_id' => $ben2->id,
            'status' => 'certificado',
        ]);

        // Certificados generados en certificates table
        $this->assertEquals(2, $this->activity->certificates()->count());

        $this->assertDatabaseHas('certificates', [
            'activity_id' => $this->activity->id,
            'beneficiary_id' => $ben1->id,
            'status' => Certificate::STATUS_VIGENTE,
        ]);

        $this->assertDatabaseHas('certificates', [
            'activity_id' => $this->activity->id,
            'beneficiary_id' => $ben2->id,
            'status' => Certificate::STATUS_VIGENTE,
        ]);
    }

    public function test_can_save_visual_template_and_activity_customizations(): void
    {
        $template = CertificateTemplate::first();

        $response = $this->actingAs($this->user)
            ->post(route('activities.certificate-config', $this->activity), [
                'certificate_template_id' => $template?->id,
                'custom_title' => 'DIPLOMA DE EXCELENCIA ACADÉMICA',
                'modality' => 'Virtual',
                'resolution_number' => 'Resolución HCF N° 890/2026',
            ]);

        $response->assertRedirect(route('activities.show', $this->activity));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_certificate_configs', [
            'activity_id' => $this->activity->id,
            'certificate_template_id' => $template?->id,
            'custom_title' => 'DIPLOMA DE EXCELENCIA ACADÉMICA',
        ]);

        $this->activity->refresh();
        $config = $this->activity->certificateConfig;
        $this->assertEquals('Virtual', $config->custom_fields_schema['modalidad']);
        $this->assertEquals('Resolución HCF N° 890/2026', $config->custom_fields_schema['resolucion']);
    }
}
