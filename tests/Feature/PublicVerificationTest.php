<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\User;
use App\Services\CertificateService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_public_search_screen_can_be_rendered(): void
    {
        $response = $this->get('/verificar');
        $response->assertStatus(200);
        $response->assertSee('Verificación de Certificados Académicos');
    }

    public function test_public_qr_verification_shows_valid_certificate(): void
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
            'issue_date' => now()->toDateString(),
        ], $user);

        $response = $this->get('/verificar/' . $certificate->verification_token);
        $response->assertStatus(200);
        $response->assertSee('Certificado Oficial Válido');
        $response->assertSee('VIGENTE');
        $response->assertSee($certificate->certificate_code);
        $response->assertSee($beneficiary->full_name);
    }

    public function test_public_qr_verification_shows_expired_when_past_3_years(): void
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
            'issue_date' => '2020-01-01',
        ], $user);

        $response = $this->get('/verificar/' . $certificate->verification_token);
        $response->assertStatus(200);
        $response->assertSee('Certificado Expirado');
        $response->assertSee('EXPIRADO');
    }

    public function test_public_qr_verification_shows_revoked_status(): void
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

        $service->revokeCertificate($certificate, $user, 'Certificado duplicado por error');

        $response = $this->get('/verificar/' . $certificate->verification_token);
        $response->assertStatus(200);
        $response->assertSee('Certificado Revocado / Anulado');
        $response->assertSee('REVOCADO');
        $response->assertSee('Certificado duplicado por error');
    }

    public function test_manual_code_search_redirects_to_verification(): void
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

        $response = $this->post('/verificar/buscar', [
            'certificate_code' => $certificate->certificate_code,
        ]);

        $response->assertRedirect('/verificar/' . $certificate->verification_token . '?method=manual_code');
    }

    public function test_non_existent_token_returns_404_view(): void
    {
        $response = $this->get('/verificar/00000000-0000-0000-0000-000000000000');
        $response->assertStatus(404);
        $response->assertSee('Certificado No Encontrado');
    }
}
