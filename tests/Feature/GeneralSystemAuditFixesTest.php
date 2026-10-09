<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\InstitutionalAsset;
use App\Models\Role;
use App\Models\User;
use App\Services\CertificatePdfService;
use App\Services\CertificateService;
use App\Services\CertificateSnapshotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class GeneralSystemAuditFixesTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $operatorUser;
    protected CertificateTemplate $defaultTemplate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn($q) => $q->where('name', Role::ADMIN))->first();
        $this->operatorUser = User::whereHas('role', fn($q) => $q->where('name', Role::OPERATOR))->first();
        $this->defaultTemplate = CertificateTemplate::first();
    }

    public function test_live_preview_renders_preview_canvas_successfully(): void
    {
        $schema = [
            'font_family' => 'Helvetica',
            'primary_color' => '#0f2b5c',
            'secondary_color' => '#d4af37',
            'elements' => [
                [
                    'id' => 'el_text_1',
                    'type' => 'text',
                    'content' => 'Certificado de Aprobación para {{beneficiario.nombre}}',
                    'x' => 10,
                    'y' => 30,
                    'width' => 80,
                    'height' => 15,
                    'font_size' => 18,
                    'font_weight' => 'bold',
                    'text_align' => 'center',
                    'is_visible' => true,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->post(route('templates.live-preview', $this->defaultTemplate), [
            'schema' => json_encode($schema),
            'orientation' => 'landscape',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('certificates.templates.preview_canvas');
        $response->assertSee('Simulación en Vivo');
        $response->assertSee('Univ. Rodrigo Quispe Mamani');
    }

    public function test_template_preview_pdf_generates_stream_without_errors(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('templates.preview', $this->defaultTemplate));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_template_show_and_edit_routes_redirect_to_designer(): void
    {
        $showResponse = $this->actingAs($this->adminUser)->get(route('templates.show', $this->defaultTemplate));
        $showResponse->assertRedirect(route('templates.designer', $this->defaultTemplate));

        $editResponse = $this->actingAs($this->adminUser)->get(route('templates.edit', $this->defaultTemplate));
        $editResponse->assertRedirect(route('templates.designer', $this->defaultTemplate));
    }

    public function test_user_show_redirects_to_edit(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('users.show', $this->operatorUser));
        $response->assertRedirect(route('users.edit', $this->operatorUser));
    }

    public function test_user_cannot_delete_themselves(): void
    {
        $response = $this->actingAs($this->adminUser)->delete(route('users.destroy', $this->adminUser));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error', 'No puede eliminar su propia cuenta de usuario.');
        $this->assertDatabaseHas('users', ['id' => $this->adminUser->id]);
    }

    public function test_user_with_historical_certificates_is_deactivated_instead_of_deleted(): void
    {
        $operatorRole = Role::where('name', Role::OPERATOR)->first();
        $testUser = User::create([
            'name' => 'Operador Histórico',
            'email' => 'operador.historico@umsa.bo',
            'password' => Hash::make('Secret123!'),
            'role_id' => $operatorRole->id,
            'is_active' => true,
        ]);

        $beneficiary = Beneficiary::first();
        $activity = Activity::first();
        $certType = CertificateType::first();

        // Crear certificado emitido por este usuario
        Certificate::create([
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $certType->id,
            'certificate_template_id' => $this->defaultTemplate->id,
            'generated_by_user_id' => $testUser->id,
            'issue_date' => now()->toDateString(),
            'status' => Certificate::STATUS_VIGENTE,
        ]);

        $response = $this->actingAs($this->adminUser)->delete(route('users.destroy', $testUser));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('warning');

        // El usuario debe continuar existiendo pero marcado como inactivo
        $testUser->refresh();
        $this->assertFalse($testUser->is_active);
        $this->assertDatabaseHas('users', ['id' => $testUser->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_deactivated_historic',
            'entity_id' => $testUser->id,
        ]);
    }

    public function test_user_without_historical_records_can_be_deleted(): void
    {
        $operatorRole = Role::where('name', Role::OPERATOR)->first();
        $testUser = User::create([
            'name' => 'Operador Temporal',
            'email' => 'operador.temp@umsa.bo',
            'password' => Hash::make('Secret123!'),
            'role_id' => $operatorRole->id,
            'is_active' => true,
        ]);

        $userId = $testUser->id;
        $response = $this->actingAs($this->adminUser)->delete(route('users.destroy', $testUser));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success', "Usuario 'Operador Temporal' eliminado correctamente.");
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_immutable_snapshot_freezes_element_assets_and_resolved_text(): void
    {
        $beneficiary = Beneficiary::first();
        $activity = Activity::first();
        $certType = CertificateType::first();

        // Configurar plantilla con elemento dinámico de texto
        $this->defaultTemplate->configuration = [
            'theme' => 'studio',
            'orientation' => 'landscape',
            'elements' => [
                [
                    'id' => 'el_title',
                    'type' => 'text',
                    'content' => 'Otorga reconocimiento a: {{beneficiario.nombre}} por {{actividad.horas}} horas',
                    'x' => 10,
                    'y' => 20,
                    'width' => 80,
                    'height' => 10,
                    'font_size' => 14,
                    'is_visible' => true,
                ],
                [
                    'id' => 'el_logo',
                    'type' => 'logo',
                    'asset_path' => 'assets/institutional/logo_umsa.svg',
                    'x' => 5,
                    'y' => 5,
                    'width' => 15,
                    'height' => 15,
                    'is_visible' => true,
                ],
            ],
        ];
        $this->defaultTemplate->save();

        $certService = app(CertificateService::class);
        $certificate = $certService->issueCertificate([
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $certType->id,
            'certificate_template_id' => $this->defaultTemplate->id,
            'issue_date' => now()->toDateString(),
        ], $this->adminUser);

        // Verificar que el snapshot congelado contiene el texto resuelto y asset en Base64
        $snapshot = $certificate->snapshot_data;
        $this->assertNotEmpty($snapshot);
        $this->assertArrayHasKey('design_schema', $snapshot);
        $elements = $snapshot['design_schema']['elements'];

        $textElement = collect($elements)->firstWhere('id', 'el_title');
        $this->assertNotNull($textElement);
        $this->assertArrayHasKey('resolved_content', $textElement);
        $this->assertStringContainsString($beneficiary->full_title_name, $textElement['resolved_content']);

        $logoElement = collect($elements)->firstWhere('id', 'el_logo');
        $this->assertNotNull($logoElement);
        $this->assertArrayHasKey('asset_base64', $logoElement);
        $this->assertNotEmpty($logoElement['asset_base64']);

        // Verificar que el PDF congelado se renderiza sin errores
        $pdfService = app(CertificatePdfService::class);
        $pdf = $pdfService->render($certificate);
        $this->assertNotNull($pdf);
    }

    public function test_assets_catalog_page_renders_cleanly(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('assets.index'));
        $response->assertStatus(200);
        $response->assertSee('Biblioteca de Identidad Universitaria');
    }
}
