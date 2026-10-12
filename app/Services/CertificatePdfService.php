<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class CertificatePdfService
{
    public function __construct(
        protected QRCodeService $qrCodeService,
        protected TemplateEngineService $templateEngine,
        protected AssetManagerService $assetManager
    ) {}

    /**
     * Renderiza el PDF de un certificado y retorna la instancia DomPDF.
     * Soporta snapshots congelados para certificados ya emitidos (inmutabilidad documental).
     */
    public function render(Certificate $certificate)
    {
        $qrUrl = $certificate->verification_url;
        $qrBase64 = $this->qrCodeService->generateBase64Svg($qrUrl, 160);
        $settings = Setting::all()->pluck('value', 'key')->toArray();

        // 1. Verificar si existe snapshot de diseño congelado en el certificado (Inmutabilidad)
        $snapshot = $certificate->snapshot_data ?? [];
        $isFrozen = ! empty($snapshot['design_schema']);

        if ($isFrozen) {
            $schema = $snapshot['design_schema'];
            $orientation = $snapshot['orientation'] ?? 'landscape';
            $resolvedBodyText = $snapshot['resolved_body_text'] ?? '';
            $signatures = $snapshot['signatures'] ?? [];
            $beneficiaryData = $snapshot['beneficiary'] ?? [];
            $certificateTypeName = $snapshot['certificate_type'] ?? '';
            $logosBase64 = $snapshot['logos_base64'] ?? [];
            $watermarkImageBase64 = $snapshot['watermark_image_base64'] ?? null;
            $stampBase64 = $snapshot['stamp_base64'] ?? null;
        } else {
            // Emisión nueva o certificado sin snapshot previo: obtener de la plantilla o actividad
            $template = $certificate->certificateTemplate;
            $activityConfig = $certificate->activity?->certificateConfig;

            $templateConfig = $template?->configuration ?? [];
            $secondaryFallback = $templateConfig['secondary_color'] ?? '#d4af37';

            $schema = $activityConfig && ! empty($activityConfig->primary_color)
                ? array_merge($templateConfig, [
                    'primary_color' => $activityConfig->primary_color,
                    'secondary_color' => $activityConfig->secondary_color ?? $secondaryFallback,
                ])
                : $templateConfig;

            $orientation = $template?->orientation ?? 'landscape';
            $certificateTypeName = $certificate->certificateType->name ?? 'CERTIFICADO';

            // Interpolar texto del cuerpo
            $rawBodyText = ($activityConfig && ! empty($activityConfig->custom_body_text))
                ? $activityConfig->custom_body_text
                : ($schema['body_text'] ?? 'Por su participación en {{actividad.nombre}}');

            $context = [
                'beneficiary' => $certificate->beneficiary,
                'activity' => $certificate->activity,
                'certificate' => $certificate,
                'settings' => $settings,
                'custom_fields' => $certificate->custom_fields_data ?? [],
            ];
            $resolvedBodyText = $this->templateEngine->interpolate($rawBodyText, $context);

            $beneficiaryData = [
                'name' => $certificate->beneficiary->full_title_name,
                'document' => $certificate->beneficiary->formatted_document,
            ];

            // Resolver firmas con Base64
            $signatures = $schema['signatures'] ?? [
                [
                    'name' => $settings['authority_name'] ?? 'Dr. Carlos Mendoza Fernández',
                    'position' => $settings['authority_position'] ?? 'Vicedecano de Facultad',
                    'institution' => 'FCPN - UMSA',
                    'asset_path' => 'assets/institutional/firma_vicedecano.svg',
                ],
            ];

            foreach ($signatures as &$sig) {
                if (! empty($sig['asset_path'])) {
                    $sig['asset_base64'] = $this->assetManager->getAssetBase64($sig['asset_path']);
                }
            }

            // Resolver logos en Base64 (soporta JPG, PNG, SVG)
            $customFields = $certificate->custom_fields_data ?? [];
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

            // Resolver marca de agua e imagen de fondo en Base64
            $watermarkRef = $customFields['watermark_asset_id']
                ?? $schema['watermark_asset_id']
                ?? null;
            $watermarkImageBase64 = $watermarkRef ? $this->assetManager->getAssetBase64($watermarkRef) : null;

            // Resolver sello seco en Base64
            $stampRef = $customFields['stamp_asset_id']
                ?? $schema['stamp_asset_id']
                ?? (! empty($schema['show_stamp']) ? 'assets/institutional/sello_oficial_vicedecanato.svg' : null);
            $stampBase64 = $stampRef ? $this->assetManager->getAssetBase64($stampRef) : null;
        }

        // Normalizar esquema asegurando estructura de elementos
        $schema = $this->templateEngine->normalizeSchema($schema, $orientation);

        // Pre-procesar elementos si existen en el esquema (soporte integral para snapshots y emisión en vivo)
        if (! empty($schema['elements']) && is_array($schema['elements'])) {
            $context = $context ?? [
                'beneficiary' => $beneficiaryData ?? $certificate->beneficiary,
                'activity' => $certificate->activity,
                'certificate' => $certificate,
                'settings' => $settings,
                'custom_fields' => $certificate->custom_fields_data ?? [],
            ];

            foreach ($schema['elements'] as &$el) {
                if (in_array($el['type'] ?? '', ['logo', 'signature', 'stamp', 'watermark', 'image'])) {
                    if (empty($el['asset_base64'])) {
                        $ref = $el['asset_id'] ?? $el['asset_path'] ?? null;
                        if ($ref) {
                            $el['asset_base64'] = $this->assetManager->getAssetBase64($ref);
                        }
                    }
                } elseif (($el['type'] ?? '') === 'text' && empty($el['resolved_content']) && ! empty($el['content'])) {
                    $el['resolved_content'] = $this->templateEngine->interpolate($el['content'], $context);
                }
            }
            unset($el);
        }

        $view = 'certificates.templates.engine';
        if (! view()->exists($view)) {
            $view = 'certificates.templates.default';
        }

        $pdf = Pdf::loadView($view, [
            'certificate' => $certificate,
            'schema' => $schema,
            'orientation' => $orientation,
            'qr_base64' => $qrBase64,
            'settings' => $settings,
            'resolved_body_text' => $resolvedBodyText,
            'signatures' => $signatures,
            'beneficiary_data' => $beneficiaryData,
            'certificate_type_name' => $certificateTypeName,
            'logos_base64' => $logosBase64,
            'watermark_image_base64' => $watermarkImageBase64,
            'stamp_base64' => $stampBase64,
        ]);

        $pdf->setPaper('a4', $orientation);
        $pdf->setOption('isRemoteEnabled', true);
        $pdf->setOption('isHtml5ParserEnabled', true);
        $pdf->setOption('defaultFont', $schema['font_family'] ?? 'Helvetica');

        return $pdf;
    }

    /**
     * Guarda el archivo PDF renderizado en el disco local y retorna la ruta relativa.
     */
    public function generateAndStore(Certificate $certificate): string
    {
        $pdf = $this->render($certificate);
        $fileName = 'certificates/'.date('Y').'/'.$certificate->certificate_code.'.pdf';

        Storage::disk('public')->put($fileName, $pdf->output());

        return $fileName;
    }
}
