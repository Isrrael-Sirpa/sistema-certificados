@php
    $thumbnailSchema = $tpl->configuration ?? [];
    if (!array_key_exists('elements', $thumbnailSchema)) {
        $thumbnailSchema = app(\App\Services\TemplateEngineService::class)->normalizeSchema($thumbnailSchema, $tpl->orientation);
    }

    $thumbnailElements = is_array($thumbnailSchema['elements'] ?? null) ? $thumbnailSchema['elements'] : [];
    $thumbnailPortrait = $tpl->orientation === 'portrait';
    $thumbnailWidth = $thumbnailPortrait ? 580 : 820;
    $thumbnailColor = static function (mixed $value, string $fallback): string {
        return is_string($value) && preg_match('/^#(?:[0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value) ? $value : $fallback;
    };
    $thumbnailPrimary = $thumbnailColor($thumbnailSchema['primary_color'] ?? null, '#024966');
    $thumbnailSecondary = $thumbnailColor($thumbnailSchema['secondary_color'] ?? null, '#d4af37');
    $thumbnailText = $thumbnailColor($thumbnailSchema['text_color'] ?? null, '#1e293b');
    $thumbnailFonts = ['Helvetica', 'Arial', 'Times', 'Times-Roman', 'Times New Roman', 'Courier', 'Courier New', 'DejaVu Sans', 'DejaVu Serif', 'sans-serif', 'serif'];
    $thumbnailFont = in_array($thumbnailSchema['font_family'] ?? '', $thumbnailFonts, true) ? $thumbnailSchema['font_family'] : 'Helvetica';
    $thumbnailSamples = [
        'beneficiario.nombre' => 'Nombre del participante',
        'beneficiario.documento' => 'Documento de ejemplo',
        'actividad.nombre' => 'Actividad académica de ejemplo',
        'actividad.horas' => '40',
        'actividad.fechas' => 'Octubre de 2026',
        'certificado.tipo' => 'CERTIFICADO DE PARTICIPACIÓN',
        'certificado.codigo' => 'CERT-EJEMPLO',
        'certificado.lugar_fecha_emision' => 'La Paz, Bolivia',
        'certificado.fecha_emision_larga' => '28 de octubre de 2026',
        'certificado.nota' => '85',
        'certificado.calificacion' => '85',
        'certificado.mencion' => 'Con aprobación',
        'certificado.score' => '85',
        'certificado.score_label' => 'Con aprobación',
        'certificado.puntaje' => '85',
        'certificado.vigencia' => 'Válido hasta el 28 de octubre de 2029',
        'certificado.vigencia_corta' => 'Hasta 28 de octubre de 2029',
        'certificado.fecha_vencimiento' => '28 de octubre de 2029',
        'actividad.resolucion' => 'Resolución de ejemplo',
        'acreditacion.resolucion' => 'Resolución de ejemplo',
    ];
    $thumbnailSampleText = static function (mixed $value) use ($thumbnailSamples): string {
        return preg_replace_callback('/@?\{\{\s*([^{}]+?)\s*\}\}/', static function (array $match) use ($thumbnailSamples): string {
            return $thumbnailSamples[trim($match[1])] ?? 'Dato de ejemplo';
        }, (string) $value);
    };
@endphp

<div class="ft-template-thumbnail relative w-full overflow-hidden bg-white shadow-md" style="container-type: inline-size; aspect-ratio: {{ $thumbnailPortrait ? '210 / 297' : '297 / 210' }}; font-family: {{ $thumbnailFont }}; color: {{ $thumbnailText }};" aria-hidden="true">
    @if(($thumbnailSchema['border_style'] ?? 'ornamental-double') !== 'none')
        <div class="absolute pointer-events-none" style="inset: 2.7%; border: .35cqw {{ ($thumbnailSchema['border_style'] ?? '') === 'ornamental-double' ? 'double' : 'solid' }} {{ $thumbnailSecondary }};"></div>
        <div class="absolute pointer-events-none" style="inset: 4.1%; border: .12cqw solid {{ $thumbnailPrimary }};"></div>
    @endif

    @foreach($thumbnailElements as $thumbnailElement)
        @continue(!is_array($thumbnailElement) || ($thumbnailElement['is_visible'] ?? true) === false)
        @continue(($thumbnailElement['visibility_rule'] ?? 'always') === 'permanent')
        @php
            $thumbnailStyles = is_array($thumbnailElement['styles'] ?? null) ? $thumbnailElement['styles'] : [];
            $thumbnailType = $thumbnailElement['type'] ?? 'text';
            $thumbnailX = (float) ($thumbnailElement['x'] ?? 0);
            $thumbnailY = (float) ($thumbnailElement['y'] ?? 0);
            $thumbnailElementWidth = max(0, (float) ($thumbnailElement['width'] ?? 80));
            $thumbnailElementHeight = max(0, (float) ($thumbnailElement['height'] ?? 8));
            $thumbnailOpacity = max(0, min(1, (float) ($thumbnailElement['opacity'] ?? 1)));
            $thumbnailRotation = (float) ($thumbnailElement['rotation'] ?? 0);
            $thumbnailZ = (int) ($thumbnailElement['z_index'] ?? 10);
            $thumbnailSize = max(1, (float) ($thumbnailStyles['font_size'] ?? $thumbnailElement['font_size'] ?? 12)) * 100 / $thumbnailWidth;
            $thumbnailElementColor = $thumbnailColor($thumbnailStyles['text_color'] ?? $thumbnailElement['text_color'] ?? null, $thumbnailText);
            $thumbnailAlign = $thumbnailStyles['text_align'] ?? $thumbnailElement['text_align'] ?? 'center';
            $thumbnailAlign = in_array($thumbnailAlign, ['left', 'center', 'right', 'justify'], true) ? $thumbnailAlign : 'center';
            $thumbnailWeight = $thumbnailStyles['font_weight'] ?? $thumbnailElement['font_weight'] ?? 'normal';
            $thumbnailWeight = in_array((string) $thumbnailWeight, ['normal', 'bold', '100', '200', '300', '400', '500', '600', '700', '800', '900'], true) ? $thumbnailWeight : 'normal';
            $thumbnailItalic = ($thumbnailStyles['font_style'] ?? $thumbnailElement['font_style'] ?? '') === 'italic' ? 'italic' : 'normal';
            $thumbnailElementFont = $thumbnailStyles['font_family'] ?? $thumbnailElement['font_family'] ?? $thumbnailFont;
            $thumbnailElementFont = in_array($thumbnailElementFont, $thumbnailFonts, true) ? $thumbnailElementFont : $thumbnailFont;
            $thumbnailTracking = (float) ($thumbnailStyles['letter_spacing'] ?? $thumbnailElement['letter_spacing'] ?? 0) * 100 / $thumbnailWidth;
            $thumbnailTransform = $thumbnailStyles['text_transform'] ?? 'none';
            $thumbnailTransform = in_array($thumbnailTransform, ['none', 'uppercase', 'lowercase', 'capitalize'], true) ? $thumbnailTransform : 'none';
            $thumbnailAsset = (string) (($thumbnailElement['asset_url'] ?? '') ?: ($thumbnailElement['asset_path'] ?? ''));
            if ($thumbnailAsset !== '' && !preg_match('#^https?://#i', $thumbnailAsset)) {
                $thumbnailAsset = preg_match('#^(?:/?(?:assets|storage)/)#', $thumbnailAsset) ? asset(ltrim($thumbnailAsset, '/')) : '';
            }
            $thumbnailFit = $thumbnailStyles['object_fit'] ?? $thumbnailElement['object_fit'] ?? 'contain';
            $thumbnailFit = in_array($thumbnailFit, ['contain', 'cover', 'fill'], true) ? $thumbnailFit : 'contain';
        @endphp
        <div class="absolute overflow-hidden" style="left: {{ $thumbnailX }}%; top: {{ $thumbnailY }}%; width: {{ $thumbnailElementWidth }}%; height: {{ $thumbnailElementHeight }}%; opacity: {{ $thumbnailOpacity }}; z-index: {{ $thumbnailZ }}; transform: rotate({{ $thumbnailRotation }}deg);">
            @if(in_array($thumbnailType, ['logo', 'watermark', 'stamp', 'image', 'background'], true))
                @if($thumbnailAsset !== '')
                    <img src="{{ $thumbnailAsset }}" alt="" loading="lazy" class="w-full h-full" style="object-fit: {{ $thumbnailFit }};">
                @endif
            @elseif(in_array($thumbnailType, ['shape', 'band', 'rectangle', 'ellipse'], true))
                <div class="w-full h-full" style="background: {{ $thumbnailColor($thumbnailStyles['fill'] ?? null, $thumbnailPrimary) }}; border: {{ max(0, (float) ($thumbnailStyles['border_width'] ?? 0)) * 100 / $thumbnailWidth }}cqw solid {{ $thumbnailColor($thumbnailStyles['border_color'] ?? null, $thumbnailSecondary) }}; border-radius: {{ ($thumbnailElement['shape'] ?? $thumbnailType) === 'ellipse' ? '50%' : max(0, (float) ($thumbnailStyles['border_radius'] ?? 0)) * 100 / $thumbnailWidth.'cqw' }};"></div>
            @elseif(in_array($thumbnailType, ['qr', 'qr_code'], true))
                <svg viewBox="0 0 24 24" class="w-full h-full" fill="currentColor" aria-hidden="true">
                    <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm13-2h3v2h-3v-2zm-3 2h2v2h-2v-2zm3 2h3v4h-3v-4zm-5 2h2v2h-2v-2zm2 2h3v-2h-3v2z"/>
                </svg>
            @elseif(in_array($thumbnailType, ['signature', 'signature_block'], true))
                <div class="w-full h-full flex flex-col items-center justify-end text-center" style="font-size: {{ 10 * 100 / $thumbnailWidth }}cqw; line-height: 1.2;">
                    @if($thumbnailAsset !== '')
                        <img src="{{ $thumbnailAsset }}" alt="" loading="lazy" class="max-w-full object-contain" style="height: 50%;">
                    @endif
                    <div style="width: 80%; border-top: .12cqw solid {{ $thumbnailText }};"></div>
                    <p class="w-full truncate font-bold">{{ $thumbnailSampleText($thumbnailElement['content'] ?? $thumbnailElement['name'] ?? 'Autoridad firmante') }}</p>
                    <p class="w-full truncate">{{ $thumbnailSampleText($thumbnailElement['subtitle'] ?? $thumbnailElement['position'] ?? '') }}</p>
                </div>
            @elseif(in_array($thumbnailType, ['divider', 'decorative_line', 'line'], true))
                <div class="w-full h-full flex items-center"><div class="w-full" style="height: .25cqw; background: {{ $thumbnailPrimary }};"></div></div>
            @else
                <p class="w-full h-full overflow-hidden" style="font-family: {{ $thumbnailElementFont }}; font-size: {{ $thumbnailSize }}cqw; font-weight: {{ $thumbnailWeight }}; font-style: {{ $thumbnailItalic }}; text-align: {{ $thumbnailAlign }}; text-transform: {{ $thumbnailTransform }}; letter-spacing: {{ $thumbnailTracking }}cqw; color: {{ $thumbnailElementColor }}; line-height: {{ max(.5, min(3, (float) ($thumbnailStyles['line_height'] ?? $thumbnailElement['line_height'] ?? 1.3))) }}; white-space: pre-line;">{{ $thumbnailSampleText($thumbnailElement['content'] ?? '') }}</p>
            @endif
        </div>
    @endforeach

    @if($thumbnailElements === [])
        <div class="absolute inset-0 flex flex-col items-center justify-center text-slate-300" style="font-size: 3cqw;">
            <span class="text-[5cqw]">＋</span>
            <span>Lienzo en blanco</span>
        </div>
    @endif
</div>
