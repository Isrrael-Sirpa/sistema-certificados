<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\InstitutionalAsset;
use App\Models\Role;
use App\Models\User;
use App\Services\CertificateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateTemplateDesignerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $operatorUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn($q) => $q->where('name', Role::ADMIN))->first();
        $this->operatorUser = User::whereHas('role', fn($q) => $q->where('name', Role::OPERATOR))->first();
    }

    public function test_admin_can_view_templates_catalog(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('templates.index'));
        $response->assertStatus(200);
        $response->assertSee('Catálogo de Plantillas Institucionales');
        $response->assertSee('Institucional Clásica');
    }

    public function test_admin_can_create_new_template_with_initial_version(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('templates.store'), [
            'name' => 'Plantilla Congreso Virtual 2026',
            'category' => 'seminario',
            'orientation' => 'landscape',
            'description' => 'Diseño especial para congreso de tecnología',
            'base_template_slug' => 'seminario-congreso',
        ]);

        $template = CertificateTemplate::where('name', 'Plantilla Congreso Virtual 2026')->first();
        $this->assertNotNull($template);
        $this->assertEquals(1, $template->current_version);
        $this->assertEquals(1, $template->versions()->count());

        $response->assertRedirect(route('templates.designer', $template));
    }

    public function test_admin_can_update_designer_and_increment_version(): void
    {
        $template = CertificateTemplate::first();

        $schema = $template->configuration;
        $schema['primary_color'] = '#123456';
        $schema['award_intro'] = 'Test Intro';

        $response = $this->actingAs($this->adminUser)->put(route('templates.designer.update', $template), [
            'name' => $template->name,
            'category' => $template->category,
            'orientation' => $template->orientation,
            'description' => 'Descripción actualizada',
            'schema' => $schema,
            'create_new_version' => 1,
            'change_summary' => 'Ajuste de color y encabezado',
        ]);

        $response->assertSessionHas('success');

        $template->refresh();
        $this->assertEquals(2, $template->current_version);
        $this->assertCount(2, $template->versions);
        $this->assertEquals('#123456', $template->configuration['primary_color']);
    }

    public function test_admin_can_duplicate_template(): void
    {
        $template = CertificateTemplate::first();

        $response = $this->actingAs($this->adminUser)->post(route('templates.duplicate', $template), [
            'name' => 'Plantilla Duplicada Especial',
        ]);

        $duplicated = CertificateTemplate::where('name', 'Plantilla Duplicada Especial')->first();
        $this->assertNotNull($duplicated);
        $this->assertEquals(1, $duplicated->current_version);
        $this->assertEquals($template->configuration['primary_color'], $duplicated->configuration['primary_color']);

        $response->assertRedirect(route('templates.designer', $duplicated));
    }

    public function test_cannot_delete_template_with_issued_certificates(): void
    {
        $template = CertificateTemplate::first();
        $beneficiary = Beneficiary::first();
        $activity = Activity::first();
        $type = CertificateType::first();

        $service = app(CertificateService::class);
        $service->issueCertificate([
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $type->id,
            'certificate_template_id' => $template->id,
        ], $this->adminUser);

        $response = $this->actingAs($this->adminUser)->delete(route('templates.destroy', $template));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('certificate_templates', ['id' => $template->id]);
    }

    public function test_activity_certificate_config_can_be_saved(): void
    {
        $activity = Activity::first();
        $template = CertificateTemplate::first();

        $response = $this->actingAs($this->adminUser)->post(route('activities.certificate-config', $activity), [
            'certificate_template_id' => $template->id,
            'custom_title' => 'DIPLOMA DE HONOR',
            'custom_body_text' => 'Texto personalizado para {{beneficiario.nombre}} en {{actividad.nombre}}',
            'primary_color' => '#2563eb',
            'resolution_number' => 'RF-99/2026',
            'modality' => 'Presencial',
        ]);

        $response->assertSessionHas('success');

        $activity->refresh();
        $this->assertNotNull($activity->certificateConfig);
        $this->assertEquals('DIPLOMA DE HONOR', $activity->certificateConfig->custom_title);
        $this->assertEquals('#2563eb', $activity->certificateConfig->primary_color);
        $this->assertEquals('RF-99/2026', $activity->certificateConfig->custom_fields_schema['resolucion']);
    }

    public function test_draft_preview_renders_pdf_stream(): void
    {
        $beneficiary = Beneficiary::first();
        $activity = Activity::first();
        $type = CertificateType::first();
        $template = CertificateTemplate::first();

        $response = $this->actingAs($this->adminUser)->post(route('certificates.preview-draft'), [
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $type->id,
            'certificate_template_id' => $template->id,
            'issue_date' => now()->toDateString(),
            'special_note' => 'Nota de prueba',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_institutional_assets_catalog_can_be_viewed_and_filtered(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('assets.index', ['type' => 'logo']));
        $response->assertStatus(200);
        $response->assertSee('Biblioteca de Identidad Universitaria');
        $response->assertSee('Escudo Oficial UMSA');
    }

    public function test_draft_preview_with_custom_logos_and_watermark_renders_pdf(): void
    {
        $beneficiary = Beneficiary::first();
        $activity = Activity::first();
        $type = CertificateType::first();
        $template = CertificateTemplate::first();
        $logo = InstitutionalAsset::where('type', InstitutionalAsset::TYPE_LOGO)->first();
        $watermark = InstitutionalAsset::where('type', InstitutionalAsset::TYPE_WATERMARK)->first();

        $response = $this->actingAs($this->adminUser)->post(route('certificates.preview-draft'), [
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $type->id,
            'certificate_template_id' => $template->id,
            'issue_date' => now()->toDateString(),
            'logo_left_id' => $logo?->id,
            'logo_right_id' => $logo?->id,
            'watermark_asset_id' => $watermark?->id,
            'special_note' => 'Acreditación con honores',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_designer_loads_with_element_studio_and_assets(): void
    {
        $template = CertificateTemplate::first();

        $response = $this->actingAs($this->adminUser)->get(route('templates.designer', $template));
        $response->assertStatus(200);
        $response->assertSee('Diseñador Visual de Certificados');
        $response->assertSee('Lienzo A4');
        $response->assertSee('Biblioteca');
        $response->assertSee('Capas');
        $response->assertSee('Paleta Oficial de Colores');
    }

    public function test_admin_can_create_blank_canvas_template(): void
    {
        $response = $this->actingAs($this->adminUser)->post(route('templates.store'), [
            'name' => 'Plantilla Lienzo Limpio 2026',
            'category' => 'academica',
            'orientation' => 'landscape',
            'creation_mode' => 'blank',
        ]);

        $template = CertificateTemplate::where('name', 'Plantilla Lienzo Limpio 2026')->first();
        $this->assertNotNull($template);
        $this->assertEquals(1, $template->current_version);
        $this->assertIsArray($template->configuration);
        $this->assertArrayHasKey('elements', $template->configuration);
        $this->assertEmpty($template->configuration['elements']);

        $response->assertRedirect(route('templates.designer', $template));
    }

    public function test_admin_can_create_template_by_duplicating_existing(): void
    {
        $source = CertificateTemplate::first();

        $response = $this->actingAs($this->adminUser)->post(route('templates.store'), [
            'name' => 'Plantilla Clonada del Sistema',
            'category' => 'capacitacion',
            'orientation' => 'landscape',
            'creation_mode' => 'duplicate',
            'source_template_id' => $source->id,
        ]);

        $template = CertificateTemplate::where('name', 'Plantilla Clonada del Sistema')->first();
        $this->assertNotNull($template);
        $this->assertEquals(1, $template->current_version);
        $this->assertIsArray($template->configuration);
        $this->assertEquals($source->configuration['primary_color'], $template->configuration['primary_color']);

        $response->assertRedirect(route('templates.designer', $template));
    }

    public function test_admin_can_save_designer_using_schema_json(): void
    {
        $template = CertificateTemplate::first();

        $schemaPayload = [
            'primary_color' => '#003366',
            'secondary_color' => '#d4af37',
            'border_style' => 'ornamental-double',
            'font_family' => 'Helvetica',
            'elements' => [
                [
                    'id' => 'el_test_title',
                    'type' => 'header_title',
                    'name' => 'TÍTULO TEST',
                    'x' => 10,
                    'y' => 12,
                    'width' => 80,
                    'height' => 8,
                    'z_index' => 20,
                    'opacity' => 1,
                    'is_visible' => true,
                    'is_locked' => false,
                    'is_mandatory' => false,
                    'content' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS TEST',
                    'styles' => [
                        'font_size' => 16,
                        'font_weight' => 'bold',
                        'text_align' => 'center',
                    ],
                ],
                [
                    'id' => 'el_test_qr',
                    'type' => 'qr_code',
                    'name' => 'QR TEST',
                    'x' => 85,
                    'y' => 75,
                    'width' => 10,
                    'height' => 15,
                    'z_index' => 25,
                    'opacity' => 1,
                    'is_visible' => true,
                    'is_locked' => false,
                    'is_mandatory' => false,
                ],
            ],
        ];

        $response = $this->actingAs($this->adminUser)->put(route('templates.designer.update', $template), [
            'name' => $template->name,
            'category' => $template->category,
            'orientation' => $template->orientation,
            'schema_json' => json_encode($schemaPayload),
            'create_new_version' => 1,
        ]);

        $response->assertSessionHas('success');

        $template->refresh();
        $this->assertEquals(2, $template->current_version);
        $this->assertEquals('#003366', $template->configuration['primary_color']);
        $this->assertCount(2, $template->configuration['elements']);
        $this->assertEquals('el_test_title', $template->configuration['elements'][0]['id']);
    }

    public function test_asset_manager_resolves_base64_data_uris(): void
    {
        $assetManager = app(\App\Services\AssetManagerService::class);
        $asset = InstitutionalAsset::first();

        $this->assertNotNull($asset);
        $base64 = $assetManager->getAssetBase64($asset->id);
        $this->assertNotNull($base64);
        $this->assertStringStartsWith('data:image/', $base64);
    }
}
