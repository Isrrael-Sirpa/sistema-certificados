@extends('layouts.app')

@section('page-title', 'Registrar Actividad Académica')
@section('page-subtitle', 'Alta de curso, seminario, taller o evento universitario')

@section('content')
@php($initialType = $types->firstWhere('id', (int) old('certificate_type_id')) ?? $types->firstWhere('code', 'aprobacion') ?? $types->first())
<div class="ft-form-page" x-data="{
    typesData: {{ Js::from($types->keyBy('id')) }},
    selectedTypeId: @js((string) old('certificate_type_id', $initialType?->id ?? '')),
    targetAudienceMode: @js(old('target_audience_mode', 'own_unit')),
    validityMode: @js(old('validity_mode', $initialType?->validity_mode === 'permanent' ? 'permanent' : 'temporal')),
    validityYears: @js((int) old('validity_years', $initialType?->default_validity_years ?? 3)),
    showScorePdf: {{ old('show_score_on_pdf', '1') == '1' ? 'true' : 'false' }},
    showScorePortal: {{ old('show_score_on_portal', '1') == '1' ? 'true' : 'false' }},
    selectedTemplateId: @js((string) old('certificate_template_id', '')),
    organizerUnitId: @js((string) old('organizational_unit_id', auth()->user()->organizational_unit_id ?? $units->first()?->id ?? '')),
    templateSearch: '', templateOrientation: '',
    templateData: @js($templates->keyBy('id')->map->only(['name', 'orientation', 'organizational_unit_id'])),
    templateVisible(id) {
        const item = this.templateData[id];
        return item && (!item.organizational_unit_id || String(item.organizational_unit_id) === String(this.organizerUnitId))
            && (!this.templateOrientation || item.orientation === this.templateOrientation)
            && item.name.toLocaleLowerCase('es').includes(this.templateSearch.toLocaleLowerCase('es').trim());
    },
    get matchingTemplateCount() { return Object.keys(this.templateData).filter(id => this.templateVisible(id)).length; },
    onOrganizerChange() {
        if (this.selectedTemplateId && this.templateData[this.selectedTemplateId]?.organizational_unit_id
            && String(this.templateData[this.selectedTemplateId].organizational_unit_id) !== String(this.organizerUnitId)) this.selectedTemplateId = '';
    },

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
            this.validityMode = tp.validity_mode === 'permanent' ? 'permanent' : 'temporal';
            this.validityYears = tp.default_validity_years || 3;
        }
    }
}">
    <div class="space-y-6">

        <div class="ft-form-hero" style="margin-bottom:0">
            <div>
                <span class="ft-form-kicker">Gestión académica · Nueva actividad</span>
                <h2>Prepara tu próximo evento.</h2>
                <p>Organiza la actividad, define quiénes pueden participar y elige la presentación de sus certificados.</p>
            </div>
            <a href="{{ route('activities.index') }}" class="ft-form-back">← Volver a actividades</a>
        </div>

        <nav aria-label="Secciones de la actividad" class="flex flex-wrap gap-3 text-xs font-semibold text-brand">
            <a href="#activity-info" class="rounded-xl bg-white border border-border px-4 py-3">01 · Datos del evento</a>
            <a href="#activity-audience" class="rounded-xl bg-white border border-border px-4 py-3">02 · Participantes habilitados</a>
            <a href="#activity-certification" class="rounded-xl bg-white border border-border px-4 py-3">03 · Certificado y plantilla</a>
        </nav>
        <form method="POST" action="{{ route('activities.store') }}" class="space-y-6">
            @csrf

            <!-- SECCIÓN 1: DATOS GENERALES DE LA ACTIVIDAD -->
            <div id="activity-info" class="ft-form-section space-y-5 scroll-mt-28">
                <div class="border-b border-slate-100 pb-2">
                    <h4 class="text-xs font-bold text-brand uppercase tracking-wider flex items-center gap-2">
                        <span class="w-5 h-5 rounded-md bg-brand/10 text-brand flex items-center justify-center text-[11px] font-bold">1</span>
                        <span>Datos Académicos del Evento</span>
                    </h4>
                </div>

                <div>
                    <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nombre de la Actividad <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand"
                           placeholder="Ej: Curso de Especialización en Inteligencia Artificial y Ciencia de Datos">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="activity_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Tipo de Evento <span class="text-red-500">*</span>
                        </label>
                        <select name="activity_type" id="activity_type" required
                                class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                            <option value="Curso" {{ old('activity_type') === 'Curso' ? 'selected' : '' }}>Curso</option>
                            <option value="Taller" {{ old('activity_type') === 'Taller' ? 'selected' : '' }}>Taller</option>
                            <option value="Seminario" {{ old('activity_type') === 'Seminario' ? 'selected' : '' }}>Seminario</option>
                            <option value="Congreso" {{ old('activity_type') === 'Congreso' ? 'selected' : '' }}>Congreso</option>
                            <option value="Capacitación" {{ old('activity_type') === 'Capacitación' ? 'selected' : '' }}>Capacitación</option>
                            <option value="Conferencia" {{ old('activity_type') === 'Conferencia' ? 'selected' : '' }}>Conferencia</option>
                            <option value="Diplomado" {{ old('activity_type') === 'Diplomado' ? 'selected' : '' }}>Diplomado</option>
                        </select>
                    </div>

                    <div>
                        <label for="academic_hours" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Carga Horaria (Horas Académicas) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="academic_hours" id="academic_hours" value="{{ old('academic_hours', 40) }}" min="0" required
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Fecha de Inicio <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', now()->toDateString()) }}" required
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="end_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Fecha de Conclusión <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', now()->toDateString()) }}" required
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="location" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Lugar / Modalidad
                        </label>
                        <input type="text" name="location" id="location" value="{{ old('location', 'Paraninfo Universitario y Modalidad Virtual') }}"
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand"
                               placeholder="Ej: Auditorio Central">
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Estado del Evento <span class="text-red-500">*</span>
                        </label>
                        <select name="status" id="status" required
                                class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                            <option value="activa" {{ old('status') === 'activa' ? 'selected' : '' }}>Activa</option>
                            <option value="finalizada" {{ old('status') === 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                            <option value="cancelada" {{ old('status') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="organizer" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Unidad Organizadora
                        </label>
                        <input type="text" name="organizer" id="organizer" value="{{ old('organizer', 'Vicedecanato - Facultad de Tecnología') }}"
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="responsible_authority" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Autoridad Responsable (Firma)
                        </label>
                        <input type="text" name="responsible_authority" id="responsible_authority" value="{{ old('responsible_authority', 'Ing. Aniceto Velarde Forest - Vicedecano') }}"
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Descripción del Contenido Académico
                    </label>
                    <textarea name="description" id="description" rows="2"
                              class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand"
                              placeholder="Resumen temático que puede reflejarse en el certificado...">{{ old('description') }}</textarea>
                </div>
            </div>

            <!-- SECCIÓN 2: RESOLUCIÓN Y ALCANCE DE CARRERAS -->
            <div id="activity-audience" class="ft-form-section space-y-5 scroll-mt-28">
                <div class="border-b border-slate-100 pb-2">
                    <h4 class="text-xs font-bold text-brand uppercase tracking-wider flex items-center gap-2">
                        <span class="w-5 h-5 rounded-md bg-brand/10 text-brand flex items-center justify-center text-[11px] font-bold">2</span>
                        <span>Marco Normativo y Carreras Habilitadas</span>
                    </h4>
                    <p class="text-xs text-slate-500 mt-0.5">Defina el alcance de la resolución y las carreras que pueden certificar en esta actividad</p>
                </div>

                @if(auth()->user()->isAdmin())
                    <div>
                        <label for="organizational_unit_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Carrera o Departamento Organizador <span class="text-red-500">*</span>
                        </label>
                        <select name="organizational_unit_id" id="organizational_unit_id" required x-model="organizerUnitId" @change="onOrganizerChange()"
                                class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                            @foreach($units as $u)
                                <option value="{{ $u->id }}" {{ old('organizational_unit_id', auth()->user()->organizational_unit_id) == $u->id ? 'selected' : '' }}>
                                    [{{ $u->code }}] {{ $u->name }} ({{ $u->type_label }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex items-center justify-between">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Unidad Organizadora</span>
                            <div class="text-xs font-bold text-brand mt-0.5">{{ auth()->user()->organizationalUnit->name ?? 'Mi Carrera' }}</div>
                        </div>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-brand/10 text-brand">
                            {{ auth()->user()->organizationalUnit->code ?? 'CARRERA' }}
                        </span>
                    </div>
                @endif

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="resolution_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            N° de Resolución Facultativa / Honorable Consejo
                        </label>
                        <input type="text" name="resolution_number" id="resolution_number" value="{{ old('resolution_number') }}"
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand"
                               placeholder="Ej: Res. Facultativa N° 084/2026">
                        <p class="ft-field-help">Opcional. Si no existe una resolución, deja este campo vacío; no se añadirá un número al certificado.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Alcance de Estudiantes Habilitados <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2 mt-1">
                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="radio" name="target_audience_mode" value="own_unit" x-model="targetAudienceMode" class="text-brand focus:ring-brand">
                                <span class="text-xs font-medium text-slate-800">Solo mi propia carrera / unidad</span>
                            </label>

                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="radio" name="target_audience_mode" value="all_faculty" x-model="targetAudienceMode" class="text-brand focus:ring-brand">
                                <span class="text-xs font-medium text-slate-800">Toda la Facultad de Tecnología (las 9 carreras)</span>
                            </label>

                            <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 hover:bg-slate-50 cursor-pointer">
                                <input type="radio" name="target_audience_mode" value="specific_units" x-model="targetAudienceMode" class="text-brand focus:ring-brand">
                                <span class="text-xs font-medium text-slate-800">Carreras específicas habilitadas</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Selección de Carreras Específicas Habilitadas -->
                <div x-show="targetAudienceMode === 'specific_units'" style="display: none;" class="p-4 bg-brand/5 border border-brand/20 rounded-xl space-y-3">
                    <div class="text-xs font-bold text-brand">Seleccione las carreras autorizadas para participar en este evento:</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                        @foreach($units->where('type', 'carrera') as $cu)
                            <label class="flex items-center gap-2 p-2 bg-white rounded-lg border border-slate-200 hover:border-brand cursor-pointer text-xs">
                                <input type="checkbox" name="allowed_unit_ids[]" value="{{ $cu->id }}"
                                       {{ is_array(old('allowed_unit_ids')) && in_array($cu->id, old('allowed_unit_ids')) ? 'checked' : '' }}
                                       class="rounded border-slate-300 text-brand focus:ring-brand">
                                <span class="font-bold text-slate-800">[{{ $cu->code }}]</span>
                                <span class="truncate">{{ $cu->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="text-[10px] text-slate-500 italic">
                        * Estudiantes inscritos de otras carreras no autorizadas serán observados automáticamente según la resolución y no se emitirá su certificado.
                    </p>
                </div>
            </div>

            <!-- SECCIÓN 3: TIPO DE CERTIFICADO Y POLÍTICA DE VIGENCIA/NOTAS -->
            <div id="activity-certification" class="ft-form-section space-y-6 scroll-mt-28">
                <div class="border-b border-slate-100 pb-2 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-brand uppercase tracking-wider flex items-center gap-2">
                            <span class="w-5 h-5 rounded-md bg-brand/10 text-brand flex items-center justify-center text-[11px] font-bold">3</span>
                            <span>Tipo de Certificado Oficial y Políticas de Emisión</span>
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5">Seleccione el mérito que acreditará esta actividad a los participantes</p>
                    </div>
                    <span class="text-[10px] bg-blue-50 text-brand px-2 py-0.5 rounded-full font-bold">Multi-Tipo</span>
                </div>

                <input type="hidden" name="certificate_type_id" :value="selectedTypeId">

                <!-- Type Selector Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
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
                                <span x-show="selectedTypeId == '{{ $tp->id }}'" class="text-brand font-bold text-xs">✓ Elegido</span>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Contextual Options for Selected Type -->
                <div class="p-5 bg-slate-50 rounded-2xl border border-slate-200 space-y-5">
                    <!-- Validity Scheme Options -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Esquema de Validez y Caducidad
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <label class="flex items-start gap-3 p-3 bg-white rounded-xl border cursor-pointer hover:border-slate-300"
                                   :class="validityMode === 'permanent' ? 'border-brand ring-2 ring-brand/20 bg-brand/5' : 'border-slate-200'">
                                <input type="radio" name="validity_mode" value="permanent" x-model="validityMode" class="mt-0.5 text-brand focus:ring-brand">
                                <div>
                                    <div class="flex items-center gap-1.5 font-bold text-xs text-slate-900">
                                        <span>♾️</span> Vigencia Permanente / Vitalicia
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">El certificado no vence jamás. Solo registrará la fecha en que fue otorgado.</p>
                                </div>
                            </label>

                            <label class="flex items-start gap-3 p-3 bg-white rounded-xl border cursor-pointer hover:border-slate-300"
                                   :class="validityMode === 'temporal' ? 'border-brand ring-2 ring-brand/20 bg-brand/5' : 'border-slate-200'">
                                <input type="radio" name="validity_mode" value="temporal" x-model="validityMode" class="mt-0.5 text-brand focus:ring-brand">
                                <div>
                                    <div class="flex items-center gap-1.5 font-bold text-xs text-slate-900">
                                        <span>⏳</span> Con Vencimiento Temporal
                                    </div>
                                    <p class="text-[11px] text-slate-500 mt-0.5">El certificado expirará luego de un período regulado por la institución.</p>
                                </div>
                            </label>
                        </div>

                        <!-- Temporal Duration Selector (shown only if temporal) -->
                        <div x-show="validityMode === 'temporal'" x-cloak class="mt-3 p-4 bg-white rounded-xl border border-slate-200 space-y-3">
                            <label for="validity_years" class="ft-field-label">Duración de la validez</label>
                            <div class="flex flex-wrap items-center gap-3">
                                <input type="number" id="validity_years" name="validity_years" x-model.number="validityYears" min="1" max="50" :required="validityMode === 'temporal'" :disabled="validityMode !== 'temporal'" class="ft-input max-w-28">
                                <span class="text-sm text-muted">años desde la emisión</span>
                                @foreach([1, 2, 3, 5] as $years)
                                    <button type="button" @click="validityYears = {{ $years }}" :aria-pressed="Number(validityYears) === {{ $years }}" :class="Number(validityYears) === {{ $years }} ? 'bg-brand text-white border-brand' : 'bg-white text-brand border-border'" class="rounded-lg border px-3 py-2 text-xs font-semibold">{{ $years }} {{ $years === 1 ? 'año' : 'años' }}</button>
                                @endforeach
                            </div>
                            <p class="ft-field-help">Puedes indicar entre 1 y 50 años, según las condiciones de la actividad.</p>
                            @error('validity_years')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <!-- Score and Evaluation Options (Shown if currentType?.has_score or user wants) -->
                    <div x-show="currentType && currentType.has_score" class="pt-4 border-t border-slate-200 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📊</span>
                            <h5 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Configuración de Calificación y Puntajes</h5>
                        </div>
                        <p class="text-xs text-slate-500">
                            Esta actividad admite notas individuales. Al importar participantes por Excel, el sistema detectará automáticamente las columnas de calificación (ej. <code>NOTA</code>, <code>PUNTAJE</code>, <code>MENCION</code>).
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <label class="flex items-center gap-2.5 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer">
                                <input type="hidden" name="show_score_on_pdf" value="0">
                                <input type="checkbox" name="show_score_on_pdf" value="1" x-model="showScorePdf" class="rounded text-brand focus:ring-brand">
                                <span class="text-xs font-medium text-slate-800">Mostrar puntaje / mención en el PDF impreso</span>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer">
                                <input type="hidden" name="show_score_on_portal" value="0">
                                <input type="checkbox" name="show_score_on_portal" value="1" x-model="showScorePortal" class="rounded text-brand focus:ring-brand">
                                <span class="text-xs font-medium text-slate-800">Mostrar puntaje en el portal público de verificación QR</span>
                            </label>
                        </div>
                    </div>

                    <!-- Template Pre-Assignment (Optional) -->
                    <fieldset class="pt-6 border-t border-border space-y-5" id="activity-template-picker">
                        <legend class="text-base font-bold text-brand pt-6">Presentación del certificado <span class="text-xs font-normal text-muted">Opcional</span></legend>
                        <p class="text-sm text-muted">Compara los diseños disponibles y elige cómo se presentará el certificado de esta actividad.</p>
                        <label class="ft-template-choice flex items-start gap-3" :class="selectedTemplateId === '' ? 'is-selected' : ''">
                            <input type="radio" name="certificate_template_id" value="" x-model="selectedTemplateId" class="mt-1 text-brand">
                            <span><strong class="block text-sm text-brand">Usar la plantilla predeterminada disponible</strong><span class="ft-field-help block">Se utilizará una plantilla activa de la unidad organizadora o una institucional compartida.</span></span>
                        </label>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <div><label for="template-search" class="ft-field-label">Buscar un diseño</label><input id="template-search" type="search" x-model.debounce.150ms="templateSearch" placeholder="Nombre de la plantilla…" class="ft-input"></div>
                            <div><label for="template-orientation" class="ft-field-label">Formato del certificado</label><select id="template-orientation" x-model="templateOrientation" class="ft-input"><option value="">Todos los formatos</option><option value="landscape">A4 horizontal</option><option value="portrait">A4 vertical</option></select></div>
                        </div>
                        <p class="text-xs text-muted" aria-live="polite"><span x-text="matchingTemplateCount">{{ $templates->count() }}</span> diseño(s) disponibles para esta unidad.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 max-h-[580px] overflow-y-auto p-1">
                            @foreach($templates as $tpl)
                                <label class="ft-template-choice" x-show="templateVisible({{ $tpl->id }})" :class="String(selectedTemplateId) === '{{ $tpl->id }}' ? 'is-selected' : ''">
                                    <input type="radio" name="certificate_template_id" value="{{ $tpl->id }}" x-model="selectedTemplateId" class="sr-only">
                                    <div class="ft-choice-preview">@include('templates.thumbnail', ['tpl' => $tpl])</div>
                                    <span class="flex items-start justify-between gap-2"><strong class="text-sm text-brand">{{ $tpl->name }}</strong><span class="text-brand" x-show="String(selectedTemplateId) === '{{ $tpl->id }}'" aria-hidden="true">✓</span></span>
                                    <span class="block text-xs text-muted mt-2">A4 {{ $tpl->orientation === 'landscape' ? 'horizontal' : 'vertical' }} · v{{ $tpl->current_version }}.0</span>
                                    <span class="block text-xs text-muted mt-1">{{ $tpl->organizationalUnit?->name ?? 'Institucional · Compartida' }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p x-show="matchingTemplateCount === 0" x-cloak class="ft-form-note">No hay diseños que coincidan. Prueba otro nombre o formato, o utiliza la opción predeterminada.</p>
                        <div class="ft-form-note" aria-live="polite"><strong class="text-brand">Diseño elegido:</strong> <span x-text="selectedTemplateId ? templateData[selectedTemplateId]?.name : 'Predeterminada disponible para la unidad'"></span></div>
                        @error('certificate_template_id')<p class="ft-field-error">{{ $message }}</p>@enderror
                    </fieldset>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN -->
            <div class="ft-form-actions">
                <a href="{{ route('activities.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
                    Cancelar
                </a>
                <button type="submit" class="ft-form-primary">
                    <span>Crear actividad</span><span aria-hidden="true">→</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
