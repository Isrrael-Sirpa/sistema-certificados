<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationPortalCustomizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::where('is_active', true)->first();
    }

    public function test_public_verification_portal_renders_with_default_institutional_settings(): void
    {
        $response = $this->get(route('verification.index'));

        $response->assertOk();
        $response->assertSee('Verificación Oficial de Certificados');
        $response->assertSee('Universidad Mayor de San Andrés • Facultad de Tecnología');
        $response->assertSee('Escanear código QR');
        $response->assertSee('EJ: CERT-2026-000001');
    }

    public function test_administrator_can_customize_verification_portal_settings(): void
    {
        $payload = [
            'tab_verification_portal' => '1',
            'portal_verify_title' => 'Portal Universitario de Autenticidad Estudiantil',
            'portal_verify_subtitle' => 'Compruebe la autenticidad académica de diplomas y certificados en tiempo real.',
            'portal_verify_tagline' => 'VICEDECANATO DE TECNOLOGÍA • SISTEMA CENTRAL',
            'portal_verify_code_placeholder' => 'CERT-FT-EJEMPLO-999',
            'portal_verify_show_umsa_logo' => '1',
            'portal_verify_show_ft_logo' => '1',
            'portal_verify_show_qr_card' => '1',
            'portal_verify_qr_card_title' => 'Lector QR Rápido',
            'portal_verify_qr_card_desc' => 'Escanee con su móvil directamente.',
            'portal_verify_show_validity_card' => '1',
            'portal_verify_validity_card_title' => 'Vigencia de 3 Años',
            'portal_verify_validity_card_desc' => 'Normativa facultativa vigente.',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('settings.update'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar persistencia en base de datos
        $this->assertDatabaseHas('settings', [
            'key' => 'portal_verify_title',
            'value' => 'Portal Universitario de Autenticidad Estudiantil',
        ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'portal_verify_code_placeholder',
            'value' => 'CERT-FT-EJEMPLO-999',
        ]);

        // Verificar que el portal público /verificar ahora muestra los nuevos textos
        $portalResponse = $this->get(route('verification.index'));
        $portalResponse->assertOk();
        $portalResponse->assertSee('Portal Universitario de Autenticidad Estudiantil');
        $portalResponse->assertSee('Compruebe la autenticidad académica de diplomas y certificados');
        $portalResponse->assertSee('VICEDECANATO DE TECNOLOGÍA • SISTEMA CENTRAL');
        $portalResponse->assertSee('CERT-FT-EJEMPLO-999');
        $portalResponse->assertSee('Lector QR Rápido');
    }

    public function test_guest_user_cannot_update_settings(): void
    {
        $response = $this->post(route('settings.update'), [
            'portal_verify_title' => 'Intento Hack',
        ]);

        $response->assertRedirect(route('login'));
    }
}
