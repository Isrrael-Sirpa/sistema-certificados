@extends('layouts.app')

@section('page-title', 'Detalle de la Actividad')
@section('page-subtitle', 'Gestión de participantes, calificaciones y emisión de certificados')

@section('content')
<div class="space-y-6" x-data="{
    enrollExcelModal: false,
    manualAddModal: false,
    issueRosterModal: false,
    editParticipantModal: false,
    editingParticipant: { id: null, name: '', doc: '', role: 'Participante', score: '', score_label: '', actionUrl: '' },
    searchAdd: ''
}">

    <!-- Top Navigation & Breadcrumb -->
    <div class="flex flex-wrap items-center justify-between gap-3 pb-1">
        <!-- Breadcrumbs (Left) -->
        <nav aria-label="Breadcrumb" class="flex items-center gap-2 text-xs">
            <a href="{{ route('activities.index') }}" class="text-slate-400 hover:text-brand transition-colors font-medium flex items-center gap-1.5 group">
                <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <span>Actividades Académicas</span>
            </a>
            <span class="text-slate-300">/</span>
            <span class="text-slate-700 font-bold truncate max-w-xs sm:max-w-md">{{ $activity->name }}</span>
        </nav>

        <!-- Back Button (Right) -->
        <a href="{{ route('activities.index') }}"
           class="inline-flex items-center gap-2 px-3.5 py-1.5 text-xs font-semibold text-slate-700 hover:text-brand bg-white hover:bg-slate-50 border border-slate-200/90 hover:border-brand/30 rounded-xl shadow-2xs hover:shadow-xs transition-all group active:scale-95">
            <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-brand group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <span>Volver a Actividades</span>
        </a>
    </div>

    <!-- Activity Header Card -->
    <div class="ft-panel bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <div class="flex flex-wrap items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-brand/10 text-brand border border-brand/20">
                        {{ $activity->activity_type }}
                    </span>

                    @if($activity->certificateType)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider"
                              style="background-color: {{ $activity->certificateType->badge_color ?? '#3b82f6' }}20; color: {{ $activity->certificateType->badge_color ?? '#1e40af' }};">
                            <span>{{ $activity->certificateType->icon ?? '📜' }}</span>
                            <span>{{ $activity->certificateType->name }}</span>
                        </span>
                    @endif

                    @php
                        $isPerm = ($config?->validity_mode ?? ($activity->certificateType?->validity_mode ?? 'permanent')) === 'permanent';
                    @endphp
                    @if($isPerm)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                            ♾️ VIGENCIA VITALICIA
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                            ⏳ VALIDEZ: {{ $config?->validity_years ?? ($activity->certificateType?->default_validity_years ?? 3) }} AÑOS
                        </span>
                    @endif

                    @if($activity->certificateType?->has_score)
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            📊 CALIFICACIONES ACTIVAS
                        </span>
                    @endif

                    @if($activity->status === 'activa')
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                            ● ACTIVA
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                            {{ strtoupper($activity->status) }}
                        </span>
                    @endif
                </div>

                <h3 class="text-xl font-bold text-brand leading-snug">{{ $activity->name }}</h3>
                <p class="text-xs text-slate-500 mt-2">{{ $activity->description ?? 'Sin descripción.' }}</p>

                <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-600">
                    <div>📅 <strong>Fechas:</strong> {{ $activity->formatted_date_range }}</div>
                    <div>⏱️ <strong>Carga:</strong> {{ $activity->academic_hours }} horas académicas</div>
                    <div>📍 <strong>Lugar:</strong> {{ $activity->location ?? 'No especificado' }}</div>
                    <div>🏢 <strong>Unidad:</strong> {{ $activity->organizer ?? 'Vicedecanato' }}</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                @if($activity->certificates->whereNull('revoked_at')->count() > 0)
                    <a href="{{ route('activities.download-zip', $activity) }}"
                       class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5"
                       title="Descargar todos los certificados vigentes en archivo comprimido ZIP">
                        <span>📦 Descargar ZIP</span>
                    </a>
                @endif

                <a href="{{ route('activities.edit', $activity) }}"
                   class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all flex items-center gap-1.5"
                   title="Editar datos de la actividad">
                    <span>✏️</span>
                    <span>Editar Actividad</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Activity Certificate Template & Customization Section -->
    <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden"
         x-data="{
            configOpen: false,
            typesData: {{ Js::from($types->keyBy('id')) }},
            selectedTypeId: '{{ old('certificate_type_id', $activity->certificate_type_id ?? $types->first()?->id) }}',
            validityMode: '{{ old('validity_mode', $config?->validity_mode ?? ($activity->certificateType?->validity_mode ?? 'permanent')) }}',
            validityYears: {{ old('validity_years', $config?->validity_years ?? ($activity->certificateType?->default_validity_years ?? 3)) }},
            showScorePdf: {{ old('show_score_on_pdf', $config?->show_score_on_pdf ?? true) ? 'true' : 'false' }},
            showScorePortal: {{ old('show_score_on_portal', $config?->show_score_on_portal ?? true) ? 'true' : 'false' }},
            selectedTemplateId: '{{ old('certificate_template_id', $config?->certificate_template_id ?? '') }}',
            customTitle: '{{ old('custom_title', $config?->custom_title ?? '') }}',
            modality: '{{ old('modality', $config?->custom_fields_schema['modalidad'] ?? 'Presencial') }}',
            resolutionNumber: '{{ old('resolution_number', $config?->custom_fields_schema['resolucion'] ?? '') }}',

            get currentType() {
                return this.typesData[this.selectedTypeId] || null;
            },

            get isPermanent() {
                return this.validityMode === 'permanent';
            },

            onTypeSelect(id) {
                this.selectedTypeId = id;
                const tp = this.typesData[id];
                if (tp) {
                    this.validityMode = tp.validity_mode || 'permanent';
                    this.validityYears = tp.default_validity_years || 3;
                }
            }
         }">

        <div class="p-5 bg-brand text-white flex items-center justify-between cursor-pointer select-none"
             @click="configOpen = !configOpen">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-xl font-bold border border-white/15">
                    ⚙️
                </div>
                <div>
                    <h4 class="text-sm font-bold flex flex-wrap items-center gap-2">
                        <span>Configuración, Diseño y Personalización de Certificado</span>
                        @if($activity->certificateType)
                            <span class="bg-blue-400/20 text-blue-200 border border-blue-400/40 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                Tipo: {{ $activity->certificateType->name }}
                            </span>
                        @endif
                        @if($config && $config->template)
                            <span class="bg-amber-400/20 text-amber-300 border border-amber-400/40 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                Plantilla: {{ $config->template->name }}
                            </span>
                        @endif
                    </h4>
                    <p class="text-xs text-slate-300 mt-0.5">Defina el tipo de certificado, esquema de vigencia (vitalicio o temporal), notas y diseño visual</p>
                </div>
            </div>

            <button type="button" class="text-xs font-semibold px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-xl transition-all border border-white/10 shrink-0">
                <span x-text="configOpen ? 'Ocultar Parámetros ▲' : 'Ver / Ajustar Parámetros ▼'"></span>
            </button>
        </div>

        <div x-show="configOpen" class="p-6 space-y-6 border-t border-slate-200" style="display: none;">
            <form method="POST" action="{{ route('activities.certificate-config', $activity) }}" class="space-y-6">
                @csrf
                <input type="hidden" name="certificate_type_id" :value="selectedTypeId">
                <input type="hidden" name="certificate_template_id" :value="selectedTemplateId">
                <input type="hidden" name="modality" :value="modality">

                <!-- 1. Tipo de Certificado Selector -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        1. Tipo de Certificado Oficial para esta Actividad
                    </label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                        @foreach($types as $tp)
                            <div @click="onTypeSelect('{{ $tp->id }}')"
                                 :class="selectedTypeId == '{{ $tp->id }}' ? 'border-brand bg-brand/5 ring-2 ring-brand/30 shadow-xs' : 'border-slate-200 hover:border-slate-300 bg-white'"
                                 class="p-4 rounded-xl border-2 transition-all cursor-pointer flex flex-col justify-between group">
                                <div>
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="text-2xl">{{ $tp->icon ?? '📜' }}</span>
                                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase transition-all"
                                              style="background-color: {{ $tp->badge_color ?? '#e2e8f0' }}20; color: {{ $tp->badge_color ?? '#475569' }};"
                                              x-text="selectedTypeId == '{{ $tp->id }}' ? (isPermanent ? '♾️ Vitalicio' : '⏳ Temporal') : '{{ $tp->validity_mode === 'permanent' ? '♾️ Vitalicio' : '⏳ Temporal' }}'">
                                            {{ $tp->validity_mode === 'permanent' ? '♾️ Vitalicio' : '⏳ Temporal' }}
                                        </span>
                                    </div>
                                    <h5 class="text-xs font-bold text-slate-900 group-hover:text-brand">{{ $tp->name }}</h5>
                                    <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">{{ $tp->description }}</p>
                                </div>

                                <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                    @if($tp->has_score)
                                        <span class="text-amber-700 font-semibold text-[10px]">📊 Con Calificación</span>
                                    @else
                                        <span class="text-slate-400 text-[10px]">Acreditación directa</span>
                                    @endif
                                    <span x-show="selectedTypeId == '{{ $tp->id }}'" class="text-brand font-bold text-xs">✓ Asignado</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 2. Esquema de Vigencia y Opciones de Calificación -->
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-5">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            2. Esquema de Validez y Caducidad
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-3 bg-white rounded-xl border cursor-pointer hover:border-slate-300"
                                   :class="validityMode === 'permanent' ? 'border-brand ring-2 ring-brand/20 bg-brand/5' : 'border-slate-200'">
                                <input type="radio" name="validity_mode" value="permanent" x-model="validityMode" class="mt-0.5 text-brand focus:ring-brand">
                                <div>
                                    <div class="flex items-center gap-1.5 font-bold text-xs text-slate-900">
                                        <span>♾️</span> Vigencia Permanente / Vitalicia
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">El certificado no vence jamás. Solo registrará la fecha de emisión.</p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 bg-white rounded-xl border cursor-pointer hover:border-slate-300"
                                   :class="validityMode === 'temporal' ? 'border-brand ring-2 ring-brand/20 bg-brand/5' : 'border-slate-200'">
                                <input type="radio" name="validity_mode" value="temporal" x-model="validityMode" class="mt-0.5 text-brand focus:ring-brand">
                                <div>
                                    <div class="flex items-center gap-1.5 font-bold text-xs text-slate-900">
                                        <span>⏳</span> Con Vencimiento Temporal
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">El certificado expirará luego del período configurado.</p>
                                </div>
                            </label>
                        </div>

                        <div x-show="validityMode === 'temporal'" class="mt-3 p-3.5 bg-white rounded-xl border border-slate-200 flex flex-wrap items-center gap-4">
                            <span class="text-xs font-bold text-slate-700">Duración:</span>
                            <div class="flex flex-wrap items-center gap-3">
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="validity_years" value="1" x-model.number="validityYears" class="text-brand">
                                    <span>1 Año</span>
                                </label>
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="validity_years" value="2" x-model.number="validityYears" class="text-brand">
                                    <span>2 Años</span>
                                </label>
                                <label class="flex items-center gap-1 text-xs cursor-pointer font-bold text-brand">
                                    <input type="radio" name="validity_years" value="3" x-model.number="validityYears" class="text-brand">
                                    <span>3 Años (Estándar UMSA)</span>
                                </label>
                                <label class="flex items-center gap-1 text-xs cursor-pointer">
                                    <input type="radio" name="validity_years" value="5" x-model.number="validityYears" class="text-brand">
                                    <span>5 Años</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Calificaciones y Notas (Visibilidad) -->
                    <div x-show="currentType && currentType.has_score" class="pt-4 border-t border-slate-200 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📊</span>
                            <h5 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Configuración de Calificaciones y Puntajes</h5>
                        </div>
                        <p class="text-xs text-slate-500">
                            Esta actividad admite notas individuales asignadas por Excel o manualmente. Controle la visibilidad:
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <label class="flex items-center gap-2.5 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer">
                                <input type="checkbox" name="show_score_on_pdf" value="1" x-model="showScorePdf" class="rounded text-brand focus:ring-brand">
                                <span class="text-xs font-medium text-slate-800">Mostrar nota / mención en el PDF impreso</span>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer">
                                <input type="checkbox" name="show_score_on_portal" value="1" x-model="showScorePortal" class="rounded text-brand focus:ring-brand">
                                <span class="text-xs font-medium text-slate-800">Mostrar nota en el portal público de verificación QR</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 3. Galería Visual de Plantillas -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        3. Plantilla Institucional de Diseño
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div @click="selectedTemplateId = ''"
                             :class="selectedTemplateId === '' ? 'border-brand bg-brand/5 ring-2 ring-brand/30' : 'border-slate-200 hover:border-slate-300 bg-white'"
                             class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between relative group">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <div class="w-10 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-base border border-slate-200">
                                    🏛️
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-slate-100 text-slate-700">
                                    Por Defecto
                                </span>
                            </div>
                            <div>
                                <h5 class="text-xs font-bold text-slate-900 group-hover:text-brand">Plantilla Predeterminada FT-UMSA</h5>
                                <p class="text-[11px] text-slate-500 mt-1">Utiliza la plantilla predeterminada del sistema para la Facultad.</p>
                            </div>
                            <div class="mt-4 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Automático</span>
                                <span x-show="selectedTemplateId === ''" class="text-brand font-bold">✓ Seleccionada</span>
                            </div>
                        </div>

                        @foreach($templates as $tpl)
                            <div @click="selectedTemplateId = '{{ $tpl->id }}'"
                                 :class="selectedTemplateId == '{{ $tpl->id }}' ? 'border-brand bg-brand/5 ring-2 ring-brand/30' : 'border-slate-200 hover:border-slate-300 bg-white'"
                                 class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between relative group">
                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="w-10 h-8 rounded-lg bg-brand/10 text-brand flex items-center justify-center font-bold text-xs border border-brand/20">
                                        {{ $tpl->orientation === 'portrait' ? '📱 VERT' : '🖥️ HORIZ' }}
                                    </div>
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $tpl->orientation === 'portrait' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                        {{ strtoupper($tpl->orientation === 'portrait' ? 'Vertical' : 'Horizontal') }}
                                    </span>
                                </div>
                                <div>
                                    <h5 class="text-xs font-bold text-slate-900 group-hover:text-brand">{{ $tpl->name }}</h5>
                                    <p class="text-[11px] text-slate-500 mt-1">{{ $tpl->description ?? 'Diseño institucional formal de la Facultad de Tecnología.' }}</p>
                                </div>
                                <div class="mt-4 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                    <span class="text-slate-400 font-mono text-[10px]">{{ $tpl->orientation === 'portrait' ? '210 x 297 mm' : '297 x 210 mm' }}</span>
                                    <span x-show="selectedTemplateId == '{{ $tpl->id }}'" class="text-brand font-bold">✓ Seleccionada</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 4. Título Especial y Modalidad -->
                <div class="pt-4 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            4. Título Especial del Certificado (Opcional)
                        </label>
                        <input type="text"
                               name="custom_title"
                               x-model="customTitle"
                               placeholder="Ej: CERTIFICADO DE APROBACIÓN CON MENCIÓN DE HONOR"
                               class="w-full py-2.5 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            5. Modalidad y Resolución
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" @click="modality = 'Presencial'"
                                    :class="modality === 'Presencial' ? 'border-brand bg-brand text-white font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                    class="py-2 px-2 border rounded-xl text-xs transition-all text-center">
                                🏢 Presencial
                            </button>
                            <button type="button" @click="modality = 'Virtual'"
                                    :class="modality === 'Virtual' ? 'border-brand bg-brand text-white font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                    class="py-2 px-2 border rounded-xl text-xs transition-all text-center">
                                🌐 Virtual
                            </button>
                            <button type="button" @click="modality = 'Semipresencial'"
                                    :class="modality === 'Semipresencial' ? 'border-brand bg-brand text-white font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                    class="py-2 px-2 border rounded-xl text-xs transition-all text-center">
                                🔀 Mixto
                            </button>
                        </div>
                        <div class="pt-1">
                            <input type="text" name="resolution_number" x-model="resolutionNumber" placeholder="N° Resolución (Ej: Resol. Fac. N° 451/2026)"
                                   class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-4 border-t border-slate-100">
                    <button type="submit"
                            class="px-6 py-2.5 bg-brand hover:bg-brand-deep text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-2">
                        <span>💾 Guardar Configuración y Políticas de la Actividad</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Participants Roster Section -->
    @php
        $hasScoreType = (bool) ($activity->certificateType?->has_score);
        $minPassingScore = (float) ($activity->certificateType?->min_passing_score ?? 51.0);

        $totalCount = $participants->count();
        $certifiedCount = $participants->filter(fn ($p) => $p->pivot->status === 'certificado' || isset($issuedCertificatesByBenId[$p->id]))->count();

        if ($hasScoreType) {
            $failingParticipants = $participants->filter(fn ($p) => ! isset($issuedCertificatesByBenId[$p->id]) && $p->pivot->status !== 'certificado' && $p->pivot->score !== null && (float) $p->pivot->score < $minPassingScore);
            $unscoredParticipants = $participants->filter(fn ($p) => ! isset($issuedCertificatesByBenId[$p->id]) && $p->pivot->status !== 'certificado' && $p->pivot->score === null);
            $eligibleParticipants = $participants->filter(fn ($p) => ! isset($issuedCertificatesByBenId[$p->id]) && $p->pivot->status !== 'certificado' && $p->pivot->score !== null && (float) $p->pivot->score >= $minPassingScore);

            $pendingCount = $eligibleParticipants->count();
            $failingCount = $failingParticipants->count();
            $unscoredCount = $unscoredParticipants->count();
        } else {
            $eligibleParticipants = $participants->filter(fn ($p) => ! isset($issuedCertificatesByBenId[$p->id]) && $p->pivot->status !== 'certificado');
            $pendingCount = $eligibleParticipants->count();
            $failingCount = 0;
            $unscoredCount = 0;
        }
    @endphp

    <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl">👥</span>
                    <h4 class="text-sm font-bold text-brand uppercase tracking-wider">Nómina de Participantes de esta Actividad</h4>
                </div>
                <p class="text-xs text-slate-500 mt-1 flex flex-wrap items-center gap-2">
                    <span>Total: <strong class="text-slate-800">{{ $totalCount }}</strong> participante(s) registrados</span> ·
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                        {{ $pendingCount }} {{ $hasScoreType ? 'aprobados pendientes' : 'pendientes de emitir' }}
                    </span> ·
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                        {{ $certifiedCount }} certificados
                    </span>
                    @if($hasScoreType && $failingCount > 0)
                        ·
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                            <span>🚫</span> {{ $failingCount }} reprobados (&lt; {{ $minPassingScore }} pts)
                        </span>
                    @endif
                    @if($hasScoreType && $unscoredCount > 0)
                        ·
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700">
                            <span>⏳</span> {{ $unscoredCount }} sin nota
                        </span>
                    @endif
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button"
                        @click="enrollExcelModal = true"
                        class="px-3.5 py-2 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                    <span>📂 Cargar Nómina por Excel</span>
                </button>

                <button type="button"
                        @click="manualAddModal = true"
                        class="px-3.5 py-2 bg-brand hover:bg-brand-deep text-white text-xs font-semibold rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                    <span>+ Agregar del Padrón</span>
                </button>

                @if($pendingCount > 0)
                    <button type="button"
                            @click="issueRosterModal = true"
                            class="px-4 py-2 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5 animate-pulse">
                        <span>⚡ Emitir a {{ $hasScoreType ? 'Aprobados' : 'Pendientes' }} ({{ $pendingCount }})</span>
                    </button>
                @elseif($certifiedCount === $totalCount && $totalCount > 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                        <span>✓</span> Nómina al día (Todos certificados)
                    </span>
                @elseif($hasScoreType && $failingCount > 0 && $pendingCount === 0)
                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                        <span>🚫</span> Sin aprobados pendientes ({{ $failingCount }} reprobados)
                    </span>
                @endif
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3 w-12 text-center">#</th>
                        <th class="px-5 py-3">CI / Documento</th>
                        <th class="px-5 py-3">Participante / Estudiante</th>
                        <th class="px-5 py-3">Carrera / Unidad</th>
                        <th class="px-5 py-3">Calificación / Nota</th>
                        <th class="px-5 py-3">Estado del Certificado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($participants as $index => $part)
                        @php
                            $cert = $issuedCertificatesByBenId[$part->id] ?? null;
                            $isCertified = $part->pivot->status === 'certificado' || $cert !== null;
                            $hasScore = $part->pivot->score !== null || !empty($part->pivot->score_label);
                            $isFailing = $hasScoreType && $part->pivot->score !== null && (float)$part->pivot->score < $minPassingScore;
                            $isUnscored = $hasScoreType && $part->pivot->score === null;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors {{ $isFailing ? 'bg-red-50/30' : '' }}">
                            <td class="px-5 py-3 text-center text-slate-400 font-mono text-[10px]">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-5 py-3 font-mono font-bold text-slate-800">
                                {{ $part->document_number }}
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900">
                                {{ $part->full_title_name }}
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[10px] text-slate-400">{{ $part->email ?? 'Sin correo' }}</span>
                                    @if($part->pivot->role && $part->pivot->role !== 'Participante')
                                        <span class="px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded text-[9px] font-bold">{{ $part->pivot->role }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $part->institution_or_department ?? 'Facultad de Tecnología' }}
                            </td>
                            <td class="px-5 py-3">
                                @if($hasScore)
                                    @if($isFailing)
                                        <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-red-100 border border-red-200 text-red-800 font-bold text-xs" title="Nota reprobatoria (< {{ $minPassingScore }} pts)">
                                            <span>❌</span>
                                            <span>{{ $part->pivot->formatted_score }}</span>
                                            <span class="text-[9px] font-semibold text-red-600 ml-0.5">(Reprobado)</span>
                                        </div>
                                    @else
                                        <div class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 font-bold text-xs" title="Aprobado (&ge; {{ $minPassingScore }} pts)">
                                            <span>✓</span>
                                            <span>{{ $part->pivot->formatted_score }}</span>
                                            <span class="text-[9px] font-semibold text-emerald-700 ml-0.5">(Aprobado)</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-slate-400 italic text-[11px]">Sin nota</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($isCertified && $cert)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        🟢 CERTIFICADO: {{ $cert->certificate_code }}
                                    </span>
                                @elseif($isCertified)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        🟢 CERTIFICADO EMITIDO
                                    </span>
                                @elseif($isFailing)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200" title="No alcanza la nota mínima de {{ $minPassingScore }} pts">
                                        <span>🚫</span> REPROBADO (No califica)
                                    </span>
                                @elseif($isUnscored)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-slate-200 text-slate-700" title="Requiere calificación aprobatoria">
                                        <span>⏳</span> OBSERVADO (Sin nota)
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                        🟡 PENDIENTE DE EMISIÓN
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right space-x-1 whitespace-nowrap">
                                @if($cert)
                                    <a href="{{ route('certificates.show', $cert) }}" class="p-1.5 text-slate-500 hover:text-brand hover:bg-slate-100 rounded inline-block" title="Ver Certificado">
                                        👁️
                                    </a>
                                    <a href="{{ route('certificates.download', $cert) }}" class="p-1.5 text-slate-500 hover:text-green-700 hover:bg-slate-100 rounded inline-block" title="Descargar PDF">
                                        📥
                                    </a>
                                @else
                                    <button type="button"
                                            @click="editingParticipant = {
                                                id: {{ $part->id }},
                                                name: '{{ addslashes($part->full_title_name) }}',
                                                doc: '{{ $part->document_number }}',
                                                role: '{{ addslashes($part->pivot->role ?? 'Participante') }}',
                                                score: '{{ $part->pivot->score ?? '' }}',
                                                score_label: '{{ addslashes($part->pivot->score_label ?? '') }}',
                                                actionUrl: '{{ route('activities.participants.update', [$activity, $part]) }}'
                                            }; editParticipantModal = true;"
                                            class="p-1.5 text-slate-500 hover:text-brand hover:bg-slate-100 rounded inline-block"
                                            title="Editar Calificación / Rol">
                                        📝
                                    </button>
                                    <form method="POST" action="{{ route('activities.participants.remove', [$activity, $part]) }}" class="inline-block" onsubmit="return confirm('¿Quitar a este participante de la actividad?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded" title="Quitar de la nómina">
                                            🗑️
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-400">
                                <div class="text-3xl mb-2">📋</div>
                                <p class="font-semibold text-slate-600">No hay participantes registrados en la nómina de esta actividad.</p>
                                <p class="text-xs text-slate-400 mt-1">Cargue un archivo Excel o agregue estudiantes desde el padrón para comenzar. Las columnas de notas se detectan automáticamente.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Certificates Issued List -->
    <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h4 class="text-xs font-bold text-brand uppercase tracking-wider">Certificados Emitidos en este Evento</h4>
                <p class="text-xs text-slate-400">Total: {{ $activity->certificates->count() }} documento(s) emitido(s)</p>
            </div>

            @if($activity->certificates->whereNull('revoked_at')->count() > 0)
                <a href="{{ route('activities.download-zip', $activity) }}"
                   class="px-3.5 py-1.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5"
                   title="Descargar todos los certificados en un archivo ZIP comprimido">
                    <span>📦 Descargar Todos en ZIP</span>
                </a>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                    <tr>
                        <th class="px-5 py-3">Código</th>
                        <th class="px-5 py-3">Beneficiario</th>
                        <th class="px-5 py-3">Tipo</th>
                        <th class="px-5 py-3">Calificación</th>
                        <th class="px-5 py-3">Vigencia</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($activity->certificates as $cert)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3 font-mono font-bold text-brand-hover">
                                <a href="{{ route('certificates.show', $cert) }}" class="hover:underline">
                                    {{ $cert->certificate_code }}
                                </a>
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900">
                                {{ $cert->beneficiary->full_name }}
                                <span class="text-[10px] text-slate-400 block">{{ $cert->beneficiary->formatted_document }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $cert->certificateType->name }}
                            </td>
                            <td class="px-5 py-3">
                                @if($cert->hasScore())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-900 border border-amber-200">
                                        {{ $cert->formatted_score }}
                                    </span>
                                @else
                                    <span class="text-slate-400 text-[10px]">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                @if($cert->isPermanent())
                                    <span class="font-bold text-purple-700">♾️ Vitalicia</span>
                                @else
                                    <span>Hasta {{ $cert->expiration_date->format('d/m/Y') }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if($cert->isRevoked())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                        REVOCADO
                                    </span>
                                @elseif($cert->isExpired())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                        EXPIRADO
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        VIGENTE
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('certificates.preview', $cert) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-brand-hover hover:bg-slate-100 rounded inline-block" title="Vista Previa">
                                    👁️
                                </a>
                                <a href="{{ route('certificates.download', $cert) }}" class="p-1.5 text-slate-500 hover:text-green-700 hover:bg-slate-100 rounded inline-block" title="Descargar">
                                    📥
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                No se han emitido certificados para esta actividad todavía.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal 1: Carga de Nómina por Excel / CSV -->
    <div x-show="enrollExcelModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
         style="display: none;">

        <div class="ft-panel bg-white rounded-2xl shadow-2xl max-w-xl w-full p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto flex flex-col" @click.away="enrollExcelModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl font-bold">
                        📂
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Cargar Nómina a la Actividad</h3>
                        <p class="text-xs text-slate-500">Importe estudiantes y calificaciones desde una planilla Excel o CSV.</p>
                    </div>
                </div>
                <button @click="enrollExcelModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl p-1">&times;</button>
            </div>

            <form method="POST" action="{{ route('activities.roster.enroll', $activity) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="p-4 bg-emerald-50/70 border border-emerald-200/80 rounded-xl space-y-2">
                    <h5 class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                        <span>✨</span> Reconocimiento Automático Inteligente
                    </h5>
                    <ul class="text-[11px] text-emerald-800 space-y-1 list-disc pl-4">
                        <li><strong>Datos Personales:</strong> Detecta columnas <code>CI</code>, <code>Nombre Completo</code> (o <code>Nombres</code> y <code>Apellidos</code>), <code>Correo</code>, <code>Carrera</code>.</li>
                        <li><strong>Calificaciones y Notas:</strong> Detecta automáticamente columnas <code>NOTA</code>, <code>PUNTAJE</code>, <code>CALIFICACION</code>, <code>MENCION</code> y las asocia al participante.</li>
                        <li><strong>Inscripción segura:</strong> Registra a nuevos estudiantes en el padrón y reutiliza existentes sin alterar datos previos.</li>
                    </ul>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-slate-700">
                        Seleccionar Archivo Excel o CSV <span class="text-red-500">*</span>
                    </label>
                    <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center bg-slate-50 hover:bg-slate-100/60 transition-all cursor-pointer relative">
                        <input type="file"
                               name="file"
                               required
                               accept=".xlsx,.xls,.csv,.txt"
                               class="absolute inset-0 opacity-0 cursor-pointer w-full h-full">
                        <div class="space-y-1.5">
                            <div class="text-3xl">📊</div>
                            <p class="text-xs font-bold text-slate-700">Haga clic o arrastre el archivo Excel aquí</p>
                            <p class="text-[11px] text-slate-400">Formatos soportados: .xlsx, .xls, .csv</p>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="enrollExcelModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                        <span>📥 Cargar y Procesar Nómina</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 2: Agregar Participante Manual desde el Padrón -->
    <div x-show="manualAddModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
         style="display: none;">

        <div class="ft-panel bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto flex flex-col" @click.away="manualAddModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-brand flex items-center justify-center text-xl font-bold">
                        👤
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Agregar del Padrón a la Nómina</h3>
                        <p class="text-xs text-slate-500">Busque un participante y asigne opcionalmente su calificación.</p>
                    </div>
                </div>
                <button @click="manualAddModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl p-1">&times;</button>
            </div>

            <form method="POST" action="{{ route('activities.participants.add-manual', $activity) }}" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Buscar por CI o Nombre
                    </label>
                    <div class="relative">
                        <input type="text"
                               x-model="searchAdd"
                               placeholder="Escriba documento o apellido..."
                               class="w-full text-xs py-2 pl-8 pr-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                        <span class="absolute left-2.5 top-2.5 text-slate-400 text-xs">🔍</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">
                        Seleccione el Participante <span class="text-red-500">*</span>
                    </label>
                    <div class="border border-slate-200 rounded-xl max-h-48 overflow-y-auto p-2 divide-y divide-slate-100 bg-slate-50">
                        @foreach($beneficiaries as $ben)
                            @php
                                $alreadyEnrolled = $participants->contains('id', $ben->id);
                            @endphp
                            <label class="flex items-center gap-3 py-2 px-2 hover:bg-white rounded-lg cursor-pointer transition-colors text-xs {{ $alreadyEnrolled ? 'opacity-40 cursor-not-allowed' : '' }}"
                                   x-show="!searchAdd || '{{ strtolower(addslashes($ben->full_title_name . ' ' . $ben->formatted_document)) }}'.includes(searchAdd.toLowerCase())">
                                <input type="radio"
                                       name="beneficiary_id"
                                       value="{{ $ben->id }}"
                                       {{ $alreadyEnrolled ? 'disabled' : 'required' }}
                                       class="text-brand focus:ring-brand">
                                <div class="flex-1">
                                    <span class="font-bold text-slate-800">{{ $ben->full_title_name }}</span>
                                    <span class="text-slate-400 font-mono ml-2">({{ $ben->formatted_document }})</span>
                                    @if($alreadyEnrolled)
                                        <span class="text-[10px] text-amber-700 font-bold ml-2">Ya registrado</span>
                                    @endif
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Calificación Numérica (Opcional)</label>
                        <input type="number" step="0.1" name="score" placeholder="Ej: 85" min="0" max="100"
                               class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Mención o Texto (Opcional)</label>
                        <input type="text" name="score_label" placeholder="Ej: 85/100 o Con Excelencia"
                               class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Rol en la Actividad (Opcional)</label>
                    <select name="role" class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                        <option value="Participante">Participante</option>
                        <option value="Expositor">Expositor / Disertante</option>
                        <option value="Organizador">Organizador</option>
                        <option value="Asistente">Asistente</option>
                    </select>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="manualAddModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-brand hover:bg-brand-deep text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                        <span>+ Agregar a la Nómina</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 3: Emisión Masiva a la Nómina de Participantes -->
    <div x-show="issueRosterModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
         style="display: none;">

        <div class="ft-panel bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto flex flex-col"
             @click.away="issueRosterModal = false"
             x-data="{ showAdvancedOptions: false }">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 text-action flex items-center justify-center text-xl font-bold">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Emitir Certificados a la Nómina</h3>
                        <p class="text-xs text-slate-500">Se generarán los certificados con firma digital, QR y validez oficial.</p>
                    </div>
                </div>
                <button @click="issueRosterModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl p-1">&times;</button>
            </div>

            <form method="POST" action="{{ route('activities.participants.issue-certificates', $activity) }}" class="space-y-4">
                @csrf

                <div class="p-3.5 {{ $hasScoreType && $pendingCount === 0 ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800' }} border rounded-xl text-xs space-y-1.5">
                    @if($hasScoreType)
                        <p><strong>Control de Aprobación Institucional:</strong> Se emitirán certificados digitales únicamente para los <strong class="text-slate-900">{{ $pendingCount }}</strong> participante(s) <strong>aprobados</strong> (calificación &ge; {{ $minPassingScore }} pts).</p>
                        @if($failingCount > 0)
                            <p class="text-[11px] text-red-700 font-semibold flex items-center gap-1.5">
                                <span>🚫</span>
                                <span>Se omitirán automáticamente <strong>{{ $failingCount }} estudiante(s) reprobados</strong> (nota menor a {{ $minPassingScore }} pts). Por ética y normativa facultativa, no se les otorgará certificado de aprobación.</span>
                            </p>
                        @endif
                        @if($unscoredCount > 0)
                            <p class="text-[11px] text-amber-700 flex items-center gap-1.5">
                                <span>⏳</span>
                                <span>Hay <strong>{{ $unscoredCount }} estudiante(s) sin nota registrada</strong> que no recibirán certificado hasta registrar su nota aprobatoria.</span>
                            </p>
                        @endif
                    @else
                        <p><strong>¡Atención!</strong> Se emitirán certificados digitales para los <strong class="text-slate-900">{{ $pendingCount }}</strong> participantes pendientes.</p>
                        <p class="text-[11px] text-amber-700">Se generará la certificación digital oficial con firma y código de verificación QR.</p>
                    @endif
                </div>

                @if($activity->certificateType)
                    <!-- Resumen Oficial de Certificación Configurada -->
                    <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Certificación Configurada</span>
                            @php
                                $isPermConfig = ($config?->validity_mode ?? ($activity->certificateType?->validity_mode ?? 'permanent')) === 'permanent';
                            @endphp
                            @if($isPermConfig)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                    <span>♾️</span> Vigencia Vitalicia
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    <span>⏳</span> Validez {{ $config?->validity_years ?? ($activity->certificateType->default_validity_years ?? 3) }} Años
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="text-2xl">{{ $activity->certificateType->icon ?? '📜' }}</span>
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-bold text-slate-800 truncate">{{ $activity->certificateType->name }}</h4>
                                <p class="text-[11px] text-slate-500 line-clamp-1">{{ $activity->certificateType->description }}</p>
                            </div>
                        </div>

                        <div class="pt-2 border-t border-slate-200/80 flex flex-wrap items-center justify-between text-[11px] gap-2 text-slate-600">
                            <div class="flex items-center gap-1.5">
                                <span class="text-slate-400">Plantilla:</span>
                                <span class="font-semibold text-slate-800">{{ $config?->template?->name ?? 'Predeterminada de la Facultad' }}</span>
                            </div>
                            @if($activity->certificateType->has_score)
                                <div class="flex items-center gap-1 text-amber-700 font-bold bg-amber-50 px-2 py-0.5 rounded-lg border border-amber-200">
                                    <span>📊</span> Con Notas / Calificaciones
                                </div>
                            @else
                                <div class="flex items-center gap-1 text-slate-600 font-semibold bg-slate-100 px-2 py-0.5 rounded-lg">
                                    <span>🛡️</span> Acreditación Directa
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Campos ocultos con la configuración de la actividad -->
                    <input type="hidden" name="certificate_type_id" value="{{ $activity->certificate_type_id }}">
                    <input type="hidden" name="certificate_template_id" value="{{ $config?->certificate_template_id ?? '' }}">

                    <!-- Fecha de Emisión -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Fecha Oficial de Emisión <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="issue_date" value="{{ now()->toDateString() }}" required
                               class="w-full text-xs py-2.5 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white shadow-2xs font-medium">
                        <p class="text-[11px] text-slate-400 mt-1">Fecha oficial registrada en el certificado y QR de verificación.</p>
                    </div>

                    <!-- Opciones avanzadas opcionales -->
                    <div class="pt-1">
                        <button type="button" @click="showAdvancedOptions = !showAdvancedOptions"
                                class="text-xs text-brand hover:text-brand-deep font-semibold flex items-center gap-1.5 focus:outline-hidden group">
                            <span class="group-hover:translate-x-0.5 transition-transform" x-text="showAdvancedOptions ? '▾' : '▸'"></span>
                            <span x-text="showAdvancedOptions ? 'Ocultar opciones avanzadas' : '⚙️ Opciones avanzadas (modificar tipo o plantilla para este lote)'"></span>
                        </button>

                        <div x-show="showAdvancedOptions" x-cloak class="mt-3 p-3.5 bg-slate-50/80 rounded-xl border border-slate-200 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Cambiar Tipo de Certificado (Opcional)</label>
                                <select name="override_certificate_type_id"
                                        @change="$el.form.querySelector('input[name=certificate_type_id]').value = $el.value"
                                        class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                                    @foreach($types as $tp)
                                        <option value="{{ $tp->id }}" {{ ($activity->certificate_type_id == $tp->id) ? 'selected' : '' }}>
                                            {{ $tp->icon ?? '📜' }} {{ $tp->name }} (Predeterminado: {{ $tp->validity_mode === 'permanent' ? 'Vitalicio' : 'Temporal' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Cambiar Plantilla (Opcional)</label>
                                <select name="override_certificate_template_id"
                                        @change="$el.form.querySelector('input[name=certificate_template_id]').value = $el.value"
                                        class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                                    <option value="">Predeterminada (Configurada en la actividad)</option>
                                    @foreach($templates as $tpl)
                                        <option value="{{ $tpl->id }}" {{ ($config?->certificate_template_id == $tpl->id) ? 'selected' : '' }}>
                                            {{ $tpl->name }} ({{ $tpl->orientation === 'horizontal' ? 'Horizontal' : 'Vertical' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                @else
                    <!-- Fallback para actividades antiguas sin tipo configurado -->
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Tipo de Certificado a Otorgar <span class="text-red-500">*</span></label>
                            <select name="certificate_type_id" required class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                                @foreach($types as $tp)
                                    <option value="{{ $tp->id }}">
                                        {{ $tp->icon ?? '📜' }} {{ $tp->name }} ({{ $tp->validity_mode === 'permanent' ? 'Vitalicio' : 'Temporal' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Fecha de Emisión <span class="text-red-500">*</span></label>
                            <input type="date" name="issue_date" value="{{ now()->toDateString() }}" required class="w-full text-xs py-1.5 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Plantilla de Certificado</label>
                            <select name="certificate_template_id" class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                                <option value="">Predeterminada de la Facultad</option>
                                @foreach($templates as $tpl)
                                    <option value="{{ $tpl->id }}">
                                        {{ $tpl->name }} ({{ $tpl->orientation === 'horizontal' ? 'Horizontal' : 'Vertical' }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="issueRosterModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    @if($pendingCount > 0)
                        <button type="submit" class="px-5 py-2.5 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                            <span>🚀 Confirmar y Emitir a {{ $hasScoreType ? 'Aprobados' : 'Todos' }} ({{ $pendingCount }})</span>
                        </button>
                    @else
                        <button type="button" disabled class="px-5 py-2.5 bg-slate-300 text-slate-500 text-xs font-bold rounded-xl cursor-not-allowed">
                            Sin estudiantes aprobados para emitir
                        </button>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Modal 4: Editar Calificación y Rol del Participante en la Nómina -->
    <div x-show="editParticipantModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
         style="display: none;">

        <div class="ft-panel bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 space-y-4" @click.away="editParticipantModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-xl font-bold">
                        📝
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Calificación del Estudiante</h3>
                        <p class="text-xs text-slate-500" x-text="editingParticipant.name + ' (' + editingParticipant.doc + ')'"></p>
                    </div>
                </div>
                <button @click="editParticipantModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl p-1">&times;</button>
            </div>

            <form method="POST" :action="editingParticipant.actionUrl" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Calificación Numérica</label>
                    <input type="number" step="0.1" min="0" max="1000" name="score" x-model="editingParticipant.score"
                           placeholder="Ej: 90"
                           class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    <p class="text-[10px] text-slate-400 mt-1">Puntaje numérico sobre la escala del curso.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Mención o Calificación Cualitativa</label>
                    <input type="text" name="score_label" x-model="editingParticipant.score_label"
                           placeholder="Ej: 90/100 o Aprobado con Excelencia"
                           class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    <p class="text-[10px] text-slate-400 mt-1">Texto descriptivo de la nota que aparecerá en el certificado y QR.</p>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Rol</label>
                    <input type="text" name="role" x-model="editingParticipant.role"
                           class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editParticipantModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-brand hover:bg-brand-deep text-white text-xs font-bold rounded-xl shadow-md transition-all">
                        Guardar Calificación
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
