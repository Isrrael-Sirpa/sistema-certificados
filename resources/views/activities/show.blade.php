@extends('layouts.app')

@section('page-title', 'Detalle de la Actividad')
@section('page-subtitle', 'Gestión de participantes y emisión de certificados para este evento')

@section('content')
<div class="space-y-6" x-data="{ batchModal: false, selectAll: false }">

    <!-- Activity Header Card -->
    <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="max-w-2xl">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-[#024966]/10 text-[#024966] border border-[#024966]/20">
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

                <h3 class="text-xl font-bold text-[#024966] leading-snug">{{ $activity->name }}</h3>
                <p class="text-xs text-slate-500 mt-2">{{ $activity->description ?? 'Sin descripción.' }}</p>
                
                <div class="mt-4 flex flex-wrap gap-4 text-xs text-slate-600">
                    <div>📅 <strong>Fechas:</strong> {{ $activity->formatted_date_range }}</div>
                    <div>⏱️ <strong>Carga:</strong> {{ $activity->academic_hours }} horas académicas</div>
                    <div>📍 <strong>Lugar:</strong> {{ $activity->location ?? 'No especificado' }}</div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button @click="batchModal = true"
                        class="px-4 py-2 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                    <span>⚡ Emisión Masiva de Certificados</span>
                </button>

                <a href="{{ route('certificates.create', ['activity_id' => $activity->id]) }}" 
                   class="px-4 py-2 bg-[#024966] hover:bg-[#01354a] text-white text-xs font-semibold rounded-xl shadow-xs transition-all">
                    + Emitir Individual
                </a>

                <a href="{{ route('activities.edit', $activity) }}" 
                   class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl">
                    ✏️
                </a>
            </div>
        </div>
    </div>

    <!-- Activity Certificate Personalization Section -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden" x-data="{ configOpen: false }">
        <div class="p-5 bg-[#024966] text-white flex items-center justify-between cursor-pointer"
             @click="configOpen = !configOpen">
            <div class="flex items-center gap-3">
                <span class="text-xl">🎨</span>
                <div>
                    <h4 class="text-sm font-bold flex items-center gap-2">
                        <span>Diseño y Personalización de Certificado para esta Actividad</span>
                        @if($config && $config->template)
                            <span class="bg-amber-400/20 text-amber-300 border border-amber-400/40 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                Plantilla: {{ $config->template->name }}
                            </span>
                        @endif
                    </h4>
                    <p class="text-xs text-slate-300 mt-0.5">Configure plantilla, colores específicos, redacción personalizada y resoluciones para este evento</p>
                </div>
            </div>

            <button type="button" class="text-xs font-semibold px-3 py-1.5 bg-white/10 hover:bg-white/20 rounded-xl transition-all border border-white/10">
                <span x-text="configOpen ? 'Ocultar Configuración ▲' : 'Personalizar Certificado ▼'"></span>
            </button>
        </div>

        <div x-show="configOpen" class="p-6 space-y-5 border-t border-slate-200">
            <form method="POST" action="{{ route('activities.certificate-config', $activity) }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <!-- Template Choice -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Plantilla Asignada a este Evento
                        </label>
                        <select name="certificate_template_id" 
                                class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                            <option value="">-- Utilizar Plantilla Predeterminada del Vicedecanato --</option>
                            @foreach($templates as $tpl)
                                <option value="{{ $tpl->id }}" {{ ($config?->certificate_template_id == $tpl->id) ? 'selected' : '' }}>
                                    {{ $tpl->name }} ({{ strtoupper($tpl->orientation) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Custom Title -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Título Especial de Otorgación (Opcional)
                        </label>
                        <input type="text" name="custom_title" value="{{ old('custom_title', $config?->custom_title) }}"
                               placeholder="Ej: CERTIFICADO DE EXCELENCIA ACADÉMICA"
                               class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                    </div>

                    <!-- Custom Primary Color -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Color Primario del Evento (Opcional)
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="primary_color" value="{{ old('primary_color', $config?->primary_color ?? '#024966') }}"
                                   class="w-9 h-9 rounded-xl border border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sobrescribe el color principal de la plantilla para esta actividad</span>
                        </div>
                    </div>

                    <!-- Custom Secondary Color -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Color Secundario / Bordes (Opcional)
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="color" name="secondary_color" value="{{ old('secondary_color', $config?->secondary_color ?? '#d4af37') }}"
                                   class="w-9 h-9 rounded-xl border border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Color de acentos y marco</span>
                        </div>
                    </div>

                    <!-- Custom Fields: Resolution & Modality -->
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Nro. de Resolución Facultativa o Convenio
                        </label>
                        <input type="text" name="resolution_number" 
                               value="{{ old('resolution_number', $config?->custom_fields_schema['resolucion'] ?? '') }}"
                               placeholder="Ej: Resol. Fac. N° 451/2026"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Modalidad del Evento
                        </label>
                        <input type="text" name="modality" 
                               value="{{ old('modality', $config?->custom_fields_schema['modalidad'] ?? '') }}"
                               placeholder="Ej: Modalidad Presencial y Virtual Sincrónica"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl">
                    </div>

                    <!-- Custom Body Text with Variables -->
                    <div class="md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Redacción Específica del Certificado para este Evento
                        </label>
                        <p class="text-[11px] text-slate-500 mb-2">
                            Puede redactar un texto único para este evento usando las variables dinámicas:
                            <code class="bg-slate-100 px-1 py-0.5 rounded text-[#024966]">@{{beneficiario.nombre}}</code>,
                            <code class="bg-slate-100 px-1 py-0.5 rounded text-[#024966]">@{{actividad.nombre}}</code>,
                            <code class="bg-slate-100 px-1 py-0.5 rounded text-[#024966]">@{{actividad.horas}}</code>,
                            <code class="bg-slate-100 px-1 py-0.5 rounded text-[#024966]">@{{actividad.fechas}}</code>.
                        </p>
                        <textarea name="custom_body_text" rows="3" 
                                  placeholder="Ej: Por su destacada y activa participación en calidad de @{{certificado.tipo}} en @{{actividad.nombre}}, con una carga horaria de @{{actividad.horas}} horas académicas..."
                                  class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">{{ old('custom_body_text', $config?->custom_body_text) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end pt-3 border-t border-slate-100">
                    <button type="submit" 
                            class="px-5 py-2.5 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                        Guardar Configuración de este Evento
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Certificates Issued List -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h4 class="text-xs font-bold text-[#024966] uppercase tracking-wider">Certificados Emitidos en este Evento</h4>
                <p class="text-xs text-slate-400">Total: {{ $activity->certificates->count() }} participante(s) certificados</p>
            </div>
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
                            <td class="px-5 py-3 font-mono font-bold text-[#32639E]">
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
                                <a href="{{ route('certificates.preview', $cert) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-[#32639E] hover:bg-slate-100 rounded inline-block" title="Vista Previa">
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

    <!-- Batch Issuance Modal -->
    <div x-show="batchModal" 
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
         style="display: none;">
        
        <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-6 space-y-4 max-h-[90vh] flex flex-col" @click.away="batchModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2">
                    <span class="text-2xl">⚡</span>
                    <h3 class="text-base font-bold text-[#024966]">Emisión Masiva de Certificados</h3>
                </div>
                <button @click="batchModal = false" class="text-slate-400 hover:text-slate-700 font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('activities.batch-issue', $activity) }}" class="flex-1 overflow-y-auto space-y-4">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Tipo de Certificado</label>
                        <select name="certificate_type_id" required class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl">
                            @foreach($types as $tp)
                                <option value="{{ $tp->id }}">{{ $tp->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Fecha de Emisión</label>
                        <input type="date" name="issue_date" value="{{ now()->toDateString() }}" required class="w-full text-xs py-1.5 px-3 border border-slate-300 rounded-xl">
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">
                            Seleccionar Beneficiarios del Padrón
                        </label>
                        <button type="button" @click="selectAll = !selectAll; document.querySelectorAll('.ben-checkbox').forEach(c => c.checked = selectAll)" 
                                class="text-xs text-[#32639E] font-semibold hover:underline">
                            Seleccionar / Deseleccionar Todos
                        </button>
                    </div>

                    <div class="border border-slate-200 rounded-xl max-h-60 overflow-y-auto p-3 divide-y divide-slate-100 bg-slate-50">
                        @foreach($beneficiaries as $ben)
                            <label class="flex items-center gap-3 py-2 px-2 hover:bg-white rounded-lg cursor-pointer transition-colors text-xs">
                                <input type="checkbox" name="beneficiary_ids[]" value="{{ $ben->id }}" class="ben-checkbox rounded text-[#024966] focus:ring-[#024966]">
                                <div class="flex-1">
                                    <span class="font-bold text-slate-800">{{ $ben->full_title_name }}</span>
                                    <span class="text-slate-400 font-mono ml-2">({{ $ben->formatted_document }})</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="batchModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-md transition-all">
                        Emitir Certificados Seleccionados
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
