<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\User;
use App\Services\CertificateLayoutService;
use App\Services\CertificatePdfService;
use App\Services\CertificateService;
use App\Services\CertificateSnapshotService;
use App\Services\TemplateEngineService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CertificateLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_layout_refresh_preserves_previous_versions_and_certificates_and_is_idempotent(): void
    {
        $template = CertificateTemplate::firstOrFail();
        $issued = app(CertificateService::class)->issueCertificate(['beneficiary_id' => Beneficiary::first()->id, 'activity_id' => Activity::first()->id, 'certificate_type_id' => CertificateType::first()->id, 'certificate_template_id' => $template->id], User::first());
        $snapshot = $issued->snapshot_data;
        $originalVersion = $template->versions()->first()->design_schema;
        $legacy = $template->configuration;
        unset($legacy['layout_version']);
        $template->update(['configuration' => $legacy]);

        $this->artisan('certificates:improve-layouts', ['--template' => [$template->id]])->assertExitCode(0);
        $this->assertSame(1, $template->fresh()->current_version);
        $this->artisan('certificates:improve-layouts', ['--template' => [$template->id], '--apply' => true])->assertExitCode(0);
        $this->assertSame(2, $template->fresh()->current_version);
        $this->assertSame(2, $template->fresh()->configuration['layout_version']);
        $this->assertSame($originalVersion, $template->versions()->where('version_number', 1)->first()->design_schema);
        $this->assertSame($snapshot, $issued->fresh()->snapshot_data);
        $this->artisan('certificates:improve-layouts', ['--template' => [$template->id], '--apply' => true])->assertExitCode(0);
        $this->assertSame(2, $template->versions()->count());
    }

    public function test_composition_preserves_locked_elements_assets_and_custom_text(): void
    {
        $engine = app(TemplateEngineService::class);
        $schema = $engine->normalizeSchema($engine->getInitialTemplateDefinitions()[0]['configuration']);
        $schema['elements'][0]['is_locked'] = true;
        $locked = $schema['elements'][0];
        $schema['elements'][] = ['id' => 'custom-note', 'type' => 'text', 'content' => 'Texto propio conservado', 'x' => 10, 'y' => 20, 'width' => 20, 'height' => 4];
        $improved = app(CertificateLayoutService::class)->improve($schema, 'landscape');
        $this->assertSame($locked, $improved['elements'][0]);
        $this->assertContains('Texto propio conservado', array_column($improved['elements'], 'content'));
        foreach ($improved['elements'] as $element) {
            $this->assertGreaterThanOrEqual(0, $element['x']);
            $this->assertGreaterThanOrEqual(0, $element['y']);
            $this->assertLessThanOrEqual(100.01, $element['x'] + $element['width']);
            $this->assertLessThanOrEqual(100.01, $element['y'] + $element['height']);
        }
    }

    public function test_every_initial_template_renders_on_one_a4_page_with_canonical_styles(): void
    {
        foreach (CertificateTemplate::all() as $template) {
            $certificate = $this->sampleCertificate($template);
            $pdf = app(CertificatePdfService::class)->render($certificate);
            $bytes = $pdf->output();
            $this->assertStringStartsWith('%PDF-', $bytes);
            $this->assertSame(1, $pdf->getDomPDF()->getCanvas()->get_page_count(), $template->name);
            $html = view('certificates.templates.engine', ['certificate' => $certificate, 'schema' => $template->configuration, 'orientation' => $template->orientation])->render();
            $this->assertStringContainsString('font-weight:bold', $html);
            if ($directory = getenv('CERTIFICATE_LAYOUT_REVIEW_DIR')) {
                file_put_contents($directory.'/'.$template->slug.'.pdf', $bytes);
                if (in_array($template->slug, ['institucional-clasica', 'institucional-vertical'], true)) {
                    file_put_contents($directory.'/muestra-'.$template->orientation.'.pdf', $bytes);
                }
            }
            unset($pdf, $bytes, $html, $certificate);
            gc_collect_cycles();
        }
    }

    public function test_new_renderer_reads_styles_and_renders_editable_bands(): void
    {
        $certificate = $this->sampleCertificate(CertificateTemplate::first());
        $schema = ['layout_version' => 2, 'border_style' => 'none', 'elements' => [
            ['id' => 'title', 'type' => 'text', 'content' => 'Título visible', 'x' => 10, 'y' => 10, 'width' => 80, 'height' => 10, 'styles' => ['font_size' => 28, 'font_weight' => 'bold', 'text_color' => '#950d1f', 'fit_text' => false]],
            ['id' => 'band', 'type' => 'shape', 'shape' => 'rectangle', 'x' => 0, 'y' => 0, 'width' => 100, 'height' => 5, 'rotation' => 0, 'styles' => ['fill' => '#110b51']],
        ]];
        $html = view('certificates.templates.engine', ['certificate' => $certificate, 'schema' => $schema, 'orientation' => 'landscape'])->render();
        $this->assertStringContainsString('color:#950d1f', $html);
        $this->assertStringContainsString('background:#110b51', $html);
        $this->assertMatchesRegularExpression('/font-size:28\.[0-9]+pt/', $html);
    }

    public function test_dynamic_text_blocks_keep_their_styles_and_resolve_each_recipient_in_pdf_and_snapshot(): void
    {
        $template = CertificateTemplate::first();
        $template->configuration = ['layout_version' => 2, 'border_style' => 'none', 'elements' => [
            ['id' => 'recipient', 'type' => 'beneficiary_name', 'content' => '{{beneficiario.nombre}}', 'x' => 10, 'y' => 20, 'width' => 80, 'height' => 15, 'font_size' => 12, 'text_color' => '#000000', 'styles' => ['font_size' => 26.5, 'text_color' => '#950d1f', 'fit_text' => false]],
            ['id' => 'score', 'type' => 'body_text', 'content' => 'Nota: {{certificado.puntaje}}', 'x' => 10, 'y' => 40, 'width' => 80, 'height' => 10],
        ]];
        $certificate = $this->sampleCertificate($template);
        $pdf = app(CertificatePdfService::class)->render($certificate);
        $document = $pdf->getDomPDF()->getDom();
        $this->assertStringContainsString('María Fernanda Rodríguez Quispe Mamani', $document->textContent);
        $this->assertStringContainsString('Nota: 93.5', $document->textContent);
        $this->assertStringNotContainsString('{{beneficiario.nombre}}', $document->textContent);
        $this->assertStringContainsString('color:#950d1f', $document->saveHTML());
        $this->assertMatchesRegularExpression('/font-size:27\.[0-9]+pt/', $document->saveHTML());

        $snapshot = app(CertificateSnapshotService::class)->buildImmutableSnapshot($certificate->beneficiary, $certificate->activity, $certificate->certificateType, $template, '2026-10-29', 'MUESTRA', [], '2029-10-29', 93.5);
        $this->assertStringContainsString('María Fernanda Rodríguez Quispe Mamani', $snapshot['design_schema']['elements'][0]['resolved_content']);
        $this->assertSame('Nota: 93.5', $snapshot['design_schema']['elements'][1]['resolved_content']);
        $this->assertSame(26.5, $snapshot['design_schema']['elements'][0]['styles']['font_size']);
    }

    private function sampleCertificate(CertificateTemplate $template): Certificate
    {
        $certificate = new Certificate(['certificate_code' => 'MUESTRA-DISENO-A4', 'verification_token' => 'sample-layout-review', 'issue_date' => '2026-10-29', 'expiration_date' => '2029-10-29', 'validity_type' => 'years', 'score' => 93.5, 'score_label' => 'Distinción académica', 'status' => 'VIGENTE']);
        $certificate->setRelation('beneficiary', new Beneficiary(['first_name' => 'María Fernanda', 'last_name' => 'Rodríguez Quispe Mamani', 'academic_title' => 'Univ.', 'document_type' => 'CI', 'document_number' => '1234567', 'document_expedition' => 'LP']));
        $activity = new Activity(['name' => 'Seminario Internacional de Inteligencia Artificial y Transformación Digital Aplicada a la Educación Superior', 'activity_type' => 'Seminario', 'start_date' => '2026-10-20', 'end_date' => '2026-10-25', 'academic_hours' => 40, 'responsible_authority' => 'Vicedecanato']);
        $activity->setRelation('certificateConfig', null);
        $certificate->setRelation('activity', $activity);
        $certificate->setRelation('certificateType', new CertificateType(['name' => 'Certificado de Aprobación', 'has_score' => true]));
        $certificate->setRelation('certificateTemplate', $template);

        return $certificate;
    }
}
