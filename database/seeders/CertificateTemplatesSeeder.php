<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use App\Models\User;
use App\Services\TemplateEngineService;
use Illuminate\Database\Seeder;

class CertificateTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::first();
        $engine = app(TemplateEngineService::class);
        $templates = $engine->getInitialTemplateDefinitions();

        foreach ($templates as $index => $tplData) {
            $template = CertificateTemplate::updateOrCreate(
                ['slug' => $tplData['slug']],
                [
                    'name' => $tplData['name'],
                    'category' => $tplData['category'],
                    'orientation' => $tplData['orientation'] ?? 'landscape',
                    'description' => $tplData['description'],
                    'view_path' => $tplData['view_path'] ?? 'certificates.templates.engine',
                    'configuration' => $tplData['configuration'],
                    'current_version' => 1,
                    'is_active' => true,
                    'is_default' => ($index === 0), // Institucional Clásica es la predeterminada
                    'created_by_user_id' => $admin?->id,
                ]
            );

            // Registrar versión inicial 1 en certificate_template_versions
            $template->versions()->updateOrCreate(
                ['version_number' => 1],
                [
                    'name' => "Versión 1.0 (Lanzamiento Oficial)",
                    'orientation' => $template->orientation,
                    'design_schema' => $template->configuration,
                    'change_summary' => "Plantilla inicial institucional preconfigurada para el Vicedecanato",
                    'created_by_user_id' => $admin?->id,
                ]
            );
        }
    }
}
