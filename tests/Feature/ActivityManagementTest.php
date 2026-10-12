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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->adminUser = User::whereHas('role', fn ($q) => $q->where('name', Role::ADMIN))->first();
    }

    public function test_activity_show_page_renders_successfully(): void
    {
        $activity = Activity::first();
        $this->assertNotNull($activity);

        $response = $this->actingAs($this->adminUser)
            ->get(route('activities.show', $activity));

        $response->assertStatus(200);
        $response->assertViewIs('activities.show');
        $response->assertSee($activity->name);
        $response->assertSee('Diseño y Personalización de Certificado');
        $response->assertSee('Certificados Emitidos en este Evento');
        $response->assertSee('Emisión Masiva de Certificados');
    }

    public function test_activity_search_filters_with_sql_precedence(): void
    {
        $activityA = Activity::create([
            'name' => 'Taller Especial de Robótica y Sensores',
            'description' => 'Taller práctico intensivo',
            'activity_type' => 'taller',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'academic_hours' => 20,
            'status' => 'activa',
            'location' => 'Laboratorio 1',
            'organizer' => 'Vicedecanato',
            'responsible_authority' => 'Vicedecano',
        ]);

        $activityB = Activity::create([
            'name' => 'Seminario Especial de Robótica e IA',
            'description' => 'Seminario teórico',
            'activity_type' => 'seminario',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'academic_hours' => 10,
            'status' => 'activa',
            'location' => 'Auditorio',
            'organizer' => 'Vicedecanato',
            'responsible_authority' => 'Vicedecano',
        ]);

        // Buscar 'Robótica' filtrando SOLO por tipo 'taller'
        $response = $this->actingAs($this->adminUser)
            ->get(route('activities.index', [
                'search' => 'Robótica',
                'activity_type' => 'taller',
            ]));

        $response->assertStatus(200);
        $response->assertSee('Taller Especial de Robótica y Sensores');
        $response->assertDontSee('Seminario Especial de Robótica e IA');
    }

    public function test_activity_certificate_config_can_be_saved(): void
    {
        $activity = Activity::first();
        $template = CertificateTemplate::first();

        $payload = [
            'certificate_template_id' => $template->id,
            'custom_title' => 'CERTIFICADO DE ACREDITACIÓN',
            'custom_body_text' => 'Por haber aprobado satisfactoriamente los módulos de {{actividad.nombre}}',
            'primary_color' => '#1b365d',
            'secondary_color' => '#c59b27',
            'resolution_number' => 'V-FCPN N° 120/2026',
            'modality' => 'Presencial',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('activities.certificate-config', $activity), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('activity_certificate_configs', [
            'activity_id' => $activity->id,
            'certificate_template_id' => $template->id,
            'custom_title' => 'CERTIFICADO DE ACREDITACIÓN',
            'primary_color' => '#1b365d',
            'secondary_color' => '#c59b27',
        ]);

        $config = ActivityCertificateConfig::where('activity_id', $activity->id)->first();
        $this->assertNotNull($config);
        $this->assertEquals('V-FCPN N° 120/2026', $config->custom_fields_schema['resolucion'] ?? null);
        $this->assertEquals('Presencial', $config->custom_fields_schema['modalidad'] ?? null);
    }

    public function test_batch_issue_generates_certificates_correctly(): void
    {
        $activity = Activity::first();
        $template = CertificateTemplate::first();
        $type = CertificateType::first();
        $beneficiary = Beneficiary::first();

        // Eliminar certificados previos de esta combinación para probar emisión limpia
        Certificate::where('activity_id', $activity->id)
            ->where('beneficiary_id', $beneficiary->id)
            ->delete();

        $payload = [
            'beneficiary_ids' => [$beneficiary->id],
            'certificate_type_id' => $type->id,
            'certificate_template_id' => $template->id,
            'issue_date' => now()->toDateString(),
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('activities.batch-issue', $activity), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('certificates', [
            'activity_id' => $activity->id,
            'beneficiary_id' => $beneficiary->id,
            'certificate_type_id' => $type->id,
            'certificate_template_id' => $template->id,
        ]);
    }

    public function test_mandatory_asset_cannot_be_deleted(): void
    {
        $mandatoryAsset = InstitutionalAsset::where('is_mandatory', true)->first();

        if (! $mandatoryAsset) {
            $mandatoryAsset = InstitutionalAsset::create([
                'name' => 'Escudo Oficial Obligatorio',
                'type' => InstitutionalAsset::TYPE_LOGO,
                'file_path' => 'assets/institutional/escudo_obligatorio.svg',
                'mime_type' => 'image/svg+xml',
                'is_mandatory' => true,
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->delete(route('assets.destroy', $mandatoryAsset));

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('institutional_assets', [
            'id' => $mandatoryAsset->id,
        ]);
    }

    public function test_non_mandatory_asset_is_deleted_from_disk_and_database(): void
    {
        Storage::fake('public');

        $filePath = 'assets/institutional/temp_test_asset.png';
        Storage::disk('public')->put($filePath, 'fake-image-content');

        $asset = InstitutionalAsset::create([
            'name' => 'Recurso Temporal de Prueba',
            'type' => InstitutionalAsset::TYPE_BACKGROUND,
            'file_path' => $filePath,
            'mime_type' => 'image/png',
            'is_mandatory' => false,
            'is_active' => true,
        ]);

        $this->assertTrue(Storage::disk('public')->exists($filePath));

        $response = $this->actingAs($this->adminUser)
            ->delete(route('assets.destroy', $asset));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('institutional_assets', [
            'id' => $asset->id,
        ]);
        $this->assertFalse(Storage::disk('public')->exists($filePath));
    }

    public function test_preview_draft_renders_pdf_stream_successfully(): void
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
            'special_note' => 'Aprobado con mención de honor',
        ]);

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
