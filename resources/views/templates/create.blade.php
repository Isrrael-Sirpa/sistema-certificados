@extends('layouts.app')

@section('page-title', 'Crear Nueva Plantilla de Certificado')
@section('page-subtitle', 'Seleccione un estilo base institucional o configure una plantilla desde cero')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{ 
    creationMode: 'predefined', 
    selectedBase: 'institucional-clasica', 
    selectedOrientation: 'landscape',
    selectedSourceId: '{{ $existingTemplates->first()?->id ?? '' }}'
}">

    <!-- Header Card -->
    <div class="bg-[#024966] text-white p-6 rounded-2xl shadow-xs flex items-center justify-between">
        <div>
            <h2 class="text-base font-bold">Asistente de Creación de Plantilla Institucional</h2>
            <p class="text-xs text-slate-200 mt-0.5">Elija su método de inicio y personalice libremente cada elemento en el diseñador visual</p>
        </div>
        <a href="{{ route('templates.index') }}" class="px-3.5 py-1.5 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/10 transition-all">
            &larr; Volver al Catálogo
        </a>
    </div>

    <!-- Mode Selector Tabs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        
        <!-- Mode 1: Predefined -->
        <button type="button" @click="creationMode = 'predefined'"
                class="p-4 rounded-2xl border-2 text-left transition-all cursor-pointer bg-white"
                :class="creationMode === 'predefined' ? 'border-[#024966] bg-blue-50/40 shadow-sm ring-2 ring-[#024966]/20' : 'border-slate-200 hover:border-slate-300'">
            <div class="w-10 h-10 rounded-xl bg-blue-100 text-[#024966] flex items-center justify-center text-lg mb-2">
                🏛️
            </div>
            <h3 class="text-xs font-bold text-slate-900">Plantilla Institucional Base</h3>
            <p class="text-[11px] text-slate-500 mt-1">Comience con un diseño oficial preconfigurado (colores UMSA, logos y marcos).</p>
        </button>

        <!-- Mode 2: Blank Canvas -->
        <button type="button" @click="creationMode = 'blank'"
                class="p-4 rounded-2xl border-2 text-left transition-all cursor-pointer bg-white"
                :class="creationMode === 'blank' ? 'border-[#024966] bg-blue-50/40 shadow-sm ring-2 ring-[#024966]/20' : 'border-slate-200 hover:border-slate-300'">
            <div class="w-10 h-10 rounded-xl bg-amber-100 text-amber-800 flex items-center justify-center text-lg mb-2">
                ✨
            </div>
            <h3 class="text-xs font-bold text-slate-900">Lienzo en Blanco</h3>
            <p class="text-[11px] text-slate-500 mt-1">Hoja A4 limpia sin elementos para construir el diseño totalmente desde cero.</p>
        </button>

        <!-- Mode 3: Duplicate -->
        <button type="button" @click="creationMode = 'duplicate'"
                class="p-4 rounded-2xl border-2 text-left transition-all cursor-pointer bg-white"
                :class="creationMode === 'duplicate' ? 'border-[#024966] bg-blue-50/40 shadow-sm ring-2 ring-[#024966]/20' : 'border-slate-200 hover:border-slate-300'">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-lg mb-2">
                📋
            </div>
            <h3 class="text-xs font-bold text-slate-900">Duplicar Plantilla Existente</h3>
            <p class="text-[11px] text-slate-500 mt-1">Clone una plantilla existente y modifique sus textos o disposición.</p>
        </button>

    </div>

    <form method="POST" action="{{ route('templates.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="creation_mode" :value="creationMode">

        <!-- Section 1: Basic Info -->
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-5">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>1.</span> Datos Generales de la Plantilla
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nombre de la Plantilla <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="Ej: Certificado de Seminario Internacional 2026"
                           class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Categoría Institucional <span class="text-red-500">*</span>
                    </label>
                    <select name="category" required
                            class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}">{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Descripción o Propósito Académico
                    </label>
                    <input type="text" name="description" placeholder="Ej: Plantilla oficial para talleres con sello seco y firma digital"
                           class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                </div>
            </div>
        </div>

        <!-- Section 2: Orientation Selection -->
        <div class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>2.</span> Orientación del Documento A4
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="border-2 rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all"
                       :class="selectedOrientation === 'landscape' ? 'border-[#024966] bg-blue-50/40 shadow-xs ring-1 ring-[#024966]/20' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="orientation" value="landscape" x-model="selectedOrientation" class="sr-only">
                    <div class="w-16 h-11 bg-white border-2 border-slate-400 rounded flex items-center justify-center shadow-xs">
                        <span class="text-[10px] font-bold text-slate-500">297×210 mm</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Horizontal (Landscape) - Estándar Oficial</h4>
                        <p class="text-xs text-slate-500">Formato predilecto para certificados, diplomas, reconocimientos y seminarios.</p>
                    </div>
                </label>

                <label class="border-2 rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all"
                       :class="selectedOrientation === 'portrait' ? 'border-[#024966] bg-blue-50/40 shadow-xs ring-1 ring-[#024966]/20' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="orientation" value="portrait" x-model="selectedOrientation" class="sr-only">
                    <div class="w-11 h-16 bg-white border-2 border-slate-400 rounded flex items-center justify-center shadow-xs">
                        <span class="text-[9px] font-bold text-slate-500">210×297 mm</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Vertical (Portrait)</h4>
                        <p class="text-xs text-slate-500">Adecuado para constancias, actas solemnes y certificados de calificaciones.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Section 3A: Predefined Baseline Templates -->
        <div x-show="creationMode === 'predefined'" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>3.</span> Estilo Base Prediseñado (Punto de Partida)
            </h3>
            <p class="text-xs text-slate-500">Seleccione el diseño base sobre el cual cargaremos colores, marcos, logos y tipografías para afinarlo luego en el diseñador:</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($initialTemplates as $initTpl)
                    <label class="border-2 rounded-2xl p-4 flex flex-col justify-between cursor-pointer transition-all group"
                           :class="selectedBase === '{{ $initTpl['slug'] }}' ? 'border-[#024966] bg-blue-50/30 shadow-md ring-2 ring-[#024966]/20' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="base_template_slug" value="{{ $initTpl['slug'] }}" x-model="selectedBase" class="sr-only">

                        <!-- Mini color bar -->
                        <div class="h-16 rounded-xl flex items-center justify-center p-2 mb-3 relative overflow-hidden"
                             style="background: linear-gradient(135deg, {{ $initTpl['configuration']['primary_color'] ?? '#024966' }} 0%, #01354a 100%);">
                            <span class="text-[10px] font-bold text-amber-300 tracking-wider uppercase text-center">
                                {{ $initTpl['name'] }}
                            </span>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold text-slate-900 group-hover:text-[#024966]">{{ $initTpl['name'] }}</h4>
                            <p class="text-[11px] text-slate-500 mt-1 line-clamp-2">{{ $initTpl['description'] }}</p>
                        </div>

                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between text-[10px] text-slate-400">
                            <span class="font-semibold" style="color: {{ $initTpl['configuration']['secondary_color'] ?? '#d4af37' }};">
                                ● {{ ucfirst($initTpl['category']) }}
                            </span>
                            <span>{{ strtoupper($initTpl['orientation']) }}</span>
                        </div>
                    </label>
                @endforeach
            </div>
        </div>

        <!-- Section 3B: Blank Canvas Note -->
        <div x-show="creationMode === 'blank'" class="bg-amber-50/60 border border-amber-200 p-6 rounded-2xl space-y-2">
            <div class="flex items-center gap-2 text-amber-900 font-bold text-sm">
                <span>🎨</span> Modo Lienzo en Blanco Activado
            </div>
            <p class="text-xs text-amber-800 leading-relaxed">
                Se generará una plantilla con un marco ornamental base y sin elementos predefinidos. Al ingresar al Diseñador Visual interactivo, podrá insertar títulos, logos institucionales (PNG/SVG/JPG), bloques de firmas, códigos QR y sellos secos a su entera medida mediante la paleta de herramientas y la biblioteca de recursos.
            </p>
        </div>

        <!-- Section 3C: Duplicate Selector -->
        <div x-show="creationMode === 'duplicate'" class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>3.</span> Seleccionar Plantilla a Clonar
            </h3>
            
            @if($existingTemplates->isEmpty())
                <p class="text-xs text-slate-500">No hay plantillas existentes para duplicar. Utilice una plantilla base o lienzo en blanco.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($existingTemplates as $exTpl)
                        <label class="border-2 rounded-xl p-3 flex items-center justify-between cursor-pointer transition-all"
                               :class="selectedSourceId == '{{ $exTpl->id }}' ? 'border-[#024966] bg-blue-50/40 ring-1 ring-[#024966]' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="source_template_id" value="{{ $exTpl->id }}" x-model="selectedSourceId" class="sr-only">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">{{ $exTpl->name }}</h4>
                                <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                                    <span class="capitalize">{{ $exTpl->category }}</span>
                                    <span>•</span>
                                    <span>{{ strtoupper($exTpl->orientation) }}</span>
                                    <span>•</span>
                                    <span>v{{ $exTpl->current_version }}.0</span>
                                </div>
                            </div>
                            <span class="text-xs" :class="selectedSourceId == '{{ $exTpl->id }}' ? 'text-[#024966] font-bold' : 'text-slate-300'">✓</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-end gap-4">
            <a href="{{ route('templates.index') }}" class="px-5 py-2.5 border border-slate-300 text-slate-700 text-xs font-bold rounded-xl hover:bg-slate-100 transition-all">
                Cancelar
            </a>
            <button type="submit" class="px-6 py-2.5 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all transform hover:-translate-y-0.5 cursor-pointer flex items-center gap-2">
                <span>Continuar al Diseñador Visual</span>
                <span>&rarr;</span>
            </button>
        </div>
    </form>

</div>
@endsection
