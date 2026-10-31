@if(($schema['layout_version'] ?? 1) >= 2)
    @include('certificates.templates.canvas', ['font_metrics' => $font_metrics ?? null])
@else
<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificado Oficial - {{ $certificate->certificate_code }}</title>
    <style>
        @page {
            margin: 0;
            size: a4 {{ $orientation ?? 'landscape' }};
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: {{ $schema['font_family'] ?? 'DejaVu Serif' }}, 'DejaVu Serif', serif;
            color: {{ $schema['text_color'] ?? '#1e293b' }};
            background-color: #ffffff;
            width: 100%;
            height: 100%;
            padding: 16mm;
            position: relative;
        }

        /* Border Styles */
        .border-outer {
            position: absolute;
            top: 7mm;
            left: 7mm;
            @if(($orientation ?? 'landscape') === 'portrait')
                width: 196mm;
                height: 283mm;
            @else
                width: 283mm;
                height: 196mm;
            @endif
            @if(($schema['border_style'] ?? '') === 'ornamental-double')
                border: 3px double {{ $schema['secondary_color'] ?? '#d4af37' }};
            @elseif(($schema['border_style'] ?? '') === 'gold-filigree')
                border: 4px solid {{ $schema['secondary_color'] ?? '#d4af37' }};
            @elseif(($schema['border_style'] ?? '') === 'none')
                border: none;
            @else
                border: 2px solid {{ $schema['primary_color'] ?? '#024966' }};
            @endif
            border-radius: 4px;
        }

        .border-inner {
            position: absolute;
            top: 10mm;
            left: 10mm;
            @if(($orientation ?? 'landscape') === 'portrait')
                width: 190mm;
                height: 277mm;
            @else
                width: 277mm;
                height: 190mm;
            @endif
            @if(($schema['border_style'] ?? '') === 'ornamental-double')
                border: 1px solid {{ $schema['primary_color'] ?? '#024966' }};
            @elseif(($schema['border_style'] ?? '') === 'gold-filigree')
                border: 1px dashed {{ $schema['secondary_color'] ?? '#d4af37' }};
            @elseif(($schema['border_style'] ?? '') === 'none')
                border: none;
            @else
                border: 1px solid {{ $schema['secondary_color'] ?? '#d4af37' }};
            @endif
            @if(!empty($schema['elements']) && count($schema['elements']) > 0)
                padding: 0;
            @else
                padding: 8mm 12mm;
            @endif
            text-align: center;
        }

        /* Watermark */
        .watermark {
            position: absolute;
            top: 35%;
            left: 15%;
            width: 70%;
            opacity: {{ $schema['watermark_opacity'] ?? 0.04 }};
            text-align: center;
            font-size: 55px;
            font-weight: bold;
            font-family: 'DejaVu Serif', serif;
            color: {{ $schema['primary_color'] ?? '#024966' }};
            transform: rotate(-25deg);
            z-index: 1;
        }

        /* Watermark Image */
        .watermark-img-container {
            position: absolute;
            top: 25%;
            left: 25%;
            width: 50%;
            height: 50%;
            text-align: center;
            opacity: {{ $schema['watermark_opacity'] ?? 0.07 }};
            z-index: 1;
            pointer-events: none;
        }

        .watermark-img {
            max-width: 270px;
            max-height: 270px;
            object-fit: contain;
            margin: 0 auto;
            display: block;
        }

        /* Header & Logos */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            position: relative;
            z-index: 10;
        }

        .header-logo-left {
            width: 18%;
            text-align: left;
            vertical-align: middle;
        }

        .header-text-center {
            width: 64%;
            text-align: center;
            vertical-align: middle;
        }

        .header-logo-right {
            width: 18%;
            text-align: right;
            vertical-align: middle;
        }

        .logo-img {
            max-height: 58px;
            max-width: 90px;
        }

        .institution-title {
            font-size: 15px;
            font-weight: bold;
            color: {{ $schema['primary_color'] ?? '#024966' }};
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .faculty-title {
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            letter-spacing: 1px;
            margin-top: 1px;
            text-transform: uppercase;
        }

        .department-title {
            font-size: 12.5px;
            font-weight: bold;
            color: {{ $schema['accent_color'] ?? '#b45309' }};
            letter-spacing: 1.5px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .divider {
            width: 160px;
            height: 2px;
            background: linear-gradient(to right, {{ $schema['primary_color'] ?? '#024966' }}, {{ $schema['secondary_color'] ?? '#d4af37' }}, {{ $schema['primary_color'] ?? '#024966' }});
            margin: 6px auto 10px auto;
        }

        /* Award Title & Beneficiary */
        .award-type {
            font-size: 22px;
            font-weight: 900;
            color: {{ $schema['primary_color'] ?? '#024966' }};
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 4px;
            position: relative;
            z-index: 10;
        }

        .award-intro {
            font-size: 11px;
            font-style: italic;
            color: #64748b;
            margin-bottom: 4px;
            position: relative;
            z-index: 10;
        }

        .beneficiary-name {
            font-size: 21px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            border-bottom: 2px solid {{ $schema['secondary_color'] ?? '#d4af37' }};
            display: inline-block;
            padding: 2px 25px;
            margin-bottom: 4px;
            text-transform: uppercase;
            position: relative;
            z-index: 10;
        }

        .document-id {
            font-size: 10.5px;
            color: #64748b;
            margin-bottom: 8px;
            position: relative;
            z-index: 10;
        }

        .body-text {
            font-size: 12px;
            line-height: 1.5;
            color: {{ $schema['text_color'] ?? '#334155' }};
            max-width: 92%;
            margin: 0 auto 10px auto;
            position: relative;
            z-index: 10;
        }

        /* Footer & Signatures */
        .footer-table {
            width: 100%;
            margin-top: 8px;
            border-collapse: collapse;
            position: relative;
            z-index: 10;
        }

        .signatures-cell {
            vertical-align: bottom;
            text-align: center;
        }

        .signature-item {
            display: inline-block;
            width: 190px;
            margin: 0 10px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-img {
            max-height: 38px;
            max-width: 160px;
            margin-bottom: 2px;
        }

        .signature-line {
            width: 170px;
            border-top: 1px solid #334155;
            margin: 0 auto 3px auto;
        }

        .authority-name {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
            line-height: 1.2;
        }

        .authority-position {
            font-size: 9.5px;
            color: #64748b;
            line-height: 1.2;
        }

        .authority-inst {
            font-size: 8.5px;
            color: #94a3b8;
        }

        .stamp-cell {
            width: 18%;
            text-align: center;
            vertical-align: middle;
        }

        .stamp-img {
            max-width: 85px;
            max-height: 85px;
            opacity: 0.9;
        }

        .qr-cell {
            width: 25%;
            text-align: right;
            vertical-align: bottom;
            padding-bottom: 2px;
        }

        .qr-img {
            width: 95px;
            height: 95px;
            margin-bottom: 3px;
        }

        .meta-info {
            font-size: 8px;
            color: #64748b;
            line-height: 1.35;
        }

        .cert-code {
            font-weight: bold;
            color: {{ $schema['primary_color'] ?? '#024966' }};
            font-size: 9px;
        }

        .validity-tag {
            color: #15803d;
            font-weight: bold;
            font-size: 7.5px;
            background-color: #f0fdf4;
            padding: 1px 4px;
            border-radius: 2px;
            border: 0.5px solid #bbf7d0;
            display: inline-block;
            margin-top: 2px;
        }
    </style>
</head>
<body>
    <div class="border-outer"></div>
    <div class="border-inner">
        
        @if(!empty($corners_base64) && ($schema['border_style'] ?? '') !== 'none')
            @if(!empty($corners_base64['tl']))
                <img src="{{ $corners_base64['tl'] }}" style="position: absolute; top: -3mm; left: -3mm; width: 22mm; height: 22mm; z-index: 2; pointer-events: none;" alt="esquinero">
            @endif
            @if(!empty($corners_base64['tr']))
                <img src="{{ $corners_base64['tr'] }}" style="position: absolute; top: -3mm; right: -3mm; width: 22mm; height: 22mm; z-index: 2; pointer-events: none;" alt="esquinero">
            @endif
            @if(!empty($corners_base64['bl']))
                <img src="{{ $corners_base64['bl'] }}" style="position: absolute; bottom: -3mm; left: -3mm; width: 22mm; height: 22mm; z-index: 2; pointer-events: none;" alt="esquinero">
            @endif
            @if(!empty($corners_base64['br']))
                <img src="{{ $corners_base64['br'] }}" style="position: absolute; bottom: -3mm; right: -3mm; width: 22mm; height: 22mm; z-index: 2; pointer-events: none;" alt="esquinero">
            @endif
        @endif

        @if(!empty($schema['elements']) && count($schema['elements']) > 0)
            @php
                $documentHasScore = ($certificate->score !== null || filled($certificate->score_label))
                    && ($certificate->activity?->certificateConfig?->show_score_on_pdf ?? true);
                $documentIsPermanent = $certificate->isPermanent();
            @endphp
            <!-- ============================================== -->
            <!-- DYNAMIC ELEMENT-BASED CANVAS ENGINE            -->
            <!-- ============================================== -->
            @foreach($schema['elements'] as $el)
                @continue(isset($el['is_visible']) && !$el['is_visible'])
                @continue(!match ($el['visibility_rule'] ?? 'always') {
                    'with_score' => $documentHasScore,
                    'permanent' => $documentIsPermanent,
                    'temporary' => !$documentIsPermanent,
                    default => true,
                })

                @if($el['type'] === 'watermark')
                    @if(($el['watermark_type'] ?? '') === 'image' && !empty($el['asset_base64']))
                        <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: 1; opacity: {{ $el['opacity'] ?? 0.06 }}; text-align: center; pointer-events: none;">
                            <img src="{{ $el['asset_base64'] }}" style="max-width: 100%; max-height: 100%; width: auto; height: auto; margin: 0 auto; display: block;">
                        </div>
                    @elseif(!empty($watermark_image_base64))
                        <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: 1; opacity: {{ $el['opacity'] ?? 0.06 }}; text-align: center; pointer-events: none;">
                            <img src="{{ $watermark_image_base64 }}" style="max-width: 100%; max-height: 100%; width: auto; height: auto; margin: 0 auto; display: block;">
                        </div>
                    @else
                        <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: 1; opacity: {{ $el['opacity'] ?? 0.05 }}; text-align: center; transform: rotate({{ $el['rotation'] ?? -25 }}deg); font-size: 45px; font-weight: bold; color: {{ $schema['primary_color'] ?? '#024966' }}; pointer-events: none; font-family: 'DejaVu Serif', serif;">
                            {{ $el['watermark_text'] ?? 'UMSA' }}
                        </div>
                    @endif
                @elseif($el['type'] === 'logo' || $el['type'] === 'image')
                    <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; opacity: {{ $el['opacity'] ?? 1.0 }}; text-align: center;">
                        @if(!empty($el['asset_base64']))
                            <img src="{{ $el['asset_base64'] }}" style="max-width: 100%; max-height: 100%; width: auto; height: auto; margin: 0 auto; display: block;" alt="{{ $el['name'] ?? '' }}">
                        @endif
                    </div>
                @elseif($el['type'] === 'text')
                    <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; text-align: {{ $el['text_align'] ?? 'center' }}; color: {{ $el['text_color'] ?? '#1e293b' }}; font-family: {{ $el['font_family'] ?? $schema['font_family'] ?? 'DejaVu Serif' }}, 'DejaVu Serif', serif; font-size: {{ $el['font_size'] ?? 12 }}px; font-weight: {{ $el['font_weight'] ?? 'normal' }}; font-style: {{ $el['font_style'] ?? 'normal' }}; line-height: {{ $el['line_height'] ?? 1.3 }}; letter-spacing: {{ $el['letter_spacing'] ?? 0 }}px; {{ !empty($el['border_bottom']) ? 'border-bottom: 2px solid ' . ($el['border_bottom_color'] ?? '#d4af37') . ';' : '' }}">
                        {!! nl2br(e($el['resolved_content'] ?? $el['content'] ?? '')) !!}
                    </div>
                @elseif($el['type'] === 'signature')
                    <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; text-align: center;">
                        @if(!empty($el['asset_base64']))
                            <img src="{{ $el['asset_base64'] }}" style="max-height: 38px; max-width: 90%; margin: 0 auto 2px auto; display: block;" alt="Firma">
                        @else
                            <div style="height: 32px;"></div>
                        @endif
                        <div style="width: 80%; border-top: 1px solid #334155; margin: 2px auto 3px auto;"></div>
                        <div style="font-size: 10px; font-weight: bold; color: #0f172a; line-height: 1.2;">{{ $el['authority_name'] ?? '' }}</div>
                        <div style="font-size: 8.5px; color: #64748b; line-height: 1.2;">{{ $el['authority_title'] ?? '' }}</div>
                        @if(!empty($el['institution']))
                            <div style="font-size: 8px; color: #94a3b8;">{{ $el['institution'] }}</div>
                        @endif
                    </div>
                @elseif($el['type'] === 'stamp')
                    <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; opacity: {{ $el['opacity'] ?? 0.95 }}; text-align: center;">
                        @if(!empty($el['asset_base64']))
                            <img src="{{ $el['asset_base64'] }}" style="max-width: 100%; max-height: 100%; width: auto; height: auto; margin: 0 auto; display: block;" alt="Sello Oficial">
                        @endif
                    </div>
                @elseif($el['type'] === 'qr')
                    <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; text-align: center;">
                        @if(($orientation ?? 'landscape') === 'portrait')
                            <!-- Placa Institucional de Validación QR en Vertical -->
                            <table style="width: 100%; border-collapse: collapse; background-color: #f8fafc; border: 0.5px solid #cbd5e1; border-radius: 6px; padding: 6px 8px;">
                                <tr>
                                    <td style="width: 82px; vertical-align: middle; text-align: center;">
                                        @if(!empty($qr_base64))
                                            <img src="{{ $qr_base64 }}" style="width: 78px; height: 78px; display: block; margin: 0 auto;" alt="QR">
                                        @endif
                                    </td>
                                    <td style="vertical-align: middle; text-align: left; padding-left: 8px; line-height: 1.3;">
                                        <div style="font-size: 7.5px; font-weight: bold; color: #475569; letter-spacing: 0.5px;">VERIFICACIÓN DIGITAL UMSA</div>
                                        <div style="font-size: 8.5px; font-family: monospace; font-weight: bold; color: #024966; letter-spacing: 0.3px; margin: 2px 0;">{{ $certificate->certificate_code }}</div>
                                        @if($certificate->isPermanent())
                                            <div style="font-size: 7px; font-weight: bold; color: #047857;">✓ VIGENCIA PERMANENTE</div>
                                        @else
                                            <div style="font-size: 7px; font-weight: bold; color: #047857;">✓ VÁLIDO HASTA {{ \Carbon\Carbon::parse($certificate->expiration_date)->format('d/m/Y') }}</div>
                                        @endif
                                        @if($certificate->hasScore())
                                            <div style="font-size: 7px; font-weight: bold; color: #1e40af;">Nota: {{ $certificate->formatted_score }}</div>
                                        @endif
                                        <div style="font-size: 6.5px; color: #64748b;">Escanee para validar autenticidad</div>
                                    </td>
                                </tr>
                            </table>
                        @else
                            <!-- Placa Compacta en Horizontal -->
                            <div style="display: inline-block; background-color: #ffffff; border: 0.5px solid #cbd5e1; border-radius: 6px; padding: 6px; text-align: center;">
                                @if(!empty($qr_base64))
                                    <img src="{{ $qr_base64 }}" style="width: 95px; height: 95px; margin: 0 auto 3px auto; display: block;" alt="QR">
                                @endif
                                @if(!empty($el['show_code']))
                                    <div style="font-size: 8px; font-family: monospace; font-weight: bold; color: #024966; letter-spacing: 0.3px;">{{ $certificate->certificate_code }}</div>
                                    @if($certificate->isPermanent())
                                        <div style="font-size: 7px; font-weight: bold; color: #047857; margin-top: 1px;">✓ VIGENCIA PERMANENTE</div>
                                    @else
                                        <div style="font-size: 7px; font-weight: bold; color: #047857; margin-top: 1px;">✓ VÁLIDO HASTA {{ \Carbon\Carbon::parse($certificate->expiration_date)->format('d/m/Y') }}</div>
                                    @endif
                                    @if($certificate->hasScore())
                                        <div style="font-size: 7px; font-weight: bold; color: #1e40af; margin-top: 1px;">NOTA: {{ $certificate->formatted_score }}</div>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </div>
                @elseif($el['type'] === 'line')
                    <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; border-top: {{ $el['thickness'] ?? 2 }}px solid {{ $el['color'] ?? '#d4af37' }};"></div>
                @endif
            @endforeach
        @else
            <!-- TRADITIONAL STRUCTURED TEMPLATE (LEGACY FALLBACK) -->
            <!-- Watermark (Gráfica o Textual) -->
            @if(!empty($schema['show_watermark']))
                @if(!empty($watermark_image_base64))
                    <div class="watermark-img-container">
                        <img src="{{ $watermark_image_base64 }}" class="watermark-img" alt="Marca de Agua Institucional">
                    </div>
                @elseif(!empty($schema['watermark_text']))
                    <div class="watermark">{{ $schema['watermark_text'] }}</div>
                @endif
            @endif

            <!-- Header Table with Logos -->
            <table class="header-table">
                <tr>
                    <td class="header-logo-left">
                        @if(!empty($logos_base64['left']))
                            <img src="{{ $logos_base64['left'] }}" class="logo-img" alt="Logo">
                        @endif
                    </td>
                    <td class="header-text-center">
                        <div class="institution-title">{{ $schema['header_title'] ?? $settings['institution_name'] ?? 'UNIVERSIDAD MAYOR DE SAN ANDRÉS' }}</div>
                        <div class="faculty-title">{{ $schema['header_subtitle'] ?? $settings['faculty_name'] ?? 'FACULTAD DE TECNOLOGÍA' }}</div>
                        <div class="department-title">{{ $schema['header_department'] ?? $settings['department_name'] ?? 'VICEDECANATO' }}</div>
                        <div class="divider"></div>
                    </td>
                    <td class="header-logo-right">
                        @if(!empty($logos_base64['right']))
                            <img src="{{ $logos_base64['right'] }}" class="logo-img" alt="Logo">
                        @endif
                    </td>
                </tr>
            </table>

            <!-- Certificate Award Type -->
            <div class="award-type">{{ $certificate_type_name ?? $certificate->certificateType->name ?? 'CERTIFICADO' }}</div>
            <div class="award-intro">{{ $schema['award_intro'] ?? 'Otorgado a:' }}</div>

            <!-- Beneficiary Highlight -->
            <div class="beneficiary-name">{{ $beneficiary_data['name'] ?? $certificate->beneficiary->full_title_name }}</div>
            <div class="document-id">Documento de Identidad: <strong>{{ $beneficiary_data['document'] ?? $certificate->beneficiary->formatted_document }}</strong></div>

            <!-- Interpolated Body Text -->
            <div class="body-text">
                {!! nl2br(e($resolved_body_text)) !!}
            </div>

            @if($certificate->hasScore())
                <div style="margin: 2px auto 8px auto; text-align: center; position: relative; z-index: 10;">
                    <span style="display: inline-block; padding: 3px 14px; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 10.5px; font-weight: bold; color: #1e3a8a;">
                        Calificación Oficial: <span style="color: #024966;">{{ $certificate->formatted_score }}</span>
                    </span>
                </div>
            @endif

            <!-- Footer Signatures, Stamp & QR Table -->
            <table class="footer-table">
                <tr>
                    <!-- Signatures -->
                    <td class="signatures-cell">
                        @foreach($signatures as $sig)
                            <div class="signature-item">
                                @if(!empty($sig['asset_base64']))
                                    <img src="{{ $sig['asset_base64'] }}" class="signature-img" alt="Firma">
                                @else
                                    <div style="height: 35px;"></div>
                                @endif
                                <div class="signature-line"></div>
                                <div class="authority-name">{{ $sig['name'] ?? '' }}</div>
                                <div class="authority-position">{{ $sig['position'] ?? '' }}</div>
                                @if(!empty($sig['institution']))
                                    <div class="authority-inst">{{ $sig['institution'] }}</div>
                                @endif
                            </div>
                        @endforeach
                    </td>

                    <!-- Stamp (Optional) -->
                    @if(!empty($schema['show_stamp']) && !empty($stamp_base64))
                        <td class="stamp-cell">
                            <img src="{{ $stamp_base64 }}" class="stamp-img" alt="Sello Oficial">
                        </td>
                    @endif

                    <!-- QR & Auth Info -->
                    @if(!empty($schema['show_qr']) && !empty($qr_base64))
                        <td class="qr-cell">
                            <img src="{{ $qr_base64 }}" class="qr-img" alt="QR Verificación">
                            <div class="meta-info">
                                <div>Código: <span class="cert-code">{{ $certificate->certificate_code }}</span></div>
                                <div>Emisión: {{ \Carbon\Carbon::parse($certificate->issue_date)->translatedFormat('d/m/Y') }}</div>
                                @if($certificate->isPermanent())
                                    <div><span class="validity-tag">✓ Vigencia Permanente</span></div>
                                @else
                                    <div>Vigencia: Hasta {{ \Carbon\Carbon::parse($certificate->expiration_date)->translatedFormat('d/m/Y') }}</div>
                                    <div><span class="validity-tag">✓ Documento Válido</span></div>
                                @endif
                                @if($certificate->hasScore())
                                    <div style="font-size: 8px; font-weight: bold; color: #1e40af; margin-top: 1px;">Nota: {{ $certificate->formatted_score }}</div>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            </table>
        @endif

    </div>
</body>
</html>
@endif
