@extends('layouts.app')

@section('page-title', 'Importación Masiva de Beneficiarios')
@section('page-subtitle', 'Carga de nóminas de estudiantes, docentes y participantes mediante archivos Excel (.xlsx) y CSV')

@section('content')
<div class="max-w-6xl mx-auto space-y-6">

    <!-- Top Guidance & Template Card -->
    <div class="bg-gradient-to-r from-brand via-brand to-brand-deep rounded-3xl p-6 sm:p-7 text-white shadow-xl border border-white/10 relative overflow-hidden">
        <div class="absolute -right-8 -bottom-10 opacity-10 pointer-events-none">
            <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-64 h-64 object-contain" alt="">
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-extrabold uppercase tracking-widest text-red-200 bg-red-900/40 px-2.5 py-0.5 rounded-full border border-red-500/30">
                        Padrón General • Tecnología
                    </span>
                    <span class="text-xs text-indigo-100 font-medium">Procesamiento inteligente sin formato estricto</span>
                </div>
                <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white">
                    Importación Automatizada de Participantes
                </h2>
                <p class="text-xs sm:text-sm text-slate-200 leading-relaxed">
                    Suba cualquier archivo Excel (<span class="font-mono text-amber-300 font-semibold">.xlsx</span>) o CSV (<span class="font-mono text-amber-300 font-semibold">.csv</span>). El sistema identificará de manera automática las columnas correspondientes por sinónimos comunes (<span class="italic">CI, Carnet, Nombres, Apellidos, Carrera, etc.</span>).
                </p>

                <div class="pt-2 flex flex-wrap items-center gap-2 text-[11px] text-sky-100">
                    <span class="font-bold text-white">Campos Obligatorios:</span>
                    <span class="bg-white/10 px-2 py-0.5 rounded-md border border-white/20">Carnet (CI)</span>
                    <span class="bg-white/10 px-2 py-0.5 rounded-md border border-white/20">Nombres</span>
                    <span class="bg-white/10 px-2 py-0.5 rounded-md border border-white/20">Apellidos</span>
                    <span class="text-slate-300">|</span>
                    <span class="text-slate-300">Opcionales:</span>
                    <span class="bg-white/5 px-2 py-0.5 rounded-md text-slate-300">Título/Grado</span>
                    <span class="bg-white/5 px-2 py-0.5 rounded-md text-slate-300">Carrera</span>
                    <span class="bg-white/5 px-2 py-0.5 rounded-md text-slate-300">Correo</span>
                    <span class="bg-white/5 px-2 py-0.5 rounded-md text-slate-300">Teléfono</span>
                </div>
            </div>

            <div class="shrink-0 flex flex-col sm:flex-row md:flex-col gap-2.5">
                <a href="{{ route('beneficiaries.template.download') }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-3 bg-white text-brand hover:bg-slate-100 text-xs font-bold rounded-2xl shadow-lg transition-all transform hover:-translate-y-0.5 cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    <span>Descargar Plantilla Oficial (.csv)</span>
                </a>
                <a href="{{ route('beneficiaries.index') }}"
                   class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-xs text-slate-300 hover:text-white transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Volver al Padrón</span>
                </a>
            </div>
        </div>
    </div>

    @if(empty($preview))
        <!-- FILE UPLOAD CARD (Initial Step) -->
        <div class="ft-panel bg-white rounded-3xl p-8 sm:p-10 shadow-xs border border-slate-200/80"
             x-data="{
                fileName: '',
                fileSize: '',
                isDragging: false,
                isSubmitting: false,
                handleFileSelect(event) {
                    const file = event.target.files[0];
                    if (file) {
                        this.fileName = file.name;
                        this.fileSize = (file.size / 1024 / 1024).toFixed(2) + ' MB';
                    }
                }
             }">

            <form action="{{ route('beneficiaries.import.preview') }}" method="POST" enctype="multipart/form-data" @submit="isSubmitting = true">
                @csrf

                <div class="max-w-2xl mx-auto space-y-6">
                    <div class="text-center space-y-1.5">
                        <h3 class="text-lg font-bold text-slate-800">Seleccione o arrastre el archivo</h3>
                        <p class="text-xs text-slate-500">Formatos admitidos: Microsoft Excel (<span class="font-semibold text-slate-700">.xlsx</span>) y Valores separados por comas (<span class="font-semibold text-slate-700">.csv</span>)</p>
                    </div>

                    <!-- Drag & Drop Zone -->
                    <div class="border-2 border-dashed rounded-3xl p-8 text-center transition-all cursor-pointer relative"
                         :class="isDragging ? 'border-brand-hover bg-brand-soft/60 ring-4 ring-sky-100' : 'border-slate-300 hover:border-slate-400 bg-slate-50/50'"
                         @dragover.prevent="isDragging = true"
                         @dragleave.prevent="isDragging = false"
                         @drop.prevent="isDragging = false; $refs.fileInput.files = $event.dataTransfer.files; handleFileSelect({target: $refs.fileInput})">

                        <input type="file" name="file" id="file" accept=".xlsx,.csv,.txt" required
                               x-ref="fileInput"
                               @change="handleFileSelect($event)"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">

                        <div class="space-y-3 pointer-events-none">
                            <div class="w-16 h-16 mx-auto rounded-2xl bg-sky-100 text-brand flex items-center justify-center shadow-inner">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                            </div>

                            <template x-if="!fileName">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">Arrastre su archivo aquí o haga clic para buscar</p>
                                    <p class="text-xs text-slate-400 mt-1">Límite de tamaño: hasta 10 MB por archivo</p>
                                </div>
                            </template>

                            <template x-if="fileName">
                                <div class="p-3 ft-panel bg-white rounded-2xl border border-emerald-200 inline-flex items-center gap-3 shadow-xs">
                                    <span class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">✓</span>
                                    <div class="text-left">
                                        <p class="text-xs font-bold text-slate-800" x-text="fileName"></p>
                                        <p class="text-[10px] text-slate-400" x-text="'Tamaño: ' + fileSize"></p>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Policy notice on duplicates -->
                    <div class="p-4 bg-amber-50 rounded-2xl border border-amber-200 flex items-start gap-3">
                        <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <div class="text-xs text-amber-900 leading-relaxed">
                            <span class="font-bold block mb-0.5">Política de Protección de Datos Registrados:</span>
                            Si el carnet de identidad (CI) de un estudiante ya existe en la base de datos, el sistema <span class="font-bold underline">lo omitirá automáticamente</span> para no alterar su información histórica. Si necesita actualizar algún dato de un participante específico, podrá hacerlo de forma manual e individual en el padrón.
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-center pt-2">
                        <button type="submit"
                                :disabled="!fileName || isSubmitting"
                                :class="(!fileName || isSubmitting) ? 'opacity-50 cursor-not-allowed bg-slate-300' : 'bg-brand hover:bg-brand-deep shadow-lg hover:shadow-xl cursor-pointer'"
                                class="px-8 py-3.5 text-white text-xs font-bold rounded-2xl transition-all inline-flex items-center gap-2">
                            <template x-if="!isSubmitting">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    <span>Analizar y Previsualizar Registros &rarr;</span>
                                </div>
                            </template>
                            <template x-if="isSubmitting">
                                <div class="flex items-center gap-2">
                                    <svg class="animate-spin w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                    <span>Analizando archivo...</span>
                                </div>
                            </template>
                        </button>
                    </div>
                </div>
            </form>
        </div>

    @else
        <!-- PREVIEW & CONFIRMATION CARD (Step 2) -->
        <div class="space-y-6">

            <!-- Summary KPI Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                <div class="ft-panel bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Total Filas</span>
                        <span class="text-2xl font-black text-slate-800">{{ $preview['summary']['total'] }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center font-bold">📋</div>
                </div>

                <div class="ft-panel bg-white rounded-2xl p-4 shadow-xs border border-emerald-200/80 bg-emerald-50/20 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-emerald-700 uppercase tracking-wider block">Listos para Registrar</span>
                        <span class="text-2xl font-black text-emerald-700">{{ $preview['summary']['ready'] }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">🟢</div>
                </div>

                <div class="ft-panel bg-white rounded-2xl p-4 shadow-xs border border-amber-200/80 bg-amber-50/20 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-amber-700 uppercase tracking-wider block">Duplicados (Se omitirán)</span>
                        <span class="text-2xl font-black text-amber-700">{{ $preview['summary']['duplicate_skip'] }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center font-bold">🟡</div>
                </div>

                <div class="ft-panel bg-white rounded-2xl p-4 shadow-xs border border-red-200/80 bg-red-50/20 flex items-center justify-between">
                    <div>
                        <span class="text-[11px] font-bold text-red-700 uppercase tracking-wider block">Filas con Error</span>
                        <span class="text-2xl font-black text-red-700">{{ $preview['summary']['error'] }}</span>
                    </div>
                    <div class="w-10 h-10 rounded-xl bg-red-100 text-red-700 flex items-center justify-center font-bold">🔴</div>
                </div>
            </div>

            <!-- Preview Data Table -->
            <div class="ft-panel bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4 bg-slate-50/50">
                    <div>
                        <h4 class="text-sm font-bold text-slate-800">Diagnóstico Previo de Registros</h4>
                        <p class="text-xs text-slate-500">Revise la detección antes de confirmar la inserción definitiva</p>
                    </div>

                    <div class="flex items-center gap-2">
                        <form action="{{ route('beneficiaries.import.cancel') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-colors cursor-pointer">
                                Descartar y subir otro
                            </button>
                        </form>

                        @if(!empty($preview['summary']['ready']))
                            <form action="{{ route('beneficiaries.import.store') }}" method="POST">
                                @csrf
                                <button type="submit" class="px-4 py-2 bg-[#047857] hover:bg-[#065f46] text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                                    <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span>Confirmar e Importar ({{ $preview['summary']['ready'] }})</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="overflow-x-auto max-h-[500px] overflow-y-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100/80 text-slate-600 uppercase font-bold sticky top-0 z-10 text-[10px] tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-center">Fila</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3">Carnet / CI</th>
                                <th class="px-4 py-3">Nombres y Apellidos</th>
                                <th class="px-4 py-3">Título</th>
                                <th class="px-4 py-3">Carrera / Unidad</th>
                                <th class="px-4 py-3">Correo</th>
                                <th class="px-4 py-3">Teléfono</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($preview['rows'] as $r)
                                <tr class="{{ $r['status'] === 'ready' ? 'hover:bg-emerald-50/30' : ($r['status'] === 'duplicate_skip' ? 'bg-amber-50/30' : 'bg-red-50/30') }}">
                                    <td class="px-4 py-2.5 text-center font-mono text-slate-400 text-[11px]">
                                        #{{ $r['row_number'] }}
                                    </td>
                                    <td class="px-4 py-2.5">
                                        @if($r['status'] === 'ready')
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 bg-emerald-100/70 px-2 py-0.5 rounded-full border border-emerald-200">
                                                <span>✓</span> Listo
                                            </span>
                                        @elseif($r['status'] === 'duplicate_skip')
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 bg-amber-100/70 px-2 py-0.5 rounded-full border border-amber-200" title="{{ $r['message'] }}">
                                                <span>⚠</span> Omitir (Existe)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[10px] font-bold text-red-700 bg-red-100/70 px-2 py-0.5 rounded-full border border-red-200" title="{{ $r['message'] }}">
                                                <span>✕</span> {{ $r['message'] }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-2.5 font-mono font-bold text-slate-800">
                                        {{ $r['data']['document_number'] ?: '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 font-medium text-slate-800">
                                        {{ trim(($r['data']['first_name'] ?? '') . ' ' . ($r['data']['last_name'] ?? '')) ?: '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-600">
                                        {{ $r['data']['academic_title'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-600">
                                        {{ $r['data']['institution_or_department'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-500 font-mono text-[11px]">
                                        {{ $r['data']['email'] ?? '-' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-slate-500 font-mono text-[11px]">
                                        {{ $r['data']['phone'] ?? '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <p class="text-xs text-slate-500">
                        Al presionar <strong class="text-slate-800">«Confirmar e Importar»</strong>, solo se crearán los registros marcados como <span class="text-emerald-700 font-bold">Listos</span>. Los duplicados serán omitidos de forma segura.
                    </p>

                    @if(!empty($preview['summary']['ready']))
                        <form action="{{ route('beneficiaries.import.store') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-6 py-2.5 bg-[#047857] hover:bg-[#065f46] text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer">
                                <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Confirmar e Importar {{ $preview['summary']['ready'] }} Beneficiarios</span>
                            </button>
                        </form>
                    @endif
                </div>
            </div>

        </div>
    @endif

</div>
@endsection
