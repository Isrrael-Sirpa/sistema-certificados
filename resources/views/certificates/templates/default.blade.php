<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Certificado - {{ $certificate->certificate_code }}</title>
    <style>
        @page {
            margin: 0;
            size: a4 landscape;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'Helvetica', 'DejaVu Sans', sans-serif;
            color: #1e293b;
            background-color: #ffffff;
            width: 100%;
            height: 100%;
            padding: 18mm;
        }
        
        .border-outer {
            position: absolute;
            top: 10mm;
            left: 10mm;
            right: 10mm;
            bottom: 10mm;
            border: 3px solid #024966;
            border-radius: 4px;
        }
        
        .border-inner {
            position: absolute;
            top: 13mm;
            left: 13mm;
            right: 13mm;
            bottom: 13mm;
            border: 1px solid #d4af37;
            padding: 10mm 15mm;
            text-align: center;
        }

        .watermark {
            position: absolute;
            top: 35%;
            left: 25%;
            width: 50%;
            opacity: 0.03;
            text-align: center;
            font-size: 70px;
            font-weight: bold;
            color: #024966;
            transform: rotate(-25deg);
            z-index: -1;
        }

        .header {
            margin-bottom: 8px;
        }

        .institution-name {
            font-size: 17px;
            font-weight: bold;
            color: #024966;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .faculty-name {
            font-size: 13px;
            font-weight: 600;
            color: #475569;
            letter-spacing: 1px;
            margin-top: 2px;
            text-transform: uppercase;
        }

        .department-name {
            font-size: 14px;
            font-weight: bold;
            color: #b45309;
            letter-spacing: 1.5px;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .divider {
            width: 180px;
            height: 2px;
            background: linear-gradient(to right, #024966, #d4af37, #024966);
            margin: 8px auto 14px auto;
        }

        .award-title {
            font-size: 26px;
            font-weight: bold;
            color: #024966;
            letter-spacing: 3px;
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .award-to {
            font-size: 12px;
            font-style: italic;
            color: #64748b;
            margin-bottom: 4px;
        }

        .beneficiary-name {
            font-size: 23px;
            font-weight: bold;
            color: #0f172a;
            letter-spacing: 0.5px;
            border-bottom: 1.5px solid #cbd5e1;
            display: inline-block;
            padding: 2px 25px;
            margin-bottom: 6px;
            text-transform: uppercase;
        }

        .document-id {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 10px;
        }

        .body-text {
            font-size: 12.5px;
            line-height: 1.55;
            color: #334155;
            max-width: 92%;
            margin: 0 auto 12px auto;
        }

        .activity-title {
            font-weight: bold;
            color: #024966;
        }

        .meta-details {
            font-size: 11.5px;
            color: #475569;
            margin-bottom: 16px;
        }

        .footer-table {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .signature-box {
            width: 38%;
            text-align: center;
            vertical-align: bottom;
            padding-bottom: 5px;
        }

        .signature-line {
            width: 200px;
            border-top: 1px solid #334155;
            margin: 0 auto 4px auto;
        }

        .authority-name {
            font-size: 12px;
            font-weight: bold;
            color: #0f172a;
        }

        .authority-position {
            font-size: 11px;
            color: #64748b;
        }

        .qr-box {
            width: 26%;
            text-align: center;
            vertical-align: middle;
        }

        .qr-image {
            width: 100px;
            height: 100px;
            margin-bottom: 4px;
        }

        .qr-caption {
            font-size: 8px;
            color: #64748b;
            letter-spacing: 0.3px;
        }

        .info-box {
            width: 38%;
            text-align: right;
            vertical-align: bottom;
            padding-bottom: 5px;
            font-size: 10px;
            color: #475569;
            line-height: 1.45;
        }

        .certificate-code {
            font-weight: bold;
            color: #024966;
            font-size: 11px;
        }

        .validity-tag {
            display: inline-block;
            color: #15803d;
            font-weight: bold;
            font-size: 9px;
            background-color: #f0fdf4;
            padding: 1px 6px;
            border-radius: 3px;
            border: 1px solid #bbf7d0;
        }
    </style>
</head>
<body>
    <div class="border-outer"></div>
    <div class="border-inner">
        <div class="watermark">VICEDECANATO</div>

        <div class="header">
            <div class="institution-name">{{ $settings['institution_name'] ?? 'UNIVERSIDAD MAYOR DE SAN ANDRÉS' }}</div>
            <div class="faculty-name">{{ $settings['faculty_name'] ?? 'FACULTAD DE TECNOLOGÍA' }}</div>
            <div class="department-name">{{ $settings['department_name'] ?? 'VICEDECANATO' }}</div>
            <div class="divider"></div>
        </div>

        <div class="award-title">{{ $certificate->certificateType->name ?? 'CERTIFICADO' }}</div>
        <div class="award-to">Otorgado a:</div>
        <div class="beneficiary-name">{{ $certificate->beneficiary->full_title_name }}</div>
        <div class="document-id">Documento de Identidad: <strong>{{ $certificate->beneficiary->formatted_document }}</strong></div>

        <div class="body-text">
            Por su destacada participación en calidad de <strong>{{ strtoupper($certificate->certificateType->name) }}</strong> en la actividad académica:
            <br>
            <span class="activity-title">"{{ $certificate->activity->name }}"</span>
            @if(!empty($certificate->activity->description))
                <br><span style="font-size: 11px; color: #64748b;">{{ $certificate->activity->description }}</span>
            @endif
        </div>

        <div class="meta-details">
            Realizado {{ $certificate->activity->formatted_date_range }}
            @if($certificate->activity->location)
                en {{ $certificate->activity->location }}
            @endif
            @if($certificate->activity->academic_hours > 0)
                con una carga horaria de <strong>{{ $certificate->activity->academic_hours }} horas académicas</strong>.
            @else
                .
            @endif
        </div>

        <table class="footer-table">
            <tr>
                <td class="signature-box">
                    <div style="height: 35px;"></div>
                    <div class="signature-line"></div>
                    <div class="authority-name">{{ $certificate->activity->responsible_authority ?? $settings['authority_name'] ?? 'Ing. Aniceto Velarde Forest' }}</div>
                    <div class="authority-position">{{ $settings['authority_position'] ?? 'Vicedecano de Facultad' }}</div>
                </td>
                <td class="qr-box">
                    <img src="{{ $qr_base64 }}" class="qr-image" alt="QR Verificación" />
                    <div class="qr-caption">Escanee para verificar autenticidad</div>
                </td>
                <td class="info-box">
                    <div>Código: <span class="certificate-code">{{ $certificate->certificate_code }}</span></div>
                    <div>Emisión: {{ \Carbon\Carbon::parse($certificate->issue_date)->translatedFormat('d \d\e F \d\e Y') }}</div>
                    @if($certificate->isPermanent())
                        <div>Vigencia: Documento Permanente</div>
                        <div style="margin-top: 3px;"><span class="validity-tag">✓ Vigencia Vitalicia</span></div>
                    @else
                        <div>Vigencia: Hasta {{ \Carbon\Carbon::parse($certificate->expiration_date)->translatedFormat('d \d\e F \d\e Y') }}</div>
                        <div style="margin-top: 3px;"><span class="validity-tag">✓ Vigencia Temporal</span></div>
                    @endif
                    @if($certificate->hasScore())
                        <div style="margin-top: 3px; font-weight: bold; color: #b45309; font-size: 8.5px;">Calificación: {{ $certificate->formatted_score }}</div>
                    @endif
                    <div style="font-size: 8px; color: #94a3b8; margin-top: 2px;">{{ $settings['institution_city'] ?? 'La Paz - Bolivia' }}</div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
