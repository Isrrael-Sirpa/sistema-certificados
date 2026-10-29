@extends('layouts.app')

@section('page-title', 'Editar Actividad Académica')
@section('page-subtitle', 'Modificación de fechas, carga horaria y parámetros de certificación')

@section('content')
<div class="max-w-4xl mx-auto" x-data="{
    typesData: {{ Js::from($types->keyBy('id')) }},
    selectedTypeId: '{{ old('certificate_type_id', $activity->certificate_type_id ?? $types->first()?->id) }}',
    validityMode: '{{ old('validity_mode', $config?->validity_mode ?? ($activity->certificateType?->validity_mode ?? 'permanent')) }}',
    validityYears: {{ old('validity_years', $config?->validity_years ?? ($activity->certificateType?->default_validity_years ?? 3)) }},
    showScorePdf: {{ old('show_score_on_pdf', $config?->show_score_on_pdf ?? true) ? 'true' : 'false' }},
    showScorePortal: {{ old('show_score_on_portal', $config?->show_score_on_portal ?? true) ? 'true' : 'false' }},
    selectedTemplateId: '{{ old('certificate_template_id', $config?->certificate_template_id ?? '') }}',

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
    <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">

        <div class="p-6 bg-brand text-white flex items-center justify-between">
            <h3 class="text-base font-bold truncate">Editar: {{ $activity->name }}</h3>
            <a href="{{ route('activities.show', $activity) }}" class="text-xs text-slate-300 hover:text-white">&larr; Volver</a>
        </div>

        <form method="POST" action="{{ route('activities.update', $activity) }}" class="p-6 sm:p-8 space-y-8">
            @csrf
            @method('PUT')

            <!-- SECCIÓN 1: DATOS ACADÉMICOS -->
            <div class="space-y-5">
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
                    <input type="text" name="name" id="name" value="{{ old('name', $activity->name) }}" required
                           class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="activity_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Tipo de Evento <span class="text-red-500">*</span>
                        </label>
                        <select name="activity_type" id="activity_type" required
                                class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                            <option value="Curso" {{ old('activity_type', $activity->activity_type) === 'Curso' ? 'selected' : '' }}>Curso</option>
                            <option value="Taller" {{ old('activity_type', $activity->activity_type) === 'Taller' ? 'selected' : '' }}>Taller</option>
                            <option value="Seminario" {{ old('activity_type', $activity->activity_type) === 'Seminario' ? 'selected' : '' }}>Seminario</option>
                            <option value="Congreso" {{ old('activity_type', $activity->activity_type) === 'Congreso' ? 'selected' : '' }}>Congreso</option>
                            <option value="Capacitación" {{ old('activity_type', $activity->activity_type) === 'Capacitación' ? 'selected' : '' }}>Capacitación</option>
                            <option value="Conferencia" {{ old('activity_type', $activity->activity_type) === 'Conferencia' ? 'selected' : '' }}>Conferencia</option>
                            <option value="Diplomado" {{ old('activity_type', $activity->activity_type) === 'Diplomado' ? 'selected' : '' }}>Diplomado</option>
                        </select>
                    </div>

                    <div>
                        <label for="academic_hours" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Carga Horaria (Horas) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="academic_hours" id="academic_hours" value="{{ old('academic_hours', $activity->academic_hours) }}" min="0" required
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="start_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Fecha de Inicio <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" id="start_date" value="{{ old('start_date', $activity->start_date->format('Y-m-d')) }}" required
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="end_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Fecha de Conclusión <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="end_date" id="end_date" value="{{ old('end_date', $activity->end_date->format('Y-m-d')) }}" required
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="location" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Lugar / Modalidad
                        </label>
                        <input type="text" name="location" id="location" value="{{ old('location', $activity->location) }}"
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Estado del Evento <span class="text-red-500">*</span>
                        </label>
                        <select name="status" id="status" required
                                class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                            <option value="activa" {{ old('status', $activity->status) === 'activa' ? 'selected' : '' }}>Activa</option>
                            <option value="finalizada" {{ old('status', $activity->status) === 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                            <option value="cancelada" {{ old('status', $activity->status) === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="organizer" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Unidad Organizadora
                        </label>
                        <input type="text" name="organizer" id="organizer" value="{{ old('organizer', $activity->organizer) }}"
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="responsible_authority" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Autoridad Responsable (Firma)
                        </label>
                        <input type="text" name="responsible_authority" id="responsible_authority" value="{{ old('responsible_authority', $activity->responsible_authority) }}"
                               class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Descripción del Contenido Académico
                    </label>
                    <textarea name="description" id="description" rows="2"
                              class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">{{ old('description', $activity->description) }}</textarea>
                </div>
            </div>

            <!-- SECCIÓN 2: TIPO DE CERTIFICADO Y POLÍTICA DE VIGENCIA/NOTAS -->
            <div class="space-y-6 pt-6 border-t border-slate-200">
                <div class="border-b border-slate-100 pb-2 flex items-center justify-between">
                    <div>
                        <h4 class="text-xs font-bold text-brand uppercase tracking-wider flex items-center gap-2">
                            <span class="w-5 h-5 rounded-md bg-brand/10 text-brand flex items-center justify-center text-[11px] font-bold">2</span>
                            <span>Tipo de Certificado Oficial y Políticas de Emisión</span>
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5">Defina o actualice el mérito que acreditará esta actividad</p>
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

                        <!-- Temporal Duration Selector -->
                        <div x-show="validityMode === 'temporal'" class="mt-3 p-3.5 bg-white rounded-xl border border-slate-200 flex flex-wrap items-center gap-4">
                            <span class="text-xs font-bold text-slate-700">Duración de la Validez:</span>
                            <div class="flex flex-wrap items-center gap-2">
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

                    <!-- Score and Evaluation Options -->
                    <div x-show="currentType && currentType.has_score" class="pt-4 border-t border-slate-200 space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-base">📊</span>
                            <h5 class="text-xs font-bold text-slate-800 uppercase tracking-wider">Configuración de Calificación y Puntajes</h5>
                        </div>
                        <p class="text-xs text-slate-500">
                            Esta actividad admite notas individuales detectadas desde la nómina Excel/CSV o asignadas manualmente.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <label class="flex items-center gap-2.5 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer">
                                <input type="checkbox" name="show_score_on_pdf" value="1" x-model="showScorePdf" class="rounded text-brand focus:ring-brand">
                                <span class="text-xs font-medium text-slate-800">Mostrar puntaje / mención en el PDF impreso</span>
                            </label>

                            <label class="flex items-center gap-2.5 p-3 bg-white rounded-xl border border-slate-200 cursor-pointer">
                                <input type="checkbox" name="show_score_on_portal" value="1" x-model="showScorePortal" class="rounded text-brand focus:ring-brand">
                                <span class="text-xs font-medium text-slate-800">Mostrar puntaje en el portal público de verificación QR</span>
                            </label>
                        </div>
                    </div>

                    <!-- Template Pre-Assignment (Optional) -->
                    <div class="pt-4 border-t border-slate-200">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Plantilla Institucional para esta Actividad (Opcional)
                        </label>
                        <select name="certificate_template_id" x-model="selectedTemplateId"
                                class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-brand">
                            <option value="">-- Usar plantilla predeterminada del sistema --</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" {{ ($config?->certificate_template_id == $tpl->id) ? 'selected' : '' }}>
                                    {{ $tpl->name }} ({{ $tpl->orientation === 'landscape' ? 'Horizontal' : 'Vertical' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('activities.show', $activity) }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
