<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\Role;
use App\Models\User;
use App\Services\CertificatePdfService;
use App\Services\CertificateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_certificate_can_be_issued_with_3_year_validity_and_unique_token(): void
    {
        $user = User::first();
        $beneficiary = Beneficiary::first();
        $activity = Activity::first();
        $type = CertificateType::first();
        $template = CertificateTemplate::first();

        $service = app(CertificateService::class);
        $certificate = $service->issueCertificate([
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $type->id,
            'certificate_template_id' => $template->id,
            'issue_date' => '2026-09-15',
        ], $user);

        $this->assertNotNull($certificate->id);
        $this->assertStringStartsWith('CERT-2026-', $certificate->certificate_code);
        $this->assertNotEmpty($certificate->verification_token);
        $this->assertEquals('2026-09-15', $certificate->issue_date->format('Y-m-d'));
        
        // Comprobar regla de 3 años de vigencia exacta
        $this->assertEquals('2029-09-15', $certificate->expiration_date->format('Y-m-d'));
        $this->assertEquals(Certificate::STATUS_VIGENTE, $certificate->calculated_status);
        $this->assertTrue($certificate->isValid());
        $this->assertFalse($certificate->isExpired());
        $this->assertFalse($certificate->isRevoked());
    }

    public function test_certificate_pdf_can_be_rendered(): void
    {
        $user = User::first();
        $beneficiary = Beneficiary::first();
        $activity = Activity::first();
        $type = CertificateType::first();

        $service = app(CertificateService::class);
        $certificate = $service->issueCertificate([
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $type->id,
        ], $user);

        $pdfService = app(CertificatePdfService::class);
        $pdf = $pdfService->render($certificate);

        $this->assertNotEmpty($pdf->output());
    }

    public function test_certificate_revocation_updates_status(): void
    {
        $user = User::first();
        $beneficiary = Beneficiary::first();
        $activity = Activity::first();
        $type = CertificateType::first();

        $service = app(CertificateService::class);
        $certificate = $service->issueCertificate([
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $type->id,
        ], $user);

        $service->revokeCertificate($certificate, $user, 'Error en el nombre del beneficiario');

        $this->assertEquals(Certificate::STATUS_REVOCADO, $certificate->calculated_status);
        $this->assertTrue($certificate->isRevoked());
        $this->assertFalse($certificate->isValid());
    }

    public function test_issued_certificate_remains_immutable_when_template_changes(): void
    {
        $user = User::first();
        $beneficiary = Beneficiary::first();
        $activity = Activity::first();
        $type = CertificateType::first();
        $template = CertificateTemplate::first();

        $originalPrimaryColor = $template->configuration['primary_color'];

        $service = app(CertificateService::class);
        $certificate = $service->issueCertificate([
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $type->id,
            'certificate_template_id' => $template->id,
        ], $user);

        // Snapshot congelado debe existir con el color original
        $this->assertNotNull($certificate->snapshot_data);
        $this->assertEquals($originalPrimaryColor, $certificate->snapshot_data['design_schema']['primary_color']);

        // Ahora simulamos que un administrador modifica la plantilla en el futuro (v2)
        $newConfig = $template->configuration;
        $newConfig['primary_color'] = '#ff0000'; // Cambio drástico a rojo
        $template->update([
            'configuration' => $newConfig,
            'current_version' => 2,
        ]);

        // Recargar certificado emitido
        $certificate->refresh();

        // El certificado emitido DEBE mantener su diseño original inmutable
        $this->assertEquals($originalPrimaryColor, $certificate->snapshot_data['design_schema']['primary_color']);
        $this->assertNotEquals('#ff0000', $certificate->snapshot_data['design_schema']['primary_color']);
    }
}
