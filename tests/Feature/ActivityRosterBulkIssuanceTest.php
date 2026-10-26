<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ActivityRosterBulkIssuanceTest extends TestCase
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
            'name' => 'Seminario de Inteligencia Artificial 2026',
            'activity_type' => 'Seminario',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'academic_hours' => 20,
            'status' => 'activa',
        ]);
        $this->certType = CertificateType::first();
    }

    public function test_activity_roster_preview_identifies_new_existing_and_already_certified_participants(): void
    {
        // 1. Beneficiario que YA tiene certificado en esta actividad
        $existingCertified = Beneficiary::first();
        app(CertificateService::class)->issueCertificate([
            'beneficiary_id' => $existingCertified->id,
            'activity_id' => $this->activity->id,
            'certificate_type_id' => $this->certType->id,
            'issue_date' => now()->toDateString(),
        ], $this->user);

        // 2. Beneficiario que existe en el padrón, pero AÚN NO tiene certificado en esta actividad
        $existingNotCertified = Beneficiary::skip(1)->first();

        // 3. Estudiante completamente NUEVO que no existe en la base de datos
        $newStudentCi = '9876543 LP';

        $csvContent = implode("\n", [
            'CI,NOMBRE_COMPLETO,CARRERA,CORREO',
            "{$existingCertified->document_number},{$existingCertified->full_name},Informática,{$existingCertified->email}",
            "{$existingNotCertified->document_number},{$existingNotCertified->full_name},Química,{$existingNotCertified->email}",
            "{$newStudentCi},Gabriel Alejandro Mendoza Flores,Informática,gabriel.mendoza@umsa.bo",
        ]);

        $file = UploadedFile::fake()->createWithContent('nomina_participantes.csv', $csvContent);

        $response = $this->actingAs($this->user)
            ->postJson(route('activities.roster.preview', $this->activity), [
                'file' => $file,
                'certificate_type_id' => $this->certType->id,
            ]);

        $response->assertOk();
        $data = $response->json();

        $this->assertEquals(3, $data['summary']['total']);
        $this->assertEquals(1, $data['summary']['new_students'], 'Debe detectar exactamente 1 estudiante nuevo');
        $this->assertEquals(1, $data['summary']['existing_students'], 'Debe detectar exactamente 1 estudiante existente');
        $this->assertEquals(1, $data['summary']['already_certified'], 'Debe detectar exactamente 1 estudiante ya certificado');
        $this->assertEquals(2, $data['summary']['ready_to_certify'], 'Deben haber 2 listos para certificar');
    }

    public function test_activity_roster_confirm_creates_new_beneficiary_and_issues_all_certificates(): void
    {
        $existingNotCertified = Beneficiary::first();
        $newStudentCi = '8765432 LP';

        $csvContent = implode("\n", [
            'CI,NOMBRES,APELLIDOS,CARRERA,CORREO',
            "{$existingNotCertified->document_number},{$existingNotCertified->first_name},{$existingNotCertified->last_name},Informática,test@umsa.bo",
            "{$newStudentCi},Carla Sofia,Mamani Condori,Ingeniería Civil,carla.mamani@umsa.bo",
        ]);

        $file = UploadedFile::fake()->createWithContent('nomina.csv', $csvContent);

        // Primero previsualizar
        $previewRes = $this->actingAs($this->user)
            ->postJson(route('activities.roster.preview', $this->activity), [
                'file' => $file,
                'certificate_type_id' => $this->certType->id,
            ]);

        $previewData = $previewRes->json();
        $this->assertTrue($previewData['can_import']);

        $initialCertCount = $this->activity->certificates()->count();

        // Confirmar emisión masiva
        $confirmRes = $this->actingAs($this->user)
            ->postJson(route('activities.roster.confirm', $this->activity), [
                'certificate_type_id' => $this->certType->id,
                'issue_date' => now()->toDateString(),
                'rows' => $previewData['rows'],
            ]);

        $confirmRes->assertOk();
        $confirmRes->assertJsonFragment(['success' => true]);

        // Verificar que el nuevo estudiante se creó en el padrón
        $newBen = Beneficiary::where('document_number', $newStudentCi)->first();
        $this->assertNotNull($newBen, 'El nuevo estudiante debió registrarse en beneficiaries');
        $this->assertEquals('Carla Sofia', $newBen->first_name);
        $this->assertEquals('Mamani Condori', $newBen->last_name);

        // Verificar que se emitieron 2 nuevos certificados
        $this->assertEquals($initialCertCount + 2, $this->activity->certificates()->count());

        // Verificar certificado del nuevo beneficiario
        $newCert = Certificate::where('beneficiary_id', $newBen->id)
            ->where('activity_id', $this->activity->id)
            ->first();
        $this->assertNotNull($newCert);
        $this->assertEquals(Certificate::STATUS_VIGENTE, $newCert->status);

        // Verificar log de auditoría
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'activity_bulk_certified',
        ]);
    }

    public function test_can_download_all_activity_certificates_as_zip(): void
    {
        // Emitir un certificado para la actividad
        $beneficiary = Beneficiary::first();
        app(CertificateService::class)->issueCertificate([
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $this->activity->id,
            'certificate_type_id' => $this->certType->id,
            'issue_date' => now()->toDateString(),
        ], $this->user);

        $response = $this->actingAs($this->user)
            ->get(route('activities.download-zip', $this->activity));

        $response->assertOk();
        $this->assertEquals('application/zip', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('.zip', $response->headers->get('Content-Disposition'));
    }
}
