@php
    $portrait = ($orientation ?? 'landscape') === 'portrait';
    $paperWidth = $portrait ? 210 : 297;
    $paperHeight = $portrait ? 297 : 210;
    $unit = ($paperWidth * 72 / 25.4) / ($portrait ? 580 : 820);
    $primary = $schema['primary_color'] ?? '#110b51';
    $secondary = $schema['secondary_color'] ?? '#efb43c';
    $baseFont = $schema['font_family'] ?? 'DejaVu Serif';
    $hasScore = ($certificate->score !== null || filled($certificate->score_label)) && ($certificate->activity?->certificateConfig?->show_score_on_pdf ?? true);
    $permanent = $certificate->isPermanent();
    $fitFont = static function (string $text, array $styles, float $width, float $height) use ($font_metrics, $baseFont, $unit): float {
        $size = max(1, (float) ($styles['font_size'] ?? 12)) * $unit;
        if (($styles['fit_text'] ?? true) === false || $text === '') { return $size; }
        $minimum = min($size, (float) ($styles['min_font_size'] ?? max(8, $size / $unit * .72)) * $unit);
        $family = $styles['font_family'] ?? $baseFont;
        $bold = ($styles['font_weight'] ?? '') === 'bold' || (int) ($styles['font_weight'] ?? 0) >= 600;
        $italic = ($styles['font_style'] ?? '') === 'italic';
        $font = $font_metrics?->getFont($family, $bold ? ($italic ? 'bold_italic' : 'bold') : ($italic ? 'italic' : 'normal'));
        $font = $font ?: $font_metrics?->getFont('DejaVu Serif', $bold ? 'bold' : 'normal');
        $widthOf = static fn(string $value, float $at): float => $font_metrics && $font ? $font_metrics->getTextWidth($value, $font, $at, 0, (float) ($styles['letter_spacing'] ?? 0) * $unit) : mb_strlen($value) * $at * .55;
        if (($styles['text_transform'] ?? '') === 'uppercase') { $text = mb_strtoupper($text); }
        do {
            $lines = 0;
            foreach (preg_split('/\R/u', $text) as $paragraph) {
                $line = '';
                foreach (preg_split('/\s+/u', trim($paragraph)) as $word) {
                    $candidate = $line === '' ? $word : $line.' '.$word;
                    if ($line !== '' && $widthOf($candidate, $size) > $width) { $lines++; $line = $word; }
                    else { $line = $candidate; }
                }
                $lines += max(1, (int) ceil($widthOf($line, $size) / max(1, $width)));
            }
            $lineHeight = $font_metrics && $font ? $font_metrics->getFontHeight($font, $size) : $size * 1.17;
            if ($lines * $lineHeight * (float) ($styles['line_height'] ?? 1.3) <= $height || $size <= $minimum) { break; }
            $size = max($minimum, $size - .5);
        } while (true);
        return round($size, 2);
    };
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Certificado {{ $certificate->certificate_code }}</title>
    <style>
        @page { margin: 0; size: {{ $paperWidth }}mm {{ $paperHeight }}mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; width: {{ $paperWidth }}mm; height: {{ $paperHeight }}mm; }
        body { font-family: '{{ $baseFont }}', 'DejaVu Serif', serif; color: {{ $schema['text_color'] ?? '#1e293b' }}; }
        .sheet { position: relative; width: {{ $paperWidth }}mm; height: {{ $paperHeight }}mm; overflow: hidden; background: {{ $schema['page']['background_color'] ?? '#ffffff' }}; }
        .frame-outer, .frame-inner { position: absolute; z-index: 3; }
        .frame-outer { left: 6mm; top: 6mm; width: {{ $paperWidth - 12 }}mm; height: {{ $paperHeight - 12 }}mm; border: 2pt {{ ($schema['border_style'] ?? '') === 'ornamental-double' ? 'double' : 'solid' }} {{ $secondary }}; }
        .frame-inner { left: 8mm; top: 8mm; width: {{ $paperWidth - 16 }}mm; height: {{ $paperHeight - 16 }}mm; border: .5pt solid {{ $primary }}; }
        .element { position: absolute; margin: 0; padding: 0; }
        .text { overflow: hidden; overflow-wrap: break-word; }
        .asset { text-align: center; overflow: hidden; }
        .asset img { max-width: 100%; max-height: 100%; width: auto; height: auto; }
        .signature { text-align: center; overflow: hidden; }
        .signature .signature-image { height: 36%; text-align: center; overflow: hidden; }
        .signature img { max-width: 80%; max-height: 100%; width: auto; height: auto; }
        .signature img.original-rubric { height: 140%; max-height: none; }
        .signature .rule { width: 88%; margin: 2pt auto 3pt; border-top: .7pt solid {{ $primary }}; }
        .signature .name { font-weight: bold; line-height: 1.15; }
        .signature .position { font-size: {{ 9.5 * $unit }}pt; line-height: 1.2; color: #475569; }
        .signature .institution { font-size: {{ 8 * $unit }}pt; line-height: 1.2; color: #64748b; }
        .verification { overflow: hidden; text-align: center; color: {{ $primary }}; }
        .verification .qr-image { height: 70%; }
        .verification img { max-width: 100%; max-height: 100%; width: auto; height: auto; }
        .verification .code { margin-top: 2pt; font-family: 'DejaVu Sans', sans-serif; font-size: 7pt; line-height: 1.2; }
        .verification .validity { margin-top: 2pt; color: #047857; font-size: 6.5pt; line-height: 1.2; }
    </style>
</head>
<body><div class="sheet">
    @if(($schema['border_style'] ?? '') !== 'none')
        <div class="frame-outer"></div><div class="frame-inner"></div>
        @if(in_array($schema['border_style'] ?? '', ['ornamental-double', 'gold-filigree'], true))
            @foreach(['tl' => ['left', 'top'], 'tr' => ['right', 'top'], 'bl' => ['left', 'bottom'], 'br' => ['right', 'bottom']] as $corner => [$horizontal, $vertical])
                @if(!empty($corners_base64[$corner]))<img src="{{ $corners_base64[$corner] }}" style="position:absolute;{{ $horizontal }}:6mm;{{ $vertical }}:6mm;width:11mm;height:11mm;z-index:4;" alt="">@endif
            @endforeach
        @endif
    @endif
    @foreach($schema['elements'] ?? [] as $element)
        @continue(($element['is_visible'] ?? true) === false)
        @continue(!match ($element['visibility_rule'] ?? 'always') { 'with_score' => $hasScore, 'permanent' => $permanent, 'temporary' => !$permanent, default => true })
        @php
            $styles = array_merge(array_intersect_key($element, array_flip(['font_family', 'font_size', 'font_weight', 'font_style', 'text_align', 'text_color', 'line_height', 'letter_spacing', 'border_bottom', 'border_bottom_color', 'object_fit'])), $element['styles'] ?? []);
            $type = $element['type'] ?? 'text';
            $x = (float) ($element['x'] ?? 0) / 100 * $paperWidth;
            $y = (float) ($element['y'] ?? 0) / 100 * $paperHeight;
            $width = (float) ($element['width'] ?? 80) / 100 * $paperWidth;
            $height = (float) ($element['height'] ?? 8) / 100 * $paperHeight;
            $position = 'left:'.$x.'mm;top:'.$y.'mm;width:'.$width.'mm;height:'.$height.'mm;z-index:'.(int) ($element['z_index'] ?? 10).';opacity:'.max(0, min(1, (float) ($element['opacity'] ?? 1))).';transform:rotate('.(float) ($element['rotation'] ?? 0).'deg);';
            $asset = $element['asset_base64'] ?? ($type === 'watermark' ? ($watermark_image_base64 ?? '') : '');
        @endphp
        @if(in_array($type, ['shape', 'band', 'rectangle', 'ellipse'], true))
            <div class="element" style="{{ $position }}background:{{ $styles['fill'] ?? $primary }};border:{{ (float) ($styles['border_width'] ?? 0) * $unit }}pt solid {{ $styles['border_color'] ?? $secondary }};border-radius:{{ ($element['shape'] ?? $type) === 'ellipse' ? '50%' : ((float) ($styles['border_radius'] ?? 0) * $unit).'pt' }};"></div>
        @elseif(in_array($type, ['logo', 'image', 'background', 'stamp', 'watermark'], true))
            <div class="element asset" style="{{ $position }}">
                @if($asset)<img src="{{ $asset }}" alt="{{ $element['name'] ?? '' }}">@elseif($type === 'watermark')<span style="font-size:48pt;color:{{ $primary }};">{{ $element['watermark_text'] ?? 'UMSA' }}</span>@endif
            </div>
        @elseif(in_array($type, ['signature', 'signature_block'], true))
            @php
                $name = $element['content'] ?? $element['authority_name'] ?? $element['name'] ?? '';
                $nameStyles = array_merge($styles, ['font_weight' => 'bold', 'font_size' => $styles['font_size'] ?? 11.5, 'min_font_size' => 9]);
                $nameSize = $fitFont($name, $nameStyles, $width * 72 / 25.4, $height * .25 * 72 / 25.4);
            @endphp
            <div class="element signature" style="{{ $position }}">
                <div class="signature-image">@if($asset)<img src="{{ $asset }}" class="{{ str_contains($element['asset_path'] ?? '', '_ft.svg') ? 'original-rubric' : '' }}" alt="Firma">@endif</div><div class="rule"></div>
                <div class="name" style="font-size:{{ $nameSize }}pt;">{{ $name }}</div>
                <div class="position">{{ $element['subtitle'] ?? $element['authority_title'] ?? $element['position'] ?? '' }}</div>
                <div class="institution">{{ $element['institution'] ?? 'Facultad de Tecnología · UMSA' }}</div>
            </div>
        @elseif(in_array($type, ['qr', 'qr_code'], true))
            <div class="element verification" style="{{ $position }}"><div class="qr-image">@if(!empty($qr_base64))<img src="{{ $qr_base64 }}" alt="QR de verificación">@endif</div>
                <div class="code">{{ $certificate->certificate_code }}</div><div class="validity">{{ $permanent ? 'VIGENCIA PERMANENTE' : 'VERIFICAR EN LÍNEA' }}</div>
            </div>
        @elseif(in_array($type, ['divider', 'decorative_line', 'line'], true))
            <div class="element" style="{{ $position }}height:{{ (float) ($styles['height'] ?? 1) * $unit }}pt;background:{{ $styles['background'] ?? $secondary }};"></div>
        @else
            @php
                $text = (string) ($element['resolved_content'] ?? $element['content'] ?? '');
                $fontSize = $fitFont($text, $styles, $width * 72 / 25.4, $height * 72 / 25.4);
            @endphp
            <div class="element text" style="{{ $position }}font-size:{{ $fontSize }}pt;font-family:'{{ $styles['font_family'] ?? $baseFont }}','DejaVu Serif',serif;font-weight:{{ $styles['font_weight'] ?? 'normal' }};font-style:{{ $styles['font_style'] ?? 'normal' }};text-align:{{ $styles['text_align'] ?? 'center' }};color:{{ $styles['text_color'] ?? $schema['text_color'] ?? '#1e293b' }};line-height:{{ $styles['line_height'] ?? 1.3 }};letter-spacing:{{ (float) ($styles['letter_spacing'] ?? 0) * $unit }}pt;text-transform:{{ $styles['text_transform'] ?? 'none' }};{{ !empty($styles['border_bottom']) ? 'border-bottom:.7pt solid '.($styles['border_bottom_color'] ?? $secondary).';' : '' }}">{!! nl2br(e($text)) !!}</div>
        @endif
    @endforeach
</div></body></html>
