<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\Setting;

class CertificateSnapshotService
{
    public function __construct(
        protected TemplateEngineService $templateEngine,
        protected AssetManagerService $assetManager
    ) {}

    /**
     * Construye un snapshot documental 100% inmutable y autosuficiente.
     */
    public function buildImmutableSnapshot(
        Beneficiary $beneficiary,
        Activity $activity,
        CertificateType $certificateType,
        CertificateTemplate $template,
        string $issueDate,
        string $code,
        array $customFields = [],
        ?string $expirationDate = null,
        ?float $score = null,
        ?string $scoreLabel = null,
        string $validityType = 'years'
    ): array {
        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $activityConfig = $activity->certificateConfig;

        // 1. Resolver esquema visual (fusionando plantilla base + personalización de actividad)
        $schema = $template->configuration ?? [];
        if ($activityConfig) {
            if (! empty($activityConfig->primary_color)) {
                $schema['primary_color'] = $activityConfig->primary_color;
            }
            if (! empty($activityConfig->secondary_color)) {
                $schema['secondary_color'] = $activityConfig->secondary_color;
            }
            if (! empty($activityConfig->custom_title)) {
                $schema['custom_title'] = $activityConfig->custom_title;
            }
        }

        // 2. Resolver redacción de cuerpo
        $rawText = ($activityConfig && ! empty($activityConfig->custom_body_text))
            ? $activityConfig->custom_body_text
            : ($schema['body_text'] ?? 'Por su participación en {{actividad.nombre}}');

        $isPermanent = empty($expirationDate) || $validityType === 'permanent';
        $formattedScore = ! empty($scoreLabel) ? $scoreLabel : ($score !== null ? ($score.' pts') : null);

        $context = [
            'beneficiary' => $beneficiary,
            'activity' => $activity,
            'certificate' => [
                'code' => $code,
                'issue_date' => $issueDate,
                'expiration_date' => $expirationDate,
                'validity_type' => $validityType,
                'is_permanent' => $isPermanent,
                'score' => $score,
                'score_label' => $scoreLabel,
                'formatted_score' => $formattedScore,
                'type' => $certificateType->name,
            ],
            'settings' => $settings,
            'custom_fields' => $customFields,
        ];

        $resolvedBodyText = $this->templateEngine->interpolate($rawText, $context);

        // 3. Congelar firmas con imagen en Base64
        $signatures = $schema['signatures'] ?? [
            [
                'name' => $activity->responsible_authority ?? $settings['authority_name'] ?? 'Dr. Carlos Mendoza Fernández',
                'position' => $settings['authority_position'] ?? 'Vicedecano de Facultad',
                'institution' => 'Facultad de Ciencias Puras y Naturales - UMSA',
                'asset_path' => 'assets/institutional/firma_vicedecano.svg',
            ],
        ];

        foreach ($signatures as &$sig) {
            if (! empty($sig['asset_path'])) {
                $sig['asset_base64'] = $this->assetManager->getAssetBase64($sig['asset_path']);
            }
        }

        // 4. Congelar logos izquierdo y derecho en Base64 (soporta JPG, PNG, SVG)
        $leftLogoRef = $customFields['logo_left_id']
            ?? $schema['logo_left_id']
            ?? $schema['logo_left_path']
            ?? ($schema['logos'][0]['path'] ?? null)
            ?? 'assets/institutional/logo_umsa.svg';

        $rightLogoRef = $customFields['logo_right_id']
            ?? $schema['logo_right_id']
            ?? $schema['logo_right_path']
            ?? ($schema['logos'][1]['path'] ?? null)
            ?? 'assets/institutional/logo_fcpn.svg';

        $logosBase64 = [
            'left' => $this->assetManager->getAssetBase64($leftLogoRef),
            'right' => $this->assetManager->getAssetBase64($rightLogoRef),
        ];

        // 5. Congelar marca de agua gráfica (.png / .jpg / .svg) si está configurada
        $watermarkImageRef = $customFields['watermark_asset_id']
            ?? $schema['watermark_asset_id']
            ?? $schema['watermark_asset_path']
            ?? (! empty($schema['show_watermark']) ? 'assets/institutional/marca_agua_escudo.svg' : null);

        $watermarkImageBase64 = $watermarkImageRef
            ? $this->assetManager->getAssetBase64($watermarkImageRef)
            : null;

        // 6. Congelar sello oficial
        $stampRef = $customFields['stamp_asset_id']
            ?? $schema['stamp_asset_id']
            ?? $schema['stamp_asset_path']
            ?? 'assets/institutional/sello_oficial_vicedecanato.svg';

        $stampBase64 = ! empty($schema['show_stamp'])
            ? $this->assetManager->getAssetBase64($stampRef)
            : null;

        // 7. Normalizar esquema y congelar elementos dinámicos de lienzo (Base64 y textos interpolados)
        $schema = $this->templateEngine->normalizeSchema($schema, $template->orientation);

        if (! empty($schema['elements']) && is_array($schema['elements'])) {
            foreach ($schema['elements'] as &$el) {
                if (in_array($el['type'] ?? '', ['logo', 'signature', 'signature_block', 'stamp', 'watermark', 'image', 'background'])) {
                    $ref = $el['asset_id'] ?? $el['asset_path'] ?? null;
                    if ($ref && empty($el['asset_base64'])) {
                        $el['asset_base64'] = $this->assetManager->getAssetBase64($ref);
                    }
                } elseif (in_array($el['type'] ?? '', TemplateEngineService::TEXT_ELEMENT_TYPES, true) && ! empty($el['content'])) {
                    $el['resolved_content'] = $this->templateEngine->interpolate($el['content'], $context);
                }
            }
            unset($el);
        }

        return [
            'beneficiary' => [
                'name' => $beneficiary->full_title_name,
                'document' => $beneficiary->formatted_document,
                'email' => $beneficiary->email,
            ],
            'activity' => [
                'name' => $activity->name,
                'type' => $activity->activity_type,
                'hours' => $activity->academic_hours,
                'dates' => $activity->formatted_date_range,
                'authority' => $activity->responsible_authority,
                'location' => $activity->location,
            ],
            'certificate_type' => $certificateType->name,
            'template_id' => $template->id,
            'template_name' => $template->name,
            'template_version' => $template->current_version,
            'orientation' => $template->orientation,
            'design_schema' => $schema,
            'resolved_body_text' => $resolvedBodyText,
            'signatures' => $signatures,
            'logos_base64' => $logosBase64,
            'watermark_image_base64' => $watermarkImageBase64,
            'stamp_base64' => $stampBase64,
            'validity_type' => $validityType,
            'is_permanent' => $isPermanent,
            'expiration_date' => $expirationDate,
            'score' => $score,
            'score_label' => $scoreLabel,
            'formatted_score' => $formattedScore,
            'frozen_at' => now()->toIso8601String(),
        ];
    }
}
