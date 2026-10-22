<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vista Previa en Vivo - {{ $template->name ?? 'Plantilla' }}</title>
    <!-- Tailwind CSS v4 / Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@500;700;800;900&family=Inter:wght@300;400;500;600;700&family=Playfair+Display:ital,wght@0,600;0,700;0,900;1,400&family=Montserrat:wght@400;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Source+Serif+4:ital,wght@0,600;0,700;1,400&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
        }
        .canvas-shadow {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.45), 0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; padding: 0 !important; }
            .canvas-container { box-shadow: none !important; margin: 0 !important; width: 100% !important; }
        }
    </style>
</head>
<body class="min-h-screen text-slate-100 p-4 md:p-8 flex flex-col items-center justify-start">

    <!-- Top Action Bar -->
    <header class="no-print w-full max-w-5xl mb-6 flex flex-wrap items-center justify-between gap-4 bg-slate-800/80 backdrop-blur-md p-4 rounded-2xl border border-slate-700/60 shadow-xl">
        <div class="flex items-center gap-3">
            <span class="p-2 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
            </span>
            <div>
                <h1 class="text-base font-bold text-white flex items-center gap-2">
                    {{ $template->name ?? 'Plantilla' }}
                    <span class="text-xs px-2 py-0.5 rounded-full bg-blue-500/20 text-blue-300 border border-blue-500/30 font-medium uppercase">
                        {{ $orientation === 'portrait' ? 'Vertical (A4)' : 'Horizontal (A4)' }}
                    </span>
                    <span class="text-xs px-2 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-medium">
                        Simulación en Vivo
                    </span>
                </h1>
                <p class="text-xs text-slate-400">Renderizado de fidelidad 1:1 con datos simulados y resolución dinámica de variables</p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if(isset($template->id))
                <a href="{{ route('templates.designer', $template) }}" class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 transition-colors inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Volver al Diseñador
                </a>
                <a href="{{ route('templates.preview', $template) }}" target="_blank" class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-600/30 transition-all inline-flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Ver PDF Oficial
                </a>
            @endif
            <button onclick="window.print()" class="px-3.5 py-1.5 text-xs font-semibold rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-200 transition-colors inline-flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Imprimir
            </button>
        </div>
    </header>

    <!-- Canvas Preview Wrapper -->
    <div class="canvas-container w-full max-w-5xl bg-white text-slate-900 rounded-lg canvas-shadow overflow-hidden relative"
         style="{{ $orientation === 'portrait' ? 'aspect-ratio: 210/297; max-width: 680px;' : 'aspect-ratio: 297/210;' }} font-family: {{ $schema['font_family'] ?? 'Helvetica' }}, sans-serif;">

        <!-- Outer & Inner Borders -->
        <div class="absolute inset-2 border-2 rounded-sm pointer-events-none z-10"
             style="border-color: {{ $schema['primary_color'] ?? '#024966' }};"></div>
        <div class="absolute inset-3.5 border border-dashed rounded-sm pointer-events-none z-10"
             style="border-color: {{ $schema['secondary_color'] ?? '#d4af37' }};"></div>

        <!-- Render Content -->
        <div class="w-full h-full {{ (!empty($schema['elements']) && count($schema['elements']) > 0) ? 'p-3.5 relative' : 'p-6 md:p-10 relative flex flex-col justify-between' }} overflow-hidden">
            @if(!empty($schema['elements']) && count($schema['elements']) > 0)
                <!-- Dynamic Element-Based Studio Preview -->
                <div class="relative w-full h-full">
                    @foreach($schema['elements'] as $el)
                        @continue(isset($el['is_visible']) && !$el['is_visible'])

                        @if($el['type'] === 'watermark')
                            @if(($el['watermark_type'] ?? '') === 'image' && !empty($el['asset_base64']))
                                <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: 1; opacity: {{ $el['opacity'] ?? 0.06 }}; text-align: center; pointer-events: none;">
                                    <img src="{{ $el['asset_base64'] }}" style="width: 100%; height: 100%; object-fit: contain;">
                                </div>
                            @else
                                <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: 1; opacity: {{ $el['opacity'] ?? 0.05 }}; text-align: center; transform: rotate({{ $el['rotation'] ?? -25 }}deg); font-size: 40px; font-weight: bold; color: {{ $schema['primary_color'] ?? '#024966' }}; pointer-events: none; font-family: 'Playfair Display', serif;">
                                    {{ $el['watermark_text'] ?? 'VICEDECANATO' }}
                                </div>
                            @endif
                        @elseif($el['type'] === 'logo' || $el['type'] === 'image')
                            <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; opacity: {{ $el['opacity'] ?? 1.0 }}; text-align: center;">
                                @if(!empty($el['asset_base64']))
                                    <img src="{{ $el['asset_base64'] }}" style="width: 100%; height: 100%; object-fit: contain;" alt="{{ $el['name'] ?? '' }}">
                                @elseif(!empty($el['asset_url']))
                                    <img src="{{ $el['asset_url'] }}" style="width: 100%; height: 100%; object-fit: contain;" alt="{{ $el['name'] ?? '' }}">
                                @else
                                    <div class="w-full h-full flex items-center justify-center border border-dashed border-slate-300 rounded text-slate-400 text-xs">Logo</div>
                                @endif
                            </div>
                        @elseif($el['type'] === 'text')
                            <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; text-align: {{ $el['text_align'] ?? 'center' }}; color: {{ $el['text_color'] ?? '#1e293b' }}; font-family: {{ $el['font_family'] ?? $schema['font_family'] ?? 'Helvetica' }}, sans-serif; font-size: {{ $el['font_size'] ?? 12 }}px; font-weight: {{ $el['font_weight'] ?? 'normal' }}; font-style: {{ $el['font_style'] ?? 'normal' }}; line-height: {{ $el['line_height'] ?? 1.3 }}; letter-spacing: {{ $el['letter_spacing'] ?? 0 }}px; {{ !empty($el['border_bottom']) ? 'border-bottom: 2px solid ' . ($el['border_bottom_color'] ?? '#d4af37') . ';' : '' }}">
                                {!! nl2br(e($el['resolved_content'] ?? strtr($el['content'] ?? '', [
                                    '{{beneficiario.nombre}}' => $certificate->beneficiary_name ?? 'Univ. Rodrigo Quispe Mamani',
                                    '{{beneficiario.documento}}' => $certificate->beneficiary_document ?? 'CI: 8493021 LP',
                                    '{{actividad.nombre}}' => $certificate->activity_name ?? 'Seminario Internacional de IA',
                                    '{{actividad.horas}}' => $certificate->academic_hours ?? 40,
                                    '{{actividad.fechas}}' => $certificate->date_range ?? 'Del 15 al 20 de Septiembre de 2026',
                                    '{{certificado.codigo}}' => $certificate->certificate_code ?? 'CERT-2026-000042',
                                    '{{institucion.nombre}}' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
                                    '{{facultad.nombre}}' => 'FACULTAD DE TECNOLOGÍA',
                                    '{{vicedecanato.nombre}}' => 'VICEDECANATO',
                                ]))) !!}
                            </div>
                        @elseif($el['type'] === 'signature')
                            <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; text-align: center;">
                                @if(!empty($el['asset_base64']))
                                    <img src="{{ $el['asset_base64'] }}" style="max-height: 38px; max-width: 90%; margin: 0 auto 2px auto; display: block;" alt="Firma">
                                @elseif(!empty($el['asset_url']))
                                    <img src="{{ $el['asset_url'] }}" style="max-height: 38px; max-width: 90%; margin: 0 auto 2px auto; display: block;" alt="Firma">
                                @else
                                    <div style="height: 32px;"></div>
                                @endif
                                <div style="width: 80%; border-top: 1px solid #334155; margin: 2px auto 3px auto;"></div>
                                <div style="font-size: 10px; font-weight: bold; color: #0f172a; line-height: 1.2;">{{ $el['authority_name'] ?? $certificate->authority_name ?? 'Ing. Aniceto Velarde Forest' }}</div>
                                <div style="font-size: 8.5px; color: #64748b; line-height: 1.2;">{{ $el['authority_title'] ?? $certificate->authority_position ?? 'Vicedecano de Facultad' }}</div>
                            </div>
                        @elseif($el['type'] === 'stamp')
                            <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; opacity: {{ $el['opacity'] ?? 0.9 }}; text-align: center;">
                                @if(!empty($el['asset_base64']))
                                    <img src="{{ $el['asset_base64'] }}" style="width: 100%; height: 100%; object-fit: contain;" alt="Sello Oficial">
                                @elseif(!empty($el['asset_url']))
                                    <img src="{{ $el['asset_url'] }}" style="width: 100%; height: 100%; object-fit: contain;" alt="Sello Oficial">
                                @else
                                    <div class="w-full h-full rounded-full border border-red-500/50 flex items-center justify-center text-[8px] text-red-600 font-bold">SELLO</div>
                                @endif
                            </div>
                        @elseif($el['type'] === 'qr')
                            <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; text-align: center;">
                                @if(!empty($qr_base64))
                                    <img src="{{ $qr_base64 }}" style="width: 65px; height: 65px; margin: 0 auto 2px auto; display: block;" alt="QR">
                                @else
                                    <div class="w-16 h-16 bg-slate-100 border border-slate-300 mx-auto flex items-center justify-center text-[8px] text-slate-500">QR CODE</div>
                                @endif
                                @if(!empty($el['show_code']))
                                    <div style="font-size: 7.5px; font-family: monospace; font-weight: bold; color: #024966; letter-spacing: 0.3px;">{{ $certificate->certificate_code ?? 'VISTA-PREVIA-2026' }}</div>
                                    <div style="font-size: 6.5px; font-weight: bold; color: #047857; margin-top: 1px;">✓ VIGENCIA 3 AÑOS</div>
                                @endif
                            </div>
                        @elseif($el['type'] === 'line')
                            <div style="position: absolute; left: {{ $el['x'] }}%; top: {{ $el['y'] }}%; width: {{ $el['width'] }}%; height: {{ $el['height'] }}%; z-index: {{ $el['z_index'] ?? 10 }}; border-top: {{ $el['thickness'] ?? 2 }}px solid {{ $el['color'] ?? '#d4af37' }};"></div>
                        @endif
                    @endforeach
                </div>
            @else
                <!-- Traditional Layout Fallback -->
                <div class="text-center relative z-20">
                    <h2 class="text-lg md:text-xl font-bold tracking-widest uppercase" style="color: {{ $schema['primary_color'] ?? '#024966' }};">
                        UNIVERSIDAD MAYOR DE SAN ANDRÉS
                    </h2>
                    <h3 class="text-xs md:text-sm font-semibold text-slate-600 uppercase tracking-wider">
                        FACULTAD DE TECNOLOGÍA - VICEDECANATO
                    </h3>
                    <div class="w-24 h-0.5 mx-auto my-3" style="background-color: {{ $schema['secondary_color'] ?? '#d4af37' }};"></div>
                </div>

                <div class="text-center my-auto relative z-20">
                    <h1 class="text-2xl md:text-3xl font-black uppercase tracking-wider" style="color: {{ $schema['primary_color'] ?? '#024966' }};">
                        {{ $certificate->certificate_type ?? 'CERTIFICADO' }}
                    </h1>
                    <p class="text-xs italic text-slate-500 mt-1">Otorgado con distinción a:</p>
                    <div class="text-xl md:text-2xl font-bold uppercase tracking-wide my-2 pb-1 border-b-2 inline-block px-8" style="border-color: {{ $schema['secondary_color'] ?? '#d4af37' }};">
                        {{ $certificate->beneficiary_name ?? 'Univ. Rodrigo Quispe Mamani' }}
                    </div>
                    <p class="text-xs text-slate-500">Documento de Identidad: <strong>{{ $certificate->beneficiary_document ?? 'CI: 8493021 LP' }}</strong></p>
                    
                    <p class="text-xs md:text-sm text-slate-700 max-w-2xl mx-auto mt-4 leading-relaxed">
                        Por su destacada participación y aprobación en el <strong>{{ $certificate->activity_name ?? 'Seminario Internacional de Inteligencia Artificial' }}</strong>, realizado con una carga horaria de <strong>{{ $certificate->academic_hours ?? 40 }} horas académicas</strong>.
                    </p>
                </div>

                <div class="flex items-end justify-between pt-6 border-t border-slate-200 text-center relative z-20">
                    <div class="w-48 text-center">
                        <div class="w-36 border-t border-slate-700 mx-auto pt-1">
                            <p class="text-xs font-bold text-slate-900">{{ $certificate->authority_name ?? 'Ing. Aniceto Velarde Forest' }}</p>
                            <p class="text-[10px] text-slate-500">{{ $certificate->authority_position ?? 'Vicedecano de Facultad' }}</p>
                        </div>
                    </div>
                    <div class="text-center">
                        @if(!empty($qr_base64))
                            <img src="{{ $qr_base64 }}" class="w-16 h-16 mx-auto mb-1" alt="QR">
                        @endif
                        <p class="text-[9px] font-mono text-slate-500">{{ $certificate->certificate_code }}</p>
                        <span class="inline-block px-1.5 py-0.5 rounded text-[8px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">✓ Vigencia 3 Años</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

</body>
</html>
