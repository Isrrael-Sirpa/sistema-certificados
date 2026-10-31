<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityCertificateConfig;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\InstitutionalAsset;
use App\Models\Role;
use App\Models\User;
use App\Services\AssetManagerService;
use App\Services\CertificatePdfService;
use App\Services\CertificateService;
use Barryvdh\DomPDF\PDF;
use Illuminate\Database\Eloquent\Collection;
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

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))->first();
        $this->operatorUser = User::whereHas('role', fn ($q) => $q->where('name', Role::OPERATOR))->first();
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
        $response->assertSee('Colores del documento');
        $response->assertSee('Datos de muestra del certificado');
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
        $assetManager = app(AssetManagerService::class);
        $asset = InstitutionalAsset::first();

        $this->assertNotNull($asset);
        $base64 = $assetManager->getAssetBase64($asset->id);
        $this->assertNotNull($base64);
        $this->assertStringStartsWith('data:image/', $base64);
    }

    public function test_designer_uses_registered_active_types_and_their_score_policies(): void
    {
        $type = CertificateType::create([
            'code' => 'calificacion-especial', 'name' => 'Certificado de Calificación Especial',
            'is_active' => true, 'has_score' => true, 'min_passing_score' => 95,
            'score_scale' => 'Sobre 100 puntos', 'validity_mode' => 'years', 'default_validity_years' => 5,
        ]);
        $inactive = CertificateType::create(['code' => 'archivado', 'name' => 'Tipo archivado', 'is_active' => false]);

        $this->actingAs($this->adminUser)->get(route('templates.designer', CertificateTemplate::first()))
            ->assertOk()
            ->assertViewHas('certificateTypes', function (Collection $types) use ($type, $inactive): bool {
                return $types->contains('id', $type->id)
                    && ! $types->contains('id', $inactive->id)
                    && $types->firstWhere('id', $type->id)->min_passing_score === 95.0;
            });
    }

    public function test_pdf_preview_uses_the_selected_type_score_zero_and_sample_dates_without_creating_a_certificate(): void
    {
        $type = CertificateType::where('code', 'aprobacion')->firstOrFail();
        $type->update(['has_score' => true, 'validity_mode' => 'years', 'default_validity_years' => 5]);
        $certificateCount = Certificate::count();
        $pdf = \Mockery::mock(PDF::class);
        $pdf->shouldReceive('stream')->once()->andReturn(response('PDF de muestra', 200, ['Content-Type' => 'application/pdf']));
        $this->mock(CertificatePdfService::class)->shouldReceive('render')->once()
            ->with(\Mockery::on(function (Certificate $preview) use ($type): bool {
                return ! $preview->exists && ! $preview->beneficiary->exists
                    && $preview->certificateType->id === $type->id
                    && (float) $preview->score === 0.0
                    && $preview->score_label === 'Requiere refuerzo'
                    && $preview->issue_date->toDateString() === '2026-10-29'
                    && $preview->expiration_date->toDateString() === '2031-10-29';
            }))->andReturn($pdf);

        $this->actingAs($this->adminUser)->get(route('templates.preview', [
            'template' => CertificateTemplate::first(), 'certificate_type_id' => $type->id,
            'score' => '0', 'score_label' => 'Requiere refuerzo', 'issue_date' => '2026-10-29', 'validity_type' => 'years',
        ]))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertDatabaseCount('certificates', $certificateCount);
    }

    public function test_pdf_preview_clears_notes_and_expiration_for_an_unscored_permanent_type(): void
    {
        $type = CertificateType::where('code', 'reconocimiento')->firstOrFail();
        $type->update(['has_score' => false, 'validity_mode' => 'permanent']);
        $pdf = \Mockery::mock(PDF::class);
        $pdf->shouldReceive('stream')->once()->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));
        $this->mock(CertificatePdfService::class)->shouldReceive('render')->once()
            ->with(\Mockery::on(fn (Certificate $preview): bool => $preview->certificateType->id === $type->id
                && $preview->score === null && $preview->score_label === null && $preview->isPermanent()
                && $preview->expiration_date === null))->andReturn($pdf);

        $this->actingAs($this->adminUser)->get(route('templates.preview', [
            'template' => CertificateTemplate::first(), 'certificate_type_id' => $type->id,
            'score' => 88, 'score_label' => 'Dato que no debe aparecer', 'issue_date' => '2026-10-29',
        ]))->assertOk();
    }

    public function test_pdf_preview_requires_a_valid_score_and_a_date_for_custom_expiration(): void
    {
        $this->mock(CertificatePdfService::class)->shouldNotReceive('render');
        $template = CertificateTemplate::first();
        $this->actingAs($this->adminUser)->get(route('templates.preview', ['template' => $template, 'score' => 101]))
            ->assertSessionHasErrors('score');
        $this->get(route('templates.preview', ['template' => $template, 'validity_type' => 'custom', 'issue_date' => '2026-10-29']))
            ->assertSessionHasErrors('expiration_date');
        $this->get(route('templates.preview', ['template' => $template, 'validity_type' => 'custom', 'issue_date' => '2026-10-29', 'expiration_date' => '2026-10-28']))
            ->assertSessionHasErrors('expiration_date');
    }

    public function test_saving_academic_blocks_preserves_visibility_rules_and_issued_snapshots(): void
    {
        $template = CertificateTemplate::first();
        $issued = app(CertificateService::class)->issueCertificate([
            'beneficiary_id' => Beneficiary::first()->id,
            'activity_id' => Activity::first()->id,
            'certificate_type_id' => CertificateType::first()->id,
            'certificate_template_id' => $template->id,
        ], $this->adminUser);
        $snapshot = $issued->snapshot_data;
        $this->assertArrayHasKey('design_schema', $snapshot);
        $schema = $template->configuration;
        $schema['elements'] = [[
            'id' => 'grade', 'type' => 'text', 'content' => 'Calificación: {{certificado.puntaje}} puntos',
            'data_field' => 'qualification', 'visibility_rule' => 'with_score',
            'x' => 12, 'y' => 58, 'width' => 76, 'height' => 4, 'styles' => ['font_size' => 14],
        ]];
        $this->actingAs($this->adminUser)->put(route('templates.designer.update', $template), [
            'name' => $template->name, 'category' => $template->category, 'orientation' => $template->orientation,
            'schema_json' => json_encode($schema), 'create_new_version' => false,
        ])->assertSessionHas('success');
        $this->assertSame('with_score', $template->fresh()->configuration['elements'][0]['visibility_rule']);
        $this->assertSame('qualification', $template->fresh()->configuration['elements'][0]['data_field']);
        $this->assertSame($snapshot, $issued->fresh()->snapshot_data);
    }

    public function test_document_visibility_matches_score_and_validity_including_zero_and_manual_hiding(): void
    {
        foreach ([
            [0, null, 'years', ['GRADE', 'TEMPORAL'], ['PERMANENT']],
            [null, 'Excelente', 'years', ['GRADE', 'TEMPORAL'], ['PERMANENT']],
            [null, null, 'years', ['TEMPORAL'], ['GRADE', 'PERMANENT']],
            [null, null, 'permanent', ['PERMANENT'], ['GRADE', 'TEMPORAL']],
        ] as [$score, $label, $validity, $visible, $hidden]) {
            $certificate = new Certificate(['score' => $score, 'score_label' => $label, 'validity_type' => $validity, 'expiration_date' => $validity === 'permanent' ? null : '2029-01-01']);
            $certificate->setRelation('activity', null);
            $elements = [];
            foreach (['GRADE' => 'with_score', 'PERMANENT' => 'permanent', 'TEMPORAL' => 'temporary', 'LEGACY' => 'always', 'HIDDEN' => 'always'] as $text => $rule) {
                $elements[] = ['type' => 'text', 'content' => $text, 'visibility_rule' => $rule, 'is_visible' => $text !== 'HIDDEN', 'x' => 10, 'y' => 50, 'width' => 80, 'height' => 4];
            }
            $html = view('certificates.templates.engine', ['certificate' => $certificate, 'schema' => ['elements' => $elements], 'orientation' => 'landscape'])->render();
            foreach ([...$visible, 'LEGACY'] as $text) {
                $this->assertStringContainsString($text, $html);
            }
            foreach ([...$hidden, 'HIDDEN'] as $text) {
                $this->assertStringNotContainsString($text, $html);
            }
        }
    }

    public function test_grade_blocks_respect_the_activity_pdf_score_visibility_option(): void
    {
        $certificate = new Certificate(['score' => 85, 'validity_type' => 'permanent']);
        $activity = new Activity;
        $activity->setRelation('certificateConfig', new ActivityCertificateConfig(['show_score_on_pdf' => false]));
        $certificate->setRelation('activity', $activity);
        $schema = ['elements' => [['type' => 'text', 'content' => 'HIDDEN-GRADE', 'visibility_rule' => 'with_score', 'x' => 10, 'y' => 50, 'width' => 80, 'height' => 4]]];
        $html = view('certificates.templates.engine', ['certificate' => $certificate, 'schema' => $schema, 'orientation' => 'landscape'])->render();
        $this->assertStringNotContainsString('HIDDEN-GRADE', $html);
    }
}
