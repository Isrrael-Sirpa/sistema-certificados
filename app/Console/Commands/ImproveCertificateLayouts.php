<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\CertificateTemplate;
use App\Services\CertificateLayoutService;
use App\Services\TemplateEngineService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('certificates:improve-layouts {--apply : Guardar una nueva versión de cada diseño pendiente} {--template=* : Limitar a los identificadores indicados}')]
#[Description('Revisa o mejora la distribución A4 sin alterar certificados emitidos ni versiones anteriores')]
class ImproveCertificateLayouts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CertificateLayoutService $layouts, TemplateEngineService $engine): int
    {
        $query = CertificateTemplate::query()->orderBy('id');
        if ($ids = $this->option('template')) {
            $query->whereIn('id', $ids);
        }
        $templates = $query->get()->filter(fn (CertificateTemplate $template): bool => (int) ($template->configuration['layout_version'] ?? 1) < CertificateLayoutService::VERSION);
        $this->table(['ID', 'Plantilla', 'Formato', 'Versión actual'], $templates->map(fn (CertificateTemplate $template): array => [$template->id, $template->name, $template->orientation, $template->current_version])->all());
        if (! $this->option('apply')) {
            $this->info($templates->count().' diseños pendientes. Use --apply para crear nuevas versiones.');

            return self::SUCCESS;
        }
        $updated = DB::transaction(function () use ($templates, $layouts, $engine): int {
            $count = 0;
            foreach ($templates as $candidate) {
                $template = CertificateTemplate::query()->lockForUpdate()->findOrFail($candidate->id);
                if ((int) ($template->configuration['layout_version'] ?? 1) >= CertificateLayoutService::VERSION) {
                    continue;
                }
                $schema = $layouts->improve($engine->normalizeSchema($template->configuration ?? [], $template->orientation), $template->orientation);
                $oldVersion = $template->current_version;
                $newVersion = max($oldVersion, (int) $template->versions()->max('version_number')) + 1;
                $template->versions()->create([
                    'version_number' => $newVersion, 'name' => 'Versión '.$newVersion.'.0 · Distribución A4',
                    'orientation' => $template->orientation, 'design_schema' => $schema,
                    'change_summary' => 'Tipografía legible, distribución A4, calificación individual y vigencia; diseño anterior conservado.',
                    'created_by_user_id' => null,
                ]);
                $template->update(['configuration' => $schema, 'current_version' => $newVersion]);
                AuditLog::log('template_layout_improved', 'Distribución A4 mejorada: '.$template->name, $template, ['current_version' => $oldVersion], ['current_version' => $newVersion]);
                $count++;
            }

            return $count;
        });
        $this->info($updated.' plantillas actualizadas con una nueva versión. Certificados emitidos sin modificaciones.');

        return self::SUCCESS;
    }
}
