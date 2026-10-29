@extends('layouts.app')

@section('page-title', 'Crear plantilla')
@section('page-subtitle', 'Elige un punto de partida y continúa en el diseñador visual')

@section('content')
<div class="max-w-5xl mx-auto space-y-6" x-data="{
    creationMode: @js(old('creation_mode', 'predefined')),
    selectedBase: @js(old('base_template_slug', 'institucional-clasica')),
    selectedOrientation: @js(old('orientation', 'landscape')),
    selectedSourceId: @js(old('source_template_id', $existingTemplates->first()?->id ?? ''))
}">

    <!-- Header Card -->
    <div class="ft-panel bg-white border border-border p-5 sm:p-6 flex flex-wrap items-center justify-between gap-4">
        <div class="min-w-0">
            <p class="ft-eyebrow">Un nuevo diseño</p>
            <h2 class="mt-2 text-xl font-bold text-brand">Elige cómo comenzar.</h2>
            <p class="mt-2 max-w-2xl text-sm text-muted">Después podrás editar textos, imágenes y composición sin perder el estilo de tu documento.</p>
        </div>
        <a href="{{ route('templates.index') }}" class="inline-flex min-h-11 items-center px-4 py-2 text-brand text-xs font-semibold rounded-xl border border-border hover:bg-surface-muted transition-colors">
            &larr; Volver al catálogo
        </a>
    </div>

    <!-- Mode Selector Tabs -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

        <!-- Mode 1: Predefined -->
        <button type="button" @click="creationMode = 'predefined'"
                :aria-pressed="creationMode === 'predefined'"
                class="p-5 rounded-2xl border-2 text-left transition-colors cursor-pointer"
                :class="creationMode === 'predefined' ? 'border-brand bg-brand-soft ring-1 ring-brand/15' : 'border-border bg-white hover:border-brand/40'">
            <div class="w-10 h-10 rounded-xl bg-white text-brand flex items-center justify-center text-lg mb-4" aria-hidden="true">
                ▧
            </div>
            <h3 class="text-sm font-bold text-brand">Desde un diseño base</h3>
            <p class="text-xs leading-relaxed text-muted mt-2">Parte de una composición institucional con logos, marco y textos listos para personalizar.</p>
            <span class="mt-3 inline-block text-[10px] font-semibold text-action">RECOMENDADO PARA COMENZAR</span>
        </button>

        <!-- Mode 2: Blank Canvas -->
        <button type="button" @click="creationMode = 'blank'"
                :aria-pressed="creationMode === 'blank'"
                class="p-5 rounded-2xl border-2 text-left transition-colors cursor-pointer"
                :class="creationMode === 'blank' ? 'border-brand bg-brand-soft ring-1 ring-brand/15' : 'border-border bg-white hover:border-brand/40'">
            <div class="w-10 h-10 rounded-xl bg-white text-brand flex items-center justify-center text-lg mb-4" aria-hidden="true">
                ＋
            </div>
            <h3 class="text-sm font-bold text-brand">Con un lienzo en blanco</h3>
            <p class="text-xs leading-relaxed text-muted mt-2">Añade tus propios elementos y construye la composición a tu medida desde el editor.</p>
        </button>

        <!-- Mode 3: Duplicate -->
        <button type="button" @click="creationMode = 'duplicate'"
                :aria-pressed="creationMode === 'duplicate'"
                class="p-5 rounded-2xl border-2 text-left transition-colors cursor-pointer"
                :class="creationMode === 'duplicate' ? 'border-brand bg-brand-soft ring-1 ring-brand/15' : 'border-border bg-white hover:border-brand/40'">
            <div class="w-10 h-10 rounded-xl bg-white text-brand flex items-center justify-center text-lg mb-4" aria-hidden="true">
                ▣
            </div>
            <h3 class="text-sm font-bold text-brand">Duplicando una plantilla</h3>
            <p class="text-xs leading-relaxed text-muted mt-2">Crea una variante de un diseño que ya utilizas. La plantilla de origen se conserva.</p>
        </button>

    </div>

    <form method="POST" action="{{ route('templates.store') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="creation_mode" :value="creationMode">

        <!-- Section 1: Basic Info -->
        <div class="ft-panel bg-white p-5 sm:p-6 border border-border space-y-5">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>1.</span> Datos Generales de la Plantilla
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label for="template-name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nombre de la Plantilla <span class="text-red-500">*</span>
                    </label>
                    <input id="template-name" type="text" name="name" value="{{ old('name') }}" required placeholder="Ej: Certificado de Seminario Internacional 2026"
                           class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                </div>

                <div>
                    <label for="template-category" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Categoría Institucional <span class="text-red-500">*</span>
                    </label>
                    <select id="template-category" name="category" required
                            class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                        @foreach($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label for="template-description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Descripción o Propósito Académico
                    </label>
                    <input id="template-description" type="text" name="description" value="{{ old('description') }}" placeholder="Ej: Plantilla oficial para talleres con sello seco y firma digital"
                           class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                </div>
            </div>
        </div>

        <!-- Section 2: Orientation Selection -->
        <div class="ft-panel bg-white p-5 sm:p-6 border border-border space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>2.</span> Orientación del Documento A4
            </h3>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="border-2 rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all focus-within:ring-2 focus-within:ring-accent"
                       :class="selectedOrientation === 'landscape' ? 'border-brand bg-blue-50/40 shadow-xs ring-1 ring-brand/20' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="orientation" value="landscape" x-model="selectedOrientation" class="sr-only">
                    <div class="w-16 h-11 shrink-0 bg-white border-2 border-slate-400 rounded flex items-center justify-center shadow-xs">
                        <span class="text-[10px] font-bold text-slate-500">297×210 mm</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-brand">Horizontal</h4>
                        <p class="mt-1 text-xs text-muted">297 × 210 mm. Ideal para certificados y reconocimientos.</p>
                    </div>
                </label>

                <label class="border-2 rounded-2xl p-4 flex items-center gap-4 cursor-pointer transition-all focus-within:ring-2 focus-within:ring-accent"
                       :class="selectedOrientation === 'portrait' ? 'border-brand bg-blue-50/40 shadow-xs ring-1 ring-brand/20' : 'border-slate-200 hover:border-slate-300'">
                    <input type="radio" name="orientation" value="portrait" x-model="selectedOrientation" class="sr-only">
                    <div class="w-11 h-16 shrink-0 bg-white border-2 border-slate-400 rounded flex items-center justify-center shadow-xs">
                        <span class="text-[9px] font-bold text-slate-500">210×297 mm</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-brand">Vertical</h4>
                        <p class="mt-1 text-xs text-muted">210 × 297 mm. Adecuado para constancias y diplomas.</p>
                    </div>
                </label>
            </div>
        </div>

        <!-- Section 3A: Predefined Baseline Templates -->
        <div x-show="creationMode === 'predefined'" class="ft-panel bg-white p-5 sm:p-6 border border-border space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>3.</span> Estilo Base Prediseñado (Punto de Partida)
            </h3>
            <p class="text-xs text-slate-500">Seleccione el diseño base sobre el cual cargaremos colores, marcos, logos y tipografías para afinarlo luego en el diseñador:</p>

            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($initialTemplates as $initTpl)
                    <label class="border-2 rounded-2xl p-4 flex flex-col justify-between cursor-pointer transition-all group focus-within:ring-2 focus-within:ring-accent"
                           :class="selectedBase === '{{ $initTpl['slug'] }}' ? 'border-brand bg-blue-50/30 shadow-md ring-2 ring-brand/20' : 'border-slate-200 hover:border-slate-300'">
                        <input type="radio" name="base_template_slug" value="{{ $initTpl['slug'] }}" x-model="selectedBase" class="sr-only">

                        <!-- Mini color bar -->
                        <div class="h-16 rounded-xl flex items-center justify-center p-2 mb-3 relative overflow-hidden"
                             style="background: linear-gradient(135deg, {{ $initTpl['configuration']['primary_color'] ?? '#024966' }} 0%, #01354a 100%);">
                            <span class="text-[10px] font-bold text-amber-300 tracking-wider uppercase text-center">
                                {{ $initTpl['name'] }}
                            </span>
                        </div>

                        <div>
                            <h4 class="text-xs font-bold text-slate-900 group-hover:text-brand">{{ $initTpl['name'] }}</h4>
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
        <div x-show="creationMode === 'blank'" x-cloak class="bg-accent-soft border border-accent/40 p-5 sm:p-6 rounded-2xl space-y-2">
            <div class="flex items-center gap-2 text-amber-900 font-bold text-sm">
                <span>🎨</span> Modo Lienzo en Blanco Activado
            </div>
            <p class="text-xs text-amber-800 leading-relaxed">
                Se generará una plantilla con un marco ornamental base y sin elementos predefinidos. Al ingresar al Diseñador Visual interactivo, podrá insertar títulos, logos institucionales (PNG/SVG/JPG), bloques de firmas, códigos QR y sellos secos a su entera medida mediante la paleta de herramientas y la biblioteca de recursos.
            </p>
        </div>

        <!-- Section 3C: Duplicate Selector -->
        <div x-show="creationMode === 'duplicate'" x-cloak class="ft-panel bg-white p-5 sm:p-6 border border-border space-y-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                <span>3.</span> Seleccionar Plantilla a Clonar
            </h3>

            @if($existingTemplates->isEmpty())
                <p class="text-xs text-slate-500">No hay plantillas existentes para duplicar. Utilice una plantilla base o lienzo en blanco.</p>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($existingTemplates as $exTpl)
                        <label class="border-2 rounded-xl p-4 flex items-center justify-between gap-3 cursor-pointer transition-all focus-within:ring-2 focus-within:ring-accent"
                               :class="selectedSourceId == '{{ $exTpl->id }}' ? 'border-brand bg-blue-50/40 ring-1 ring-brand' : 'border-slate-200 hover:border-slate-300'">
                            <input type="radio" name="source_template_id" value="{{ $exTpl->id }}" x-model="selectedSourceId" class="sr-only">
                            <div>
                                <h4 class="text-xs font-bold text-slate-800">{{ $exTpl->name }}</h4>
                                <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                                    <span class="capitalize">{{ $exTpl->category }}</span>
                                    <span>•</span>
                                    <span>{{ strtoupper($exTpl->orientation) }}</span>
                                    <span>•</span>
                                    <span>v{{ $exTpl->current_version }}.0</span>
                                </div>
                            </div>
                            <span class="text-xs" :class="selectedSourceId == '{{ $exTpl->id }}' ? 'text-brand font-bold' : 'text-slate-300'">✓</span>
                        </label>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Submit Button -->
        <div class="ft-panel flex flex-col-reverse sm:flex-row sm:items-center justify-between gap-4 border border-border bg-white p-5">
            <a href="{{ route('templates.index') }}" class="inline-flex min-h-11 items-center justify-center px-5 py-2.5 text-muted text-sm font-semibold rounded-xl hover:bg-surface-muted transition-colors">
                Cancelar
            </a>
            <button type="submit" class="min-h-11 px-6 py-3 bg-action hover:bg-action-hover text-white text-sm font-semibold rounded-xl transition-colors flex items-center justify-center gap-2">
                <span>Crear y abrir diseñador</span>
                <span>&rarr;</span>
            </button>
        </div>
    </form>

</div>
@endsection
