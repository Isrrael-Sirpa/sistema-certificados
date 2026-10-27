@extends('layouts.app')

@section('page-title', 'Detalle de la Actividad')
@section('page-subtitle', 'Gestión de participantes y emisión de certificados para este evento')

@section('content')
<div class="space-y-6" x-data="{
    enrollExcelModal: false,
    manualAddModal: false,
    issueRosterModal: false,
    searchAdd: ''
}">

    <!-- Activity Header Card -->
    <div class="ft-panel bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-brand/10 text-brand border border-brand/20">
                        {{ $activity->activity_type }}
                    </span>
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

                <a href="{{ route('certificates.create', ['activity_id' => $activity->id]) }}"
                   class="px-4 py-2 bg-brand hover:bg-brand-deep text-white text-xs font-semibold rounded-xl shadow-xs transition-all">
                    + Emitir Individual
                </a>

                <a href="{{ route('activities.edit', $activity) }}"
                   class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl">
                    ✏️
                </a>
            </div>
        </div>
    </div>

    <!-- Activity Certificate Template & Customization Section -->
    <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden"
         x-data="{
            configOpen: false,
            selectedTemplateId: '{{ old('certificate_template_id', $config?->certificate_template_id ?? '') }}',
            customTitle: '{{ old('custom_title', $config?->custom_title ?? '') }}',
            modality: '{{ old('modality', $config?->custom_fields_schema['modalidad'] ?? 'Presencial') }}',
            resolutionNumber: '{{ old('resolution_number', $config?->custom_fields_schema['resolucion'] ?? '') }}'
         }">

        <div class="p-5 bg-brand text-white flex items-center justify-between cursor-pointer select-none"
             @click="configOpen = !configOpen">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-white/10 flex items-center justify-center text-xl font-bold border border-white/15">
                    🎨
                </div>
                <div>
                    <h4 class="text-sm font-bold flex items-center gap-2">
                        <span>Plantilla, Diseño y Personalización de Certificado</span>
                        @if($config && $config->template)
                            <span class="bg-amber-400/20 text-amber-300 border border-amber-400/40 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                Plantilla Asignada: {{ $config->template->name }}
                            </span>
                        @else
                            <span class="bg-emerald-400/20 text-emerald-300 border border-emerald-400/40 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                Plantilla Predeterminada de la Facultad
                            </span>
                        @endif
                    </h4>
                    <p class="text-xs text-slate-300 mt-0.5">Elija la plantilla visual, título especial y modalidad que se plasmará en los certificados de esta actividad</p>
                </div>
            </div>

            <button type="button" class="text-xs font-semibold px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-xl transition-all border border-white/10 shrink-0">
                <span x-text="configOpen ? 'Ocultar Configuración ▲' : 'Elegir Plantilla y Ajustes ▼'"></span>
            </button>
        </div>

        <div x-show="configOpen" class="p-6 space-y-6 border-t border-slate-200">
            <form method="POST" action="{{ route('activities.certificate-config', $activity) }}" class="space-y-6">
                @csrf
                <input type="hidden" name="certificate_template_id" :value="selectedTemplateId">
                <input type="hidden" name="modality" :value="modality">

                <!-- 1. Visual Template Gallery -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        1. Seleccione la Plantilla Institucional para este Evento
                    </label>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Option Default / System Default -->
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
                                <p class="text-[11px] text-slate-500 mt-1">Utiliza la plantilla oficial configurada como predeterminada en el sistema para la Facultad.</p>
                            </div>

                            <div class="mt-4 pt-2 border-t border-slate-100 flex items-center justify-between text-[11px]">
                                <span class="text-slate-400">Automático</span>
                                <span x-show="selectedTemplateId === ''" class="text-brand font-bold flex items-center gap-1">
                                    ✓ Seleccionada
                                </span>
                            </div>
                        </div>

                        <!-- Specific Templates Cards -->
                        @foreach($templates as $tpl)
                            <div @click="selectedTemplateId = '{{ $tpl->id }}'"
                                 :class="selectedTemplateId == '{{ $tpl->id }}' ? 'border-brand bg-brand/5 ring-2 ring-brand/30' : 'border-slate-200 hover:border-slate-300 bg-white'"
                                 class="p-4 rounded-2xl border-2 transition-all cursor-pointer flex flex-col justify-between relative group">

                                <div class="flex items-start justify-between gap-2 mb-3">
                                    <div class="w-10 h-8 rounded-lg bg-brand/10 text-brand flex items-center justify-center font-bold text-xs border border-brand/20">
                                        @if($tpl->orientation === 'portrait')
                                            📱 VERT
                                        @else
                                            🖥️ HORIZ
                                        @endif
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
                                    <span x-show="selectedTemplateId == '{{ $tpl->id }}'" class="text-brand font-bold flex items-center gap-1">
                                        ✓ Seleccionada
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- 2. Clean Customization Fields -->
                <div class="pt-4 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Title with Quick Pills -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            2. Título Especial del Certificado (Opcional)
                        </label>
                        <input type="text"
                               name="custom_title"
                               x-model="customTitle"
                               placeholder="Ej: CERTIFICADO DE APROBACIÓN"
                               class="w-full py-2.5 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">

                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span class="text-[10px] text-slate-400 font-semibold mr-1">Sugerencias:</span>
                            <button type="button" @click="customTitle = 'CERTIFICADO DE ASISTENCIA'" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold rounded-lg transition-colors">
                                + Asistencia
                            </button>
                            <button type="button" @click="customTitle = 'CERTIFICADO DE APROBACIÓN'" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold rounded-lg transition-colors">
                                + Aprobación
                            </button>
                            <button type="button" @click="customTitle = 'CERTIFICADO DE RECONOCIMIENTO'" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold rounded-lg transition-colors">
                                + Reconocimiento
                            </button>
                            <button type="button" @click="customTitle = 'CERTIFICADO DE EXPOSITOR'" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-semibold rounded-lg transition-colors">
                                + Expositor
                            </button>
                        </div>
                    </div>

                    <!-- Modality with Visual Pills -->
                    <div class="space-y-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                            3. Modalidad del Evento
                        </label>

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
                            <button type="button"
                                    @click="modality = 'Presencial'"
                                    :class="modality === 'Presencial' ? 'border-brand bg-brand text-white font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                    class="py-2.5 px-3 border rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                                <span>🏢 Presencial</span>
                            </button>
                            <button type="button"
                                    @click="modality = 'Virtual'"
                                    :class="modality === 'Virtual' ? 'border-brand bg-brand text-white font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                    class="py-2.5 px-3 border rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                                <span>🌐 Virtual</span>
                            </button>
                            <button type="button"
                                    @click="modality = 'Semipresencial'"
                                    :class="modality === 'Semipresencial' ? 'border-brand bg-brand text-white font-bold' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                                    class="py-2.5 px-3 border rounded-xl text-xs transition-all flex items-center justify-center gap-1.5">
                                <span>🔀 Semipresencial</span>
                            </button>
                        </div>

                        <!-- Resolution Number -->
                        <div class="pt-2">
                            <label class="block text-[11px] font-bold text-slate-600 mb-1">Nro. de Resolución Facultativa o Convenio (Opcional)</label>
                            <input type="text"
                                   name="resolution_number"
                                   x-model="resolutionNumber"
                                   placeholder="Ej: Resol. Fac. N° 451/2026"
                                   class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end pt-4 border-t border-slate-100">
                    <button type="submit"
                            class="px-6 py-2.5 bg-brand hover:bg-brand-deep text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-2">
                        <span>💾 Guardar Plantilla y Ajustes de la Actividad</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Participants Roster Section -->
    <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xl">👥</span>
                    <h4 class="text-sm font-bold text-brand uppercase tracking-wider">Nómina de Participantes de esta Actividad</h4>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    Total: <strong class="text-slate-800">{{ $participants->count() }}</strong> participante(s) registrados ·
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                        {{ $participants->where('pivot.status', 'inscrito')->count() }} pendientes de emitir
                    </span> ·
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                        {{ $participants->where('pivot.status', 'certificado')->count() }} certificados
                    </span>
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

                @if($participants->where('pivot.status', 'inscrito')->count() > 0)
                    <button type="button"
                            @click="issueRosterModal = true"
                            class="px-4 py-2 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                        <span>⚡ Emitir Certificados a la Nómina ({{ $participants->where('pivot.status', 'inscrito')->count() }})</span>
                    </button>
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
                        <th class="px-5 py-3">Estado del Certificado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($participants as $index => $part)
                        @php
                            $cert = $issuedCertificatesByBenId[$part->id] ?? null;
                            $isCertified = $part->pivot->status === 'certificado' || $cert !== null;
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3 text-center text-slate-400 font-mono text-[10px]">
                                {{ $index + 1 }}
                            </td>
                            <td class="px-5 py-3 font-mono font-bold text-slate-800">
                                {{ $part->document_number }}
                            </td>
                            <td class="px-5 py-3 font-semibold text-slate-900">
                                {{ $part->full_title_name }}
                                <span class="text-[10px] text-slate-400 block">{{ $part->email ?? 'Sin correo' }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $part->institution_or_department ?? 'Facultad de Tecnología' }}
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
                            <td colspan="6" class="px-5 py-10 text-center text-slate-400">
                                <div class="text-3xl mb-2">📋</div>
                                <p class="font-semibold text-slate-600">No hay participantes registrados en la nómina de esta actividad.</p>
                                <p class="text-xs text-slate-400 mt-1">Cargue un archivo Excel o agregue estudiantes desde el padrón para comenzar.</p>
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
                <p class="text-xs text-slate-400">Total: {{ $activity->certificates->count() }} participante(s) certificados</p>
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
                        <th class="px-5 py-3">Emisión</th>
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
                            <td class="px-5 py-3 text-slate-600">
                                {{ $cert->issue_date->format('d/m/Y') }}
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
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400">
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
                        <p class="text-xs text-slate-500">Agregue participantes antes o durante el evento sin emitir aún los certificados.</p>
                    </div>
                </div>
                <button @click="enrollExcelModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl p-1">&times;</button>
            </div>

            <form method="POST" action="{{ route('activities.roster.enroll', $activity) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="p-4 bg-emerald-50/70 border border-emerald-200/80 rounded-xl space-y-2">
                    <h5 class="text-xs font-bold text-emerald-900 flex items-center gap-1.5">
                        <span>✨</span> ¿Cómo funciona la inscripción en la nómina?
                    </h5>
                    <ul class="text-[11px] text-emerald-800 space-y-1 list-disc pl-4">
                        <li><strong>Estudiantes nuevos:</strong> Se registrarán automáticamente en el sistema y se agregarán a esta nómina.</li>
                        <li><strong>Estudiantes existentes:</strong> Se vincularán directamente a esta actividad sin duplicar sus datos.</li>
                        <li><strong>Estado:</strong> Quedarán como <span class="font-bold">🟡 Pendiente de Emisión</span> para que pueda revisarlos antes de emitir los certificados.</li>
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
                            <p class="text-xs font-bold text-slate-700">Haga clic o arrastre el archivo aquí</p>
                            <p class="text-[11px] text-slate-400">Formatos soportados: .xlsx, .xls, .csv</p>
                        </div>
                    </div>
                </div>

                <div class="text-[11px] text-slate-500 bg-slate-50 p-2.5 rounded-lg border border-slate-200">
                    💡 <strong>Columnas reconocidas automáticamente:</strong> <code>CI</code>, <code>Nombre Completo</code> (o <code>Nombres</code> y <code>Apellidos</code>), <code>Correo</code>, <code>Carrera</code>.
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="enrollExcelModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                        <span>📥 Guardar en la Nómina</span>
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
                        <p class="text-xs text-slate-500">Busque un estudiante o docente ya registrado e incorpórelo a esta actividad.</p>
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
                    <div class="border border-slate-200 rounded-xl max-h-52 overflow-y-auto p-2 divide-y divide-slate-100 bg-slate-50">
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

        <div class="ft-panel bg-white rounded-2xl shadow-2xl max-w-lg w-full p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto flex flex-col" @click.away="issueRosterModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 text-action flex items-center justify-center text-xl font-bold">
                        ⚡
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Emitir Certificados a la Nómina</h3>
                        <p class="text-xs text-slate-500">Se generarán los certificados con firma y QR para los participantes pendientes.</p>
                    </div>
                </div>
                <button @click="issueRosterModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl p-1">&times;</button>
            </div>

            <form method="POST" action="{{ route('activities.participants.issue-certificates', $activity) }}" class="space-y-4">
                @csrf

                <div class="p-3.5 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs">
                    <strong>¡Atención!</strong> Se emitirán certificados digitales para los <strong class="text-slate-900">{{ $participants->where('pivot.status', 'inscrito')->count() }}</strong> participantes pendientes de esta actividad.
                </div>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Tipo de Certificado <span class="text-red-500">*</span></label>
                        <select name="certificate_type_id" required class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                            @foreach($types as $tp)
                                <option value="{{ $tp->id }}">{{ $tp->name }}</option>
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
                            <option value="">Predeterminada (Configurada en la actividad)</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" {{ ($config?->certificate_template_id == $tpl->id) ? 'selected' : '' }}>
                                    {{ $tpl->name }} ({{ $tpl->orientation === 'horizontal' ? 'Horizontal' : 'Vertical' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="issueRosterModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5">
                        <span>🚀 Confirmar y Emitir Todos</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
