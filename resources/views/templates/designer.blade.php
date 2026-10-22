@extends('layouts.app')

@section('page-title', 'Diseñador Visual de Certificados')
@section('page-subtitle', 'Estudio de diseño profesional de ' . $template->name . ' (v' . $template->current_version . '.0)')

@section('content')
@php
    $normalizedSchema = $schema;
    if (empty($normalizedSchema['elements'])) {
        $normalizedSchema = app(\App\Services\TemplateEngineService::class)->normalizeSchema($schema, $template->orientation);
    }

    $assetsData = [
        'logos' => $logos->map(function($a) {
            return ['id' => $a->id, 'name' => $a->name, 'url' => $a->url, 'category' => $a->type, 'type' => $a->type, 'format' => strtoupper(pathinfo($a->file_path, PATHINFO_EXTENSION))];
        })->values(),
        'signatures' => $signatures->map(function($a) {
            return ['id' => $a->id, 'name' => $a->name, 'url' => $a->url, 'category' => $a->type, 'type' => $a->type, 'format' => strtoupper(pathinfo($a->file_path, PATHINFO_EXTENSION))];
        })->values(),
        'stamps' => $stamps->map(function($a) {
            return ['id' => $a->id, 'name' => $a->name, 'url' => $a->url, 'category' => $a->type, 'type' => $a->type, 'format' => strtoupper(pathinfo($a->file_path, PATHINFO_EXTENSION))];
        })->values(),
        'watermarks' => $watermarks->map(function($a) {
            return ['id' => $a->id, 'name' => $a->name, 'url' => $a->url, 'category' => $a->type, 'type' => $a->type, 'format' => strtoupper(pathinfo($a->file_path, PATHINFO_EXTENSION))];
        })->values(),
        'backgrounds' => $backgrounds->map(function($a) {
            return ['id' => $a->id, 'name' => $a->name, 'url' => $a->url, 'category' => $a->type, 'type' => $a->type, 'format' => strtoupper(pathinfo($a->file_path, PATHINFO_EXTENSION))];
        })->values(),
        'decoratives' => $decoratives->map(function($a) {
            return ['id' => $a->id, 'name' => $a->name, 'url' => $a->url, 'category' => $a->type, 'type' => $a->type, 'format' => strtoupper(pathinfo($a->file_path, PATHINFO_EXTENSION))];
        })->values(),
    ];
@endphp

<!-- Safe Data Injection Script: Avoids breaking HTML attributes with raw JSON -->
<script>
window.__CERTIFICATE_STUDIO_CONFIG = {
    templateId: {{ $template->id }},
    name: @json($template->name),
    category: @json($template->category),
    orientation: @json($template->orientation),
    description: @json($template->description ?? ''),
    initialSchema: @json($normalizedSchema),
    initialElements: @json($normalizedSchema['elements'] ?? []),
    assets: @json($assetsData)
};
</script>

<div x-data="certificateStudio(window.__CERTIFICATE_STUDIO_CONFIG)" 
     @keydown.window="handleKeyDown($event)"
     class="space-y-4 select-none">

    <!-- ============================================================== -->
    <!-- 1. TOP STUDIO ACTION BAR                                       -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- 1. TOP STUDIO ACTION BAR (ESTÁTICA, SIN SUPERPOSICIÓN)         -->
    <!-- ============================================================== -->
    <header class="bg-white px-5 py-3 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4 relative z-20">
        
        <!-- Left: Back & Template Meta -->
        <div class="flex items-center gap-3">
            <a href="{{ route('templates.index') }}" 
               class="p-2 text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all cursor-pointer"
               title="Volver al catálogo de plantillas">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <div class="relative flex items-center">
                        <input type="text" x-model="name" @input="markDirty()"
                               class="text-sm sm:text-base font-bold text-[#024966] border border-slate-200 hover:border-slate-300 focus:border-[#024966] focus:ring-1 focus:ring-[#024966] pl-2 pr-6 py-1 rounded-lg transition-all w-48 sm:w-64 bg-slate-50/70"
                               title="Nombre de la plantilla">
                        <span class="absolute right-2 text-slate-400 text-xs pointer-events-none">✏️</span>
                    </div>
                    <span class="bg-blue-50 text-[#024966] border border-blue-200 text-[10px] font-bold px-2 py-0.5 rounded-full">
                        v{{ $template->current_version }}.0
                    </span>
                    <button type="button" @click="toggleOrientation()" 
                            class="bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 text-[10px] font-bold px-2.5 py-0.5 rounded-full uppercase cursor-pointer transition-all flex items-center gap-1"
                            title="Alternar entre orientación horizontal y vertical">
                        <span x-text="orientation === 'landscape' ? '📄 A4 Horizontal (297×210 mm)' : '📃 A4 Vertical (210×297 mm)'"></span>
                    </button>
                    <button type="button" @click="autoAdaptLayout()" 
                            class="bg-blue-50 hover:bg-blue-100 text-[#024966] border border-blue-200 text-[10px] font-bold px-2.5 py-0.5 rounded-full cursor-pointer transition-all flex items-center gap-1"
                            title="Reacomodar automáticamente los elementos para aprovechar de forma óptima el espacio A4 según la orientación seleccionada">
                        <svg class="w-3 h-3 text-[#024966]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        <span>Adaptar Proporciones</span>
                    </button>
                    <!-- Status indicator -->
                    <span class="flex items-center gap-1.5 text-[11px] font-medium transition-all"
                          :class="isDirty ? 'text-amber-600' : 'text-emerald-600'">
                        <span class="w-2 h-2 rounded-full" :class="isDirty ? 'bg-amber-500 animate-pulse' : 'bg-emerald-500'"></span>
                        <span x-text="isDirty ? 'Modificado sin guardar' : 'Guardado'"></span>
                    </span>
                </div>
                <p class="text-[11px] text-slate-500 mt-0.5">Estudio visual institucional • Haz clic en cualquier elemento del lienzo para seleccionarlo y editarlo</p>
            </div>
        </div>

        <!-- Center: Tools (Undo, Redo, Zoom, Grid, PDF Preview) -->
        <div class="flex items-center gap-1 bg-slate-100/90 p-1.5 rounded-xl border border-slate-200">
            <!-- Undo / Redo -->
            <button type="button" @click="undo()" :disabled="undoStack.length <= 1"
                    class="p-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all cursor-pointer"
                    title="Deshacer última acción (Ctrl+Z)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2m0 0l-4-4m4 4l4-4M3 10l4-4m-4 4l4 4"/></svg>
            </button>
            <button type="button" @click="redo()" :disabled="redoStack.length === 0"
                    class="p-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-white disabled:opacity-30 disabled:hover:bg-transparent transition-all cursor-pointer"
                    title="Rehacer acción (Ctrl+Y)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a5 5 0 00-5 5v2m0 0l4-4m-4 4l-4-4m21-2l-4-4m4 4l-4 4"/></svg>
            </button>

            <div class="h-4 w-px bg-slate-300 mx-1"></div>

            <!-- Zoom Controls -->
            <button type="button" @click="setZoom(zoom - 10)" :disabled="zoom <= 50"
                    class="p-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-white disabled:opacity-30 transition-all cursor-pointer"
                    title="Reducir zoom">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
            </button>
            <span class="text-xs font-mono font-bold text-slate-700 w-12 text-center" x-text="zoom + '%'"></span>
            <button type="button" @click="setZoom(zoom + 10)" :disabled="zoom >= 150"
                    class="p-1.5 rounded-lg text-slate-600 hover:text-slate-900 hover:bg-white disabled:opacity-30 transition-all cursor-pointer"
                    title="Aumentar zoom">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </button>
            <button type="button" @click="setZoom(100)"
                    class="px-1.5 py-1 text-[10px] font-bold text-slate-500 hover:text-slate-800 hover:bg-white rounded transition-all cursor-pointer"
                    title="Restablecer zoom al 100%">
                100%
            </button>

            <div class="h-4 w-px bg-slate-300 mx-1"></div>

            <!-- Grid Toggle -->
            <button type="button" @click="showGrid = !showGrid"
                    :class="showGrid ? 'bg-white text-[#024966] font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                    class="px-2 py-1 text-xs rounded-lg transition-all cursor-pointer flex items-center gap-1"
                    title="Alternar cuadrícula de alineación">
                <span>📏</span>
                <span class="hidden sm:inline">Guías</span>
            </button>

            <!-- Real PDF Preview Button -->
            <a href="{{ route('templates.preview', $template) }}" target="_blank"
               class="px-2.5 py-1 text-xs font-bold text-rose-700 hover:text-rose-900 hover:bg-rose-50 rounded-lg transition-all cursor-pointer flex items-center gap-1.5"
               title="Ver PDF oficial generado en tiempo real con DomPDF">
                <svg class="w-3.5 h-3.5 text-rose-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 14.5v-9l6 4.5-6 4.5z"/></svg>
                <span>Previsualizar PDF</span>
            </a>
        </div>

        <!-- Right: Save Actions -->
        <div class="flex items-center gap-2">
            <button type="button" @click="saveTemplate(false)"
                    class="px-4 py-2 border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl transition-all cursor-pointer flex items-center gap-1.5"
                    title="Guardar cambios en la versión actual v{{ $template->current_version }}.0">
                <span>💾 Guardar v{{ $template->current_version }}.0</span>
            </button>

            <button type="button" @click="saveTemplate(true)"
                    class="px-5 py-2 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all transform hover:-translate-y-0.5 cursor-pointer flex items-center gap-1.5"
                    title="Guardar como nueva versión incrementada v{{ $template->current_version + 1 }}.0">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Guardar como v{{ $template->current_version + 1 }}.0</span>
            </button>
        </div>
    </header>

    <!-- Hidden Form for submission -->
    <form id="studioForm" method="POST" action="{{ route('templates.designer.update', $template) }}" class="hidden">
        @csrf
        @method('PUT')
        <input type="hidden" name="name" :value="name">
        <input type="hidden" name="category" :value="category">
        <input type="hidden" name="orientation" :value="orientation">
        <input type="hidden" name="description" :value="description">
        <input type="hidden" name="create_new_version" id="createNewVersionInput" value="0">
        <input type="hidden" name="schema_json" id="schemaJsonInput">
    </form>

    <!-- Responsive View Mode Switcher (Visible on small screens) -->
    <div class="flex lg:hidden items-center justify-center bg-slate-100 p-1 rounded-xl gap-1 text-xs">
        <button type="button" @click="activePanel = 'tools'" :class="activePanel === 'tools' ? 'bg-white font-bold text-[#024966] shadow-xs' : 'text-slate-600'" class="px-3 py-1.5 rounded-lg flex-1 text-center">
            🛠️ Herramientas
        </button>
        <button type="button" @click="activePanel = 'canvas'" :class="activePanel === 'canvas' ? 'bg-white font-bold text-[#024966] shadow-xs' : 'text-slate-600'" class="px-3 py-1.5 rounded-lg flex-1 text-center">
            🎨 Lienzo A4
        </button>
        <button type="button" @click="activePanel = 'inspector'" :class="activePanel === 'inspector' ? 'bg-white font-bold text-[#024966] shadow-xs' : 'text-slate-600'" class="px-3 py-1.5 rounded-lg flex-1 text-center">
            🎛️ Propiedades
        </button>
    </div>

    <!-- ============================================================== -->
    <!-- 2. STUDIO THREE-COLUMN LAYOUT                                  -->
    <!-- ============================================================== -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        <!-- ========================================================== -->
        <!-- LEFT COLUMN: TOOLBOX, ASSET LIBRARY & LAYERS (3 COLS)      -->
        <!-- ========================================================== -->
        <aside class="lg:col-span-3 space-y-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col h-[780px]"
               :class="activePanel !== 'tools' ? 'hidden lg:flex' : 'flex'">
            
            <!-- Left Tabs Bar -->
            <div class="flex items-center border-b border-slate-200 bg-slate-50/80 p-1.5 gap-1">
                <button type="button" @click="leftTab = 'tools'"
                        :class="leftTab === 'tools' ? 'bg-white text-[#024966] font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex-1 py-1.5 text-xs rounded-lg transition-all text-center cursor-pointer">
                    🛠️ Elementos
                </button>
                <button type="button" @click="leftTab = 'library'"
                        :class="leftTab === 'library' ? 'bg-white text-[#024966] font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex-1 py-1.5 text-xs rounded-lg transition-all text-center cursor-pointer">
                    🖼️ Biblioteca
                </button>
                <button type="button" @click="leftTab = 'layers'"
                        :class="leftTab === 'layers' ? 'bg-white text-[#024966] font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="flex-1 py-1.5 text-xs rounded-lg transition-all text-center cursor-pointer">
                    📚 Capas (<span x-text="elements.length"></span>)
                </button>
            </div>

            <!-- Tab Content (Scrollable) -->
            <div class="p-3.5 space-y-4 overflow-y-auto flex-1 text-xs">

                <!-- -------------------------------------------------- -->
                <!-- SUB-TAB 1: HERRAMIENTAS DE INSERCIÓN              -->
                <!-- -------------------------------------------------- -->
                <div x-show="leftTab === 'tools'" class="space-y-4">
                    
                    <!-- Text Blocks Category -->
                    <div>
                        <h3 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <span>📝</span> Textos Dinámicos
                        </h3>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="addTextElement('text', 'UNIVERSIDAD MAYOR DE SAN ANDRÉS', 14, true)"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Título Superior</p>
                                <p class="text-[9px] text-slate-400">Universidad / Entidad</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'FACULTAD DE TECNOLOGÍA', 11, true)"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Subtítulo</p>
                                <p class="text-[9px] text-slate-400">Facultad / Carrera</p>
                            </button>

                            <button type="button" @click="addTextElement('text', '@{{certificado.tipo}}', 22, true)"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Tipo Certificado</p>
                                <p class="text-[9px] text-slate-400">Aprobación, Asistencia</p>
                            </button>

                            <button type="button" @click="addTextElement('text', '@{{beneficiario.nombre}}', 18, true)"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Beneficiario</p>
                                <p class="text-[9px] text-slate-400">Nombre completo</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'C.I. @{{beneficiario.documento}}', 11, false)"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Documento C.I.</p>
                                <p class="text-[9px] text-slate-400">Identificación</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'La Paz, @{{actividad.fechas}}', 10, false)"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Fechas / Lugar</p>
                                <p class="text-[9px] text-slate-400">Periodo académico</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'Por su destacada participación en la actividad académica \'@{{actividad.nombre}}\', con una carga horaria de @{{actividad.horas}} horas.', 11, false)"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group col-span-2">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Párrafo de Certificación</p>
                                <p class="text-[9px] text-slate-400">Texto libre con variables interpolables</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'El Vicedecano y las Autoridades de la Facultad de Tecnología de la Universidad Mayor de San Andrés, en uso de sus atribuciones confieren el presente:', 10, false, 'italic')"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group col-span-2">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Preámbulo Institucional</p>
                                <p class="text-[9px] text-slate-400">Fórmula solemne de otorgamiento en cursiva</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'Resolución H.C.F. Nº @{{acreditacion.resolucion}}', 9, false)"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Resolución / Aval</p>
                                <p class="text-[9px] text-slate-400">Res. HCF de acreditación</p>
                            </button>

                            <button type="button" @click="addTextElement('text', '@{{certificado.lugar_fecha_emision}}', 10, false)"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Lugar y Fecha Solemne</p>
                                <p class="text-[9px] text-slate-400">La Paz, 20 de septiembre...</p>
                            </button>
                        </div>
                    </div>

                    <!-- Visual Elements Category -->
                    <div>
                        <h3 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <span>🛡️</span> Elementos Institucionales
                        </h3>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="addQrElement()"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Código QR</p>
                                <p class="text-[9px] text-slate-400">Verificación pública</p>
                            </button>

                            <button type="button" @click="addSignatureBlock()"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Bloque de Firma</p>
                                <p class="text-[9px] text-slate-400">Línea + Nombre + Cargo</p>
                            </button>

                            <button type="button" @click="addDecorativeLine()"
                                    class="p-2 border border-slate-200 hover:border-[#024966] hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-[#024966] text-[11px]">Línea Ornamental</p>
                                <p class="text-[9px] text-slate-400">Separador visual</p>
                            </button>

                            <button type="button" @click="leftTab = 'library'"
                                    class="p-2 border border-dashed border-blue-300 bg-blue-50/50 hover:bg-blue-100/50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-[#024966] text-[11px]">+ Insertar Imagen</p>
                                <p class="text-[9px] text-slate-500">De la biblioteca</p>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Sample Toggle -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <label class="block text-[11px] font-bold text-slate-700 uppercase tracking-wider">Perfil de Ejemplo en Lienzo</label>
                        <select x-model="sampleProfile" @change="updateSampleProfile()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded-lg bg-white">
                            <option value="participant">Participante (Univ. Rodrigo Quispe Mamani)</option>
                            <option value="speaker">Disertante (Dr. Alejandro Morales Ramos)</option>
                            <option value="organizer">Comité Organizador (Lic. Carmen Vargas Luna)</option>
                            <option value="honor">Excelencia Académica (Univ. Valeria Torrico Siles)</option>
                        </select>
                    </div>

                </div>

                <!-- -------------------------------------------------- -->
                <!-- SUB-TAB 2: BIBLIOTECA INSTITUCIONAL               -->
                <!-- -------------------------------------------------- -->
                <div x-show="leftTab === 'library'" class="space-y-3">
                    
                    <!-- Category Filter -->
                    <div class="flex items-center gap-1 overflow-x-auto pb-1 text-[11px]">
                        <button type="button" @click="libFilter = 'all'"
                                :class="libFilter === 'all' ? 'bg-[#024966] text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Todos
                        </button>
                        <button type="button" @click="libFilter = 'logo'"
                                :class="libFilter === 'logo' ? 'bg-[#024966] text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Logos
                        </button>
                        <button type="button" @click="libFilter = 'signature'"
                                :class="libFilter === 'signature' ? 'bg-[#024966] text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Firmas
                        </button>
                        <button type="button" @click="libFilter = 'stamp'"
                                :class="libFilter === 'stamp' ? 'bg-[#024966] text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Sellos
                        </button>
                        <button type="button" @click="libFilter = 'watermark'"
                                :class="libFilter === 'watermark' ? 'bg-[#024966] text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Marcas
                        </button>
                    </div>

                    <!-- Direct Upload Link -->
                    <a href="{{ route('assets.index') }}" target="_blank"
                       class="w-full py-2 px-3 border border-dashed border-slate-300 hover:border-[#024966] text-slate-600 hover:text-[#024966] rounded-xl text-center flex items-center justify-center gap-1.5 transition-all block">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <span>Subir archivo (PNG/JPG/SVG)</span>
                    </a>

                    <!-- Grid of Assets -->
                    <div class="space-y-2">
                        <template x-for="item in filteredAssets" :key="item.id">
                            <div class="p-2 border border-slate-200 rounded-xl hover:border-blue-400 bg-white transition-all flex items-center justify-between gap-2 group">
                                <div class="flex items-center gap-2.5 overflow-hidden">
                                    <div class="w-10 h-10 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center p-1 shrink-0">
                                        <img :src="item.url" class="max-w-full max-h-full object-contain" :alt="item.name">
                                    </div>
                                    <div class="overflow-hidden">
                                        <p class="font-bold text-slate-800 text-[11px] truncate" x-text="item.name"></p>
                                        <div class="flex items-center gap-1 mt-0.5">
                                            <span class="text-[9px] font-mono px-1 rounded bg-slate-100 text-slate-600" x-text="item.type"></span>
                                            <span class="text-[9px] text-slate-400 capitalize" x-text="item.category"></span>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" @click="insertAssetToCanvas(item)"
                                        class="px-2.5 py-1.5 bg-blue-50 group-hover:bg-[#024966] text-[#024966] group-hover:text-white rounded-lg font-bold text-[10px] transition-all cursor-pointer shrink-0"
                                        title="Añadir este elemento al lienzo">
                                    + Insertar
                                </button>
                            </div>
                        </template>
                    </div>

                </div>

                <!-- -------------------------------------------------- -->
                <!-- SUB-TAB 3: PANEL DE CAPAS (LAYERS)                -->
                <!-- -------------------------------------------------- -->
                <div x-show="leftTab === 'layers'" class="space-y-2">
                    <p class="text-[10px] text-slate-400">Orden de superposición (z-index). Haga clic para seleccionar:</p>

                    <div class="space-y-1.5">
                        <template x-for="(el, index) in sortedLayers" :key="el.id">
                            <div class="p-2 rounded-xl border transition-all flex items-center justify-between gap-2 cursor-pointer"
                                 :class="selectedId === el.id ? 'border-[#024966] bg-blue-50/70 shadow-xs ring-1 ring-[#024966]' : 'border-slate-200 bg-white hover:bg-slate-50'"
                                 @click="selectElement(el.id)">
                                
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <span class="text-xs" x-text="getElementIcon(el.type)"></span>
                                    <span class="font-medium text-[11px] text-slate-700 truncate" x-text="el.name || el.type"></span>
                                </div>

                                <div class="flex items-center gap-1 shrink-0">
                                    <!-- Visibility Toggle -->
                                    <button type="button" @click.stop="toggleVisibility(el.id)" 
                                            class="p-1 text-slate-400 hover:text-slate-700 rounded transition-colors"
                                            :title="el.is_visible !== false ? 'Ocultar elemento' : 'Mostrar elemento'">
                                        <span x-text="el.is_visible !== false ? '👁️' : '🕶️'"></span>
                                    </button>

                                    <!-- Lock Toggle -->
                                    <button type="button" @click.stop="toggleLock(el.id)"
                                            class="p-1 text-slate-400 hover:text-slate-700 rounded transition-colors"
                                            :title="el.is_locked ? 'Desbloquear elemento' : 'Bloquear elemento'">
                                        <span x-text="el.is_locked ? '🔒' : '🔓'"></span>
                                    </button>

                                    <!-- Delete (if not mandatory) -->
                                    <button type="button" @click.stop="deleteElement(el.id)"
                                            :disabled="el.is_mandatory"
                                            class="p-1 text-slate-400 hover:text-rose-600 disabled:opacity-20 rounded transition-colors"
                                            title="Eliminar elemento">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

            </div>
        </aside>

        <!-- ========================================================== -->
        <!-- CENTER COLUMN: INTERACTIVE A4 CERTIFICATE CANVAS (6 COLS)  -->
        <!-- ========================================================== -->
        <main class="lg:col-span-6 space-y-3"
              :class="activePanel !== 'canvas' ? 'hidden lg:block' : 'block'">
            
            <!-- Canvas Header Bar with Coordinates Info -->
            <div class="flex items-center justify-between text-xs text-slate-500 px-1">
                <span class="font-bold text-[#024966] flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>Lienzo A4</span>
                    <span class="text-[11px] font-normal text-slate-400" x-text="orientation === 'landscape' ? '(297×210 mm Horizontal)' : '(210×297 mm Vertical)'"></span>
                </span>
                <div class="flex items-center gap-2 text-[11px] text-slate-400 font-mono">
                    <span x-show="selectedElement" x-text="'X: ' + Math.round(selectedElement?.x) + '%  Y: ' + Math.round(selectedElement?.y) + '%  W: ' + Math.round(selectedElement?.width) + '%'"></span>
                    <span class="bg-blue-50 text-[#024966] px-2 py-0.5 rounded font-sans font-bold" x-text="elements.length + ' elementos activos'"></span>
                </div>
            </div>

            <!-- Canvas Scroll Wrapper & Zoom Transformer -->
            <div class="bg-slate-200/80 p-4 sm:p-6 rounded-2xl border border-slate-300 flex items-center justify-center overflow-auto min-h-[620px] max-h-[780px] shadow-inner"
                 @click.self="selectElement(null)">
                
                <!-- The Scalable A4 Document Sheet -->
                <div id="certificateCanvasSheet"
                     @click.self="selectElement(null)"
                     class="bg-white shadow-2xl relative transition-transform origin-top select-none border border-slate-200/60"
                     :style="{
                         width: canvasWidthStyle,
                         height: canvasHeightStyle,
                         transform: 'scale(' + (zoom / 100) + ')',
                         fontFamily: schema.font_family || 'Helvetica',
                         color: schema.text_color || '#1e293b'
                     }">

                    <!-- Outer Decorative Border -->
                    <div class="absolute inset-4 rounded pointer-events-none transition-all duration-300"
                         :style="{
                             border: (schema.border_style === 'ornamental-double') ? '3px double ' + (schema.secondary_color || '#d4af37') :
                                     (schema.border_style === 'gold-filigree') ? '4px solid ' + (schema.secondary_color || '#d4af37') :
                                     (schema.border_style === 'none') ? 'none' :
                                     '2px solid ' + (schema.primary_color || '#024966')
                         }"></div>

                    <!-- Inner Decorative Border -->
                    <div class="absolute inset-6 rounded pointer-events-none transition-all duration-300"
                         :style="{
                             border: (schema.border_style === 'ornamental-double') ? '1px solid ' + (schema.primary_color || '#024966') :
                                     (schema.border_style === 'gold-filigree') ? '1px dashed ' + (schema.secondary_color || '#d4af37') :
                                     (schema.border_style === 'none') ? 'none' :
                                     '1px solid ' + (schema.secondary_color || '#d4af37'),
                             opacity: 0.7
                         }"></div>

                    <!-- 4 Ornamental Academic Corners -->
                    <template x-if="schema.border_style === 'ornamental-double' || schema.border_style === 'gold-filigree'">
                        <div class="pointer-events-none z-10">
                            <img src="{{ asset('assets/institutional/esquinero_tl.svg') }}" class="absolute top-[22px] left-[22px] w-11 h-11 pointer-events-none opacity-85" alt="Esquinero TL">
                            <img src="{{ asset('assets/institutional/esquinero_tr.svg') }}" class="absolute top-[22px] right-[22px] w-11 h-11 pointer-events-none opacity-85" alt="Esquinero TR">
                            <img src="{{ asset('assets/institutional/esquinero_bl.svg') }}" class="absolute bottom-[22px] left-[22px] w-11 h-11 pointer-events-none opacity-85" alt="Esquinero BL">
                            <img src="{{ asset('assets/institutional/esquinero_br.svg') }}" class="absolute bottom-[22px] right-[22px] w-11 h-11 pointer-events-none opacity-85" alt="Esquinero BR">
                        </div>
                    </template>

                    <!-- Optional Background Grid Layer -->
                    <div x-show="showGrid" class="absolute inset-0 pointer-events-none opacity-20"
                         style="background-image: radial-gradient(#024966 1px, transparent 1px); background-size: 20px 20px;"></div>

                    <!-- Snap Guideline: Center X (50%) -->
                    <div x-show="showSnapX" class="absolute top-0 bottom-0 left-1/2 w-px bg-rose-500 z-50 pointer-events-none"></div>
                    <!-- Snap Guideline: Center Y (50%) -->
                    <div x-show="showSnapY" class="absolute left-0 right-0 top-1/2 h-px bg-rose-500 z-50 pointer-events-none"></div>

                    <!-- ---------------------------------------------- -->
                    <!-- DYNAMIC CANVAS ELEMENTS LOOP                   -->
                    <!-- ---------------------------------------------- -->
                    <template x-for="el in elements" :key="el.id">
                        <div x-show="el.is_visible !== false"
                             :id="'canvas-element-' + el.id"
                             @mousedown="startDrag($event, el.id)"
                             @click.stop="selectElement(el.id)"
                             class="absolute transition-shadow group select-none cursor-move"
                             :class="{
                                 'ring-2 ring-[#024966] ring-offset-1 shadow-lg bg-blue-50/25': selectedId === el.id,
                                 'hover:outline hover:outline-1 hover:outline-blue-400': selectedId !== el.id,
                                 'pointer-events-none': el.is_locked
                             }"
                             :style="{
                                 left: el.x + '%',
                                 top: el.y + '%',
                                 width: el.width + '%',
                                 height: el.height ? (el.height + '%') : 'auto',
                                 zIndex: el.z_index || 10,
                                 opacity: el.opacity ?? 1,
                                 transform: 'rotate(' + (el.rotation || 0) + 'deg)'
                             }">

                            <!-- Selection Outline, Resize Handle & Element Type Badge -->
                            <template x-if="selectedId === el.id">
                                <div>
                                    <!-- Floating Element Badge -->
                                    <div class="absolute -top-6 left-0 px-2 py-0.5 bg-[#024966] text-white text-[9px] font-bold rounded shadow pointer-events-none whitespace-nowrap z-50 flex items-center gap-1 border border-blue-400">
                                        <span x-text="getElementIcon(el.type)"></span>
                                        <span x-text="el.name || el.type"></span>
                                    </div>
                                    <!-- Resize Handle (SE Corner) -->
                                    <div @mousedown.stop="startResize($event, el.id)"
                                         class="absolute -bottom-1.5 -right-1.5 w-4 h-4 bg-blue-600 border-2 border-white rounded-full shadow-md cursor-se-resize z-50 hover:scale-125 transition-transform"
                                         title="Arrastrar para redimensionar"></div>
                                    <div class="absolute -top-1 -right-1 w-2 h-2 bg-blue-500 rounded-full border border-white"></div>
                                    <div class="absolute -top-1 -left-1 w-2 h-2 bg-blue-500 rounded-full border border-white"></div>
                                    <div class="absolute -bottom-1 -left-1 w-2 h-2 bg-blue-500 rounded-full border border-white"></div>
                                </div>
                            </template>

                            <!-- ========================================== -->
                            <!-- ELEMENT CONTENT SWITCH                     -->
                            <!-- ========================================== -->

                            <!-- 1. TEXT ELEMENTS -->
                            <template x-if="isTextType(el.type)">
                                <div class="w-full h-full p-0.5 overflow-hidden"
                                     :style="{
                                         fontFamily: el.styles?.font_family || el.font_family || schema.font_family || 'Helvetica',
                                         fontSize: (el.styles?.font_size || el.font_size || 12) + 'px',
                                         fontWeight: el.styles?.font_weight || el.font_weight || 'normal',
                                         fontStyle: el.styles?.font_style || el.font_style || 'normal',
                                         textAlign: el.styles?.text_align || el.text_align || 'center',
                                         color: el.styles?.text_color || el.text_color || schema.text_color || '#1e293b',
                                         textTransform: el.styles?.text_transform || 'none',
                                         lineHeight: el.styles?.line_height || el.line_height || 1.3,
                                         letterSpacing: (el.styles?.letter_spacing || el.letter_spacing || 0) + 'px',
                                         borderBottom: (el.border_bottom || el.styles?.border_bottom) ? '2px solid ' + (el.border_bottom_color || el.styles?.border_bottom_color || schema.secondary_color || '#d4af37') : 'none'
                                     }"
                                     x-text="interpolateText(el.content || '')">
                                </div>
                            </template>

                            <!-- 2. IMAGE / LOGO / WATERMARK / STAMP -->
                            <template x-if="isImageType(el.type)">
                                <div class="w-full h-full flex items-center justify-center p-0.5 pointer-events-none overflow-hidden">
                                    <template x-if="resolveAssetUrl(el)">
                                        <img :src="resolveAssetUrl(el)" 
                                             class="w-full h-full filter drop-shadow-xs"
                                             :style="{ objectFit: el.styles?.object_fit || el.object_fit || 'contain' }"
                                             :alt="el.name || el.type">
                                    </template>
                                    <template x-if="!resolveAssetUrl(el)">
                                        <div class="w-full h-full border border-dashed border-slate-300 rounded flex items-center justify-center text-[10px] text-slate-400 bg-slate-50">
                                            <span x-text="el.name || 'Imagen'"></span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- 3. QR CODE ELEMENT -->
                            <template x-if="isQrType(el.type)">
                                <div class="w-full h-full p-1 flex items-center justify-center text-center pointer-events-none">
                                    <template x-if="orientation === 'portrait' || el.width >= 22">
                                        <div class="w-full h-full bg-slate-50/95 border border-slate-300 rounded-lg p-1.5 flex items-center justify-between gap-2 shadow-xs">
                                            <div class="h-full aspect-square bg-white border border-slate-300 rounded p-1 flex items-center justify-center shrink-0">
                                                <svg class="w-full h-full text-slate-900" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm13-2h3v2h-3v-2zm-3 2h2v2h-2v-2zm3 2h3v4h-3v-4zm-5 2h2v2h-2v-2zm2 2h3v-2h-3v2z"/>
                                                </svg>
                                            </div>
                                            <div class="flex-1 text-left overflow-hidden">
                                                <span class="inline-block text-[8px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-1.5 py-0.2 rounded border border-emerald-200">Verificación Oficial</span>
                                                <p class="text-[9px] font-mono font-bold text-slate-800 mt-0.5 truncate">CERT-2026-000042</p>
                                                <p class="text-[7px] text-slate-500 leading-tight truncate">Validez en línea UMSA</p>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="orientation !== 'portrait' && el.width < 22">
                                        <div class="w-full h-full flex flex-col items-center justify-center">
                                            <div class="w-full aspect-square max-h-[85%] bg-white border border-slate-300 rounded p-1 flex items-center justify-center shadow-xs">
                                                <svg class="w-full h-full text-slate-900" viewBox="0 0 24 24" fill="currentColor">
                                                    <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm13-2h3v2h-3v-2zm-3 2h2v2h-2v-2zm3 2h3v4h-3v-4zm-5 2h2v2h-2v-2zm2 2h3v-2h-3v2z"/>
                                                </svg>
                                            </div>
                                            <span class="text-[7px] font-mono text-slate-500 mt-0.5 truncate block max-w-full">
                                                CERT-2026-000042
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <!-- 4. SIGNATURE ELEMENT -->
                            <template x-if="isSignatureType(el.type)">
                                <div class="w-full h-full flex flex-col items-center justify-end text-center p-1 pointer-events-none">
                                    <template x-if="resolveAssetUrl(el)">
                                        <img :src="resolveAssetUrl(el)" class="h-10 max-h-[50%] object-contain mb-1" alt="Firma">
                                    </template>
                                    <div class="w-4/5 border-t border-slate-700 my-0.5"></div>
                                    <p class="text-[10px] font-bold text-slate-900 leading-tight truncate w-full" x-text="el.content || el.name || 'Ing. Aniceto Velarde Forest'"></p>
                                    <p class="text-[8px] text-slate-500 leading-tight truncate w-full" x-text="el.subtitle || el.position || 'Vicedecano de Facultad'"></p>
                                </div>
                            </template>

                            <!-- 5. DECORATIVE LINE -->
                            <template x-if="isLineType(el.type)">
                                <div class="w-full h-full flex items-center justify-center pointer-events-none">
                                    <div class="w-full"
                                         :style="{
                                             height: (el.styles?.height || el.height || 2) + 'px',
                                             background: el.styles?.background || ('linear-gradient(to right, transparent, ' + (schema.primary_color || '#024966') + ', ' + (schema.secondary_color || '#d4af37') + ', transparent)')
                                         }"></div>
                                </div>
                            </template>

                        </div>
                    </template>

                </div>

            </div>

            <!-- Canvas Footer Keyboard Shortcuts Guide -->
            <div class="flex items-center justify-between text-[11px] text-slate-400 px-2">
                <span>💡 Haga clic en cualquier elemento para seleccionarlo • Flechas del teclado para ajustar posición</span>
                <span>Supr: Eliminar • Ctrl+Z: Deshacer</span>
            </div>

        </main>

        <!-- ========================================================== -->
        <!-- RIGHT COLUMN: CONTEXTUAL PROPERTIES INSPECTOR (3 COLS)     -->
        <!-- ========================================================== -->
        <aside class="lg:col-span-3 space-y-3 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden flex flex-col h-[780px]"
               :class="activePanel !== 'inspector' ? 'hidden lg:flex' : 'flex'">
            
            <!-- Right Inspector Header -->
            <div class="border-b border-slate-200 bg-slate-50/80 px-4 py-3 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold text-[#024966] uppercase tracking-wider flex items-center gap-1.5">
                        <span>🎛️</span>
                        <span x-text="selectedElement ? 'Propiedades del Elemento' : 'Ajustes Globales del Lienzo'"></span>
                    </h2>
                    <p class="text-[10px] text-slate-400 mt-0.5" x-text="selectedElement ? (selectedElement.name || selectedElement.type) : 'Seleccione un elemento del lienzo para editarlo'"></p>
                </div>
                <template x-if="selectedElement">
                    <button type="button" @click="selectElement(null)" class="text-slate-400 hover:text-slate-700 text-xs p-1 rounded cursor-pointer" title="Deseleccionar">✕</button>
                </template>
            </div>

            <!-- Inspector Scrollable Body -->
            <div class="p-3.5 space-y-4 overflow-y-auto flex-1 text-xs">

                <!-- ================================================== -->
                <!-- SCENARIO A: AN ELEMENT IS SELECTED                 -->
                <!-- ================================================== -->
                <template x-if="selectedElement">
                    <div class="space-y-4">
                        
                        <!-- Element Quick Actions: Align, Layer, Clone -->
                        <div class="grid grid-cols-5 gap-1 p-1 bg-slate-100 rounded-xl text-[10px] font-bold">
                            <button type="button" @click="alignElementCenter('x')"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-[#024966] hover:bg-white transition-all text-center cursor-pointer"
                                    title="Centrar horizontalmente en el lienzo">
                                ↔ Centro X
                            </button>
                            <button type="button" @click="alignElementCenter('y')"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-[#024966] hover:bg-white transition-all text-center cursor-pointer"
                                    title="Centrar verticalmente en el lienzo">
                                ↕ Centro Y
                            </button>
                            <button type="button" @click="duplicateElement(selectedElement.id)"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-[#024966] hover:bg-white transition-all text-center cursor-pointer"
                                    title="Duplicar este elemento">
                                📋 Clonar
                            </button>
                            <button type="button" @click="bringToFront(selectedElement.id)"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-[#024966] hover:bg-white transition-all text-center cursor-pointer"
                                    title="Traer al frente (capa superior)">
                                ⬆ Frente
                            </button>
                            <button type="button" @click="sendToBack(selectedElement.id)"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-[#024966] hover:bg-white transition-all text-center cursor-pointer"
                                    title="Enviar al fondo (detrás de otros)">
                                ⬇ Fondo
                            </button>
                        </div>

                        <!-- 1. Geometry Coordinates (X, Y, W, H) -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Geometría y Posición (%)</span>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-0.5">Posición X</label>
                                    <input type="number" step="0.5" x-model.number="selectedElement.x" @input="markDirty()"
                                           class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-0.5">Posición Y</label>
                                    <input type="number" step="0.5" x-model.number="selectedElement.y" @input="markDirty()"
                                           class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-0.5">Ancho (W)</label>
                                    <input type="number" step="0.5" x-model.number="selectedElement.width" @input="markDirty()"
                                           class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white font-mono">
                                </div>
                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-0.5">Alto (H)</label>
                                    <input type="number" step="0.5" x-model.number="selectedElement.height" @input="markDirty()"
                                           class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white font-mono">
                                </div>
                            </div>

                            <!-- Opacity Slider -->
                            <div class="pt-1">
                                <div class="flex items-center justify-between text-[10px] text-slate-500 mb-0.5">
                                    <span>Opacidad</span>
                                    <span class="font-mono font-bold" x-text="Math.round((selectedElement.opacity ?? 1) * 100) + '%'"></span>
                                </div>
                                <input type="range" min="0.02" max="1" step="0.02" x-model.number="selectedElement.opacity" @input="markDirty()"
                                       class="w-full accent-[#024966] cursor-pointer">
                            </div>
                        </div>

                        <!-- 2. Text Content & Variables (For Text Elements) -->
                        <template x-if="isTextType(selectedElement.type)">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Contenido de Texto</label>
                                <textarea rows="3" x-model="selectedElement.content" @input="markDirty()"
                                          class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded-lg bg-white"></textarea>
                                
                                <!-- Categorized Dynamic Variable Chips -->
                                <div class="space-y-1.5 pt-1">
                                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider">Variables Dinámicas:</span>
                                    
                                    <div>
                                        <span class="text-[9px] text-slate-500 font-semibold block mb-0.5">👤 Beneficiario:</span>
                                        <div class="flex flex-wrap gap-1">
                                            <button type="button" @click="insertVariable('@{{beneficiario.nombre}}')"
                                                    class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-[#024966] rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Nombre completo del participante">
                                                + @{{beneficiario.nombre}}
                                            </button>
                                            <button type="button" @click="insertVariable('@{{beneficiario.documento}}')"
                                                    class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-[#024966] rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Documento / C.I.">
                                                + @{{beneficiario.documento}}
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <span class="text-[9px] text-slate-500 font-semibold block mb-0.5">🎓 Actividad Académica:</span>
                                        <div class="flex flex-wrap gap-1">
                                            <button type="button" @click="insertVariable('@{{actividad.nombre}}')"
                                                    class="px-2 py-0.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Título de la actividad">
                                                + @{{actividad.nombre}}
                                            </button>
                                            <button type="button" @click="insertVariable('@{{actividad.horas}}')"
                                                    class="px-2 py-0.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Carga horaria en horas">
                                                + @{{actividad.horas}}
                                            </button>
                                            <button type="button" @click="insertVariable('@{{actividad.fechas}}')"
                                                    class="px-2 py-0.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Fechas del evento">
                                                + @{{actividad.fechas}}
                                            </button>
                                            <button type="button" @click="insertVariable('@{{acreditacion.resolucion}}')"
                                                    class="px-2 py-0.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-emerald-800 rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Resolución Facultativa de Acreditación">
                                                + @{{acreditacion.resolucion}}
                                            </button>
                                        </div>
                                    </div>

                                    <div>
                                        <span class="text-[9px] text-slate-500 font-semibold block mb-0.5">📜 Certificado:</span>
                                        <div class="flex flex-wrap gap-1">
                                            <button type="button" @click="insertVariable('@{{certificado.tipo}}')"
                                                    class="px-2 py-0.5 bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-800 rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Tipo de certificación">
                                                + @{{certificado.tipo}}
                                            </button>
                                            <button type="button" @click="insertVariable('@{{certificado.codigo}}')"
                                                    class="px-2 py-0.5 bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-800 rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Código oficial correlativo">
                                                + @{{certificado.codigo}}
                                            </button>
                                            <button type="button" @click="insertVariable('@{{certificado.lugar_fecha_emision}}')"
                                                    class="px-2 py-0.5 bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-800 rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Lugar y fecha formal de emisión">
                                                + @{{certificado.lugar_fecha_emision}}
                                            </button>
                                            <button type="button" @click="insertVariable('@{{certificado.fecha_emision_larga}}')"
                                                    class="px-2 py-0.5 bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-800 rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Fecha larga de emisión">
                                                + @{{certificado.fecha_emision_larga}}
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- 3. Typography Styles (For Text Elements) -->
                        <template x-if="isTextType(selectedElement.type)">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tipografía & Estilos</span>
                                
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Tamaño (px)</label>
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="changeFontSize(-1)" class="w-7 h-7 bg-slate-100 hover:bg-slate-200 rounded border border-slate-300 font-bold text-slate-700 cursor-pointer">-</button>
                                            <input type="number" min="6" max="72" x-model.number="selectedElement.styles.font_size" 
                                                   @input="selectedElement.font_size = selectedElement.styles.font_size; markDirty();"
                                                   class="w-full py-1 px-1 text-center text-xs border border-slate-300 rounded bg-white font-mono font-bold">
                                            <button type="button" @click="changeFontSize(1)" class="w-7 h-7 bg-slate-100 hover:bg-slate-200 rounded border border-slate-300 font-bold text-slate-700 cursor-pointer">+</button>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Alineación</label>
                                        <div class="grid grid-cols-4 gap-0.5 p-0.5 bg-slate-100 rounded-lg border border-slate-200 h-7 items-center">
                                            <button type="button" @click="setTextAlign('left')" :class="(selectedElement.styles?.text_align || selectedElement.text_align) === 'left' ? 'bg-white shadow-xs text-[#024966] font-bold' : 'text-slate-500 hover:text-slate-800'" class="py-0.5 text-xs rounded text-center cursor-pointer" title="Izquierda">⫷</button>
                                            <button type="button" @click="setTextAlign('center')" :class="(selectedElement.styles?.text_align || selectedElement.text_align || 'center') === 'center' ? 'bg-white shadow-xs text-[#024966] font-bold' : 'text-slate-500 hover:text-slate-800'" class="py-0.5 text-xs rounded text-center cursor-pointer" title="Centro">≡</button>
                                            <button type="button" @click="setTextAlign('right')" :class="(selectedElement.styles?.text_align || selectedElement.text_align) === 'right' ? 'bg-white shadow-xs text-[#024966] font-bold' : 'text-slate-500 hover:text-slate-800'" class="py-0.5 text-xs rounded text-center cursor-pointer" title="Derecha">⫸</button>
                                            <button type="button" @click="setTextAlign('justify')" :class="(selectedElement.styles?.text_align || selectedElement.text_align) === 'justify' ? 'bg-white shadow-xs text-[#024966] font-bold' : 'text-slate-500 hover:text-slate-800'" class="py-0.5 text-xs rounded text-center cursor-pointer" title="Justificado">☵</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Peso</label>
                                        <select x-model="selectedElement.styles.font_weight" 
                                                @change="selectedElement.font_weight = selectedElement.styles.font_weight; markDirty();"
                                                class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white">
                                            <option value="normal">Normal</option>
                                            <option value="bold">Negrita (Bold)</option>
                                            <option value="800">Extra Bold</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Estilo</label>
                                        <select x-model="selectedElement.styles.font_style" 
                                                @change="selectedElement.font_style = selectedElement.styles.font_style; markDirty();"
                                                class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white">
                                            <option value="normal">Normal</option>
                                            <option value="italic">Cursiva (Italic)</option>
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-0.5">Color de Texto</label>
                                    <div class="flex items-center gap-2">
                                        <input type="color" x-model="selectedElement.styles.text_color" 
                                               @input="selectedElement.text_color = selectedElement.styles.text_color; markDirty();"
                                               class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                                        <input type="text" x-model="selectedElement.styles.text_color" 
                                               @input="selectedElement.text_color = selectedElement.styles.text_color; markDirty();"
                                               class="flex-1 py-1 px-2 text-xs border border-slate-300 rounded font-mono uppercase bg-white">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- 4. Asset Selection (For Image / Logo / Stamp / Signature) -->
                        <template x-if="isImageType(selectedElement.type) || isSignatureType(selectedElement.type)">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Recurso Institucional</label>
                                <select x-model="selectedElement.asset_id" 
                                        @change="
                                            const found = filteredAssets.find(a => a.id == selectedElement.asset_id);
                                            if (found) {
                                                selectedElement.asset_url = found.url;
                                                selectedElement.name = found.name;
                                            }
                                            markDirty();
                                        "
                                        class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded-lg bg-white">
                                    <option value="">-- Usar Recurso Actual --</option>
                                    <template x-for="item in filteredAssets" :key="item.id">
                                        <option :value="item.id" x-text="item.name + ' (' + item.type + ')'"></option>
                                    </template>
                                </select>

                                <div>
                                    <label class="block text-[10px] text-slate-500 mb-0.5">Ajuste de Imagen (Object Fit)</label>
                                    <select x-model="selectedElement.styles.object_fit" @change="markDirty()"
                                            class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white">
                                        <option value="contain">Contener proporcional (Contain)</option>
                                        <option value="cover">Cubrir espacio (Cover)</option>
                                        <option value="fill">Llenar / Ajustar</option>
                                    </select>
                                </div>
                            </div>
                        </template>

                        <!-- Delete Element Button -->
                        <div class="pt-2">
                            <button type="button" @click="deleteElement(selectedElement.id)"
                                    :disabled="selectedElement.is_mandatory"
                                    class="w-full py-2 px-3 border border-rose-200 bg-rose-50 hover:bg-rose-100 disabled:opacity-40 text-rose-700 font-bold rounded-xl text-xs transition-all cursor-pointer flex items-center justify-center gap-1.5">
                                <span>🗑️ Eliminar Elemento</span>
                            </button>
                        </div>

                    </div>
                </template>

                <!-- ================================================== -->
                <!-- SCENARIO B: NO ELEMENT SELECTED (CANVAS SETTINGS)  -->
                <!-- ================================================== -->
                <template x-if="!selectedElement">
                    <div class="space-y-4">
                        
                        <!-- General Template Settings -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Lienzo y Formato General</span>
                            
                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Categoría</label>
                                <select x-model="category" @change="markDirty()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded bg-white">
                                    <option value="institucional">Institucional</option>
                                    <option value="seminario">Seminario</option>
                                    <option value="capacitacion">Capacitación</option>
                                    <option value="reconocimiento">Reconocimiento</option>
                                    <option value="disertante">Disertante</option>
                                    <option value="aprobacion">Aprobación</option>
                                    <option value="academica">Académica</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Orientación A4</label>
                                <select x-model="orientation" @change="markDirty()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded bg-white">
                                    <option value="landscape">Horizontal (Landscape - 297×210 mm)</option>
                                    <option value="portrait">Vertical (Portrait - 210×297 mm)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Marco Ornamental</label>
                                <select x-model="schema.border_style" @change="markDirty()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded bg-white">
                                    <option value="ornamental-double">Doble Marco Clásico Institucional</option>
                                    <option value="gold-filigree">Filigrana Dorada de Honor</option>
                                    <option value="modern-line">Línea Sólida Moderna</option>
                                    <option value="none">Sin Marco</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Tipografía Global (DomPDF)</label>
                                <select x-model="schema.font_family" @change="markDirty()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded bg-white">
                                    <option value="Helvetica">Helvetica (Sans Serif Institucional)</option>
                                    <option value="DejaVu Serif">DejaVu Serif (Formal Solemne)</option>
                                    <option value="DejaVu Sans">DejaVu Sans (Limpio Moderno)</option>
                                    <option value="Times-Roman">Times-Roman (Académico Tradicional)</option>
                                    <option value="Courier">Courier (Monospace Oficial)</option>
                                </select>
                            </div>
                        </div>

                        <!-- Global Palettes -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Paleta Oficial de Colores</span>
                            
                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Color Primario (Azul UMSA)</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="schema.primary_color" @input="markDirty()" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                                    <input type="text" x-model="schema.primary_color" @input="markDirty()" class="flex-1 py-1 px-2 text-xs border border-slate-300 rounded font-mono uppercase bg-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Color Secundario (Dorado Institucional)</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="schema.secondary_color" @input="markDirty()" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                                    <input type="text" x-model="schema.secondary_color" @input="markDirty()" class="flex-1 py-1 px-2 text-xs border border-slate-300 rounded font-mono uppercase bg-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Color de Texto Base</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="schema.text_color" @input="markDirty()" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                                    <input type="text" x-model="schema.text_color" @input="markDirty()" class="flex-1 py-1 px-2 text-xs border border-slate-300 rounded font-mono uppercase bg-white">
                                </div>
                            </div>
                        </div>

                    </div>
                </template>

            </div>
        </aside>

    </div>

</div>

<!-- Studio Alpine.js Controller Definition -->
<script>
function certificateStudio(config) {
    return {
        templateId: config.templateId,
        name: config.name || '',
        category: config.category || 'institucional',
        orientation: config.orientation || 'landscape',
        description: config.description || '',
        schema: config.initialSchema || {},
        elements: config.initialElements || [],
        assets: config.assets || {},
        
        // Studio Interface State
        leftTab: 'tools',
        libFilter: 'all',
        selectedId: null,
        zoom: 100,
        showGrid: false,
        showSnapX: false,
        showSnapY: false,
        isDirty: false,
        activePanel: 'canvas', // For responsive smaller screens
        
        // Drag and Resize Tracking
        isDragging: false,
        isResizing: false,
        activeElementId: null,
        dragStartX: 0,
        dragStartY: 0,
        initialElementX: 0,
        initialElementY: 0,
        initialElementW: 0,
        initialElementH: 0,

        // History Undo / Redo
        undoStack: [],
        redoStack: [],

        // Sample Profile Data for Simulation
        sampleProfile: 'participant',
        sampleData: {
            beneficiaryName: 'Univ. Rodrigo Quispe Mamani',
            beneficiaryDoc: '8493021 LP',
            activityName: 'Seminario Internacional de Inteligencia Artificial y Ciencia de Datos',
            activityHours: '40',
            activityDates: 'del 15 al 20 de Septiembre de 2026',
            certificateType: 'ASISTENCIA Y APROBACIÓN',
            certificateCode: 'CERT-2026-000042',
            issuePlaceDate: 'La Paz, 20 de Septiembre de 2026',
            issueDateLong: '20 de Septiembre de 2026',
            resolution: '045/2026'
        },

        init() {
            // Ensure schema structure defaults
            if (!this.schema.primary_color) this.schema.primary_color = '#024966';
            if (!this.schema.secondary_color) this.schema.secondary_color = '#d4af37';
            if (!this.schema.border_style) this.schema.border_style = 'ornamental-double';
            if (!this.schema.font_family) this.schema.font_family = 'Helvetica';
            if (!this.schema.text_color) this.schema.text_color = '#1e293b';

            // Normalize each element in elements array
            this.elements.forEach(el => {
                if (!el.styles) el.styles = {};
                if (!el.type) el.type = 'text';
                if (typeof el.x === 'undefined') el.x = 10;
                if (typeof el.y === 'undefined') el.y = 10;
                if (typeof el.width === 'undefined') el.width = 80;
                if (typeof el.height === 'undefined') el.height = 8;
                if (el.font_size && !el.styles.font_size) el.styles.font_size = el.font_size;
                if (el.font_weight && !el.styles.font_weight) el.styles.font_weight = el.font_weight;
                if (el.text_color && !el.styles.text_color) el.styles.text_color = el.text_color;
                if (el.text_align && !el.styles.text_align) el.styles.text_align = el.text_align;
            });

            // Global mousemove and mouseup listeners
            window.addEventListener('mousemove', (e) => this.handleMouseMove(e));
            window.addEventListener('mouseup', () => this.handleMouseUp());

            // Save initial state snapshot
            this.pushHistoryState();
        },

        // Computed getters
        get selectedElement() {
            return this.elements.find(e => e.id === this.selectedId) || null;
        },

        get sortedLayers() {
            return [...this.elements].sort((a, b) => (b.z_index || 0) - (a.z_index || 0));
        },

        get canvasWidthStyle() {
            return this.orientation === 'landscape' ? '820px' : '580px';
        },

        get canvasHeightStyle() {
            return this.orientation === 'landscape' ? '580px' : '820px';
        },

        get filteredAssets() {
            const all = [
                ...(this.assets.logos || []),
                ...(this.assets.signatures || []),
                ...(this.assets.stamps || []),
                ...(this.assets.watermarks || []),
                ...(this.assets.backgrounds || []),
                ...(this.assets.decoratives || [])
            ];
            if (this.libFilter === 'all') return all;
            return all.filter(a => a.category === this.libFilter || (a.category && a.category.includes(this.libFilter)));
        },

        // Helper type checkers
        isTextType(type) {
            return ['header_title', 'header_subtitle', 'certificate_type', 'beneficiary_name', 'beneficiary_document', 'body_text', 'activity_dates', 'metadata_code', 'text'].includes(type);
        },

        isImageType(type) {
            return ['logo', 'watermark', 'stamp', 'image', 'background'].includes(type);
        },

        isQrType(type) {
            return ['qr', 'qr_code'].includes(type);
        },

        isSignatureType(type) {
            return ['signature', 'signature_block'].includes(type);
        },

        isLineType(type) {
            return ['divider', 'decorative_line', 'line'].includes(type);
        },

        getElementIcon(type) {
            const icons = {
                header_title: '🏛️',
                header_subtitle: '🏢',
                certificate_type: '📜',
                beneficiary_name: '👤',
                beneficiary_document: '🪪',
                body_text: '📝',
                activity_dates: '📅',
                metadata_code: '🔢',
                text: '🔤',
                logo: '🛡️',
                signature: '✍️',
                signature_block: '✍️',
                stamp: '⭕',
                watermark: '💧',
                qr: '🏁',
                qr_code: '🏁',
                divider: '➖',
                decorative_line: '➖'
            };
            return icons[type] || '📦';
        },

        resolveAssetUrl(el) {
            if (el.asset_url) return el.asset_url;
            if (el.asset_path) {
                if (el.asset_path.startsWith('http://') || el.asset_path.startsWith('https://')) return el.asset_path;
                return '/' + el.asset_path.replace(/^\/+/, '');
            }
            if (el.asset_id) {
                const all = this.filteredAssets;
                const found = all.find(a => a.id == el.asset_id);
                if (found) return found.url;
            }
            return '';
        },

        // Text Interpolation for Preview
        interpolateText(text) {
            if (!text) return '';
            return text
                .replace(/@?\{\{beneficiario\.nombre\}\}/g, this.sampleData.beneficiaryName)
                .replace(/@?\{\{beneficiario\.documento\}\}/g, this.sampleData.beneficiaryDoc)
                .replace(/@?\{\{actividad\.nombre\}\}/g, this.sampleData.activityName)
                .replace(/@?\{\{actividad\.horas\}\}/g, this.sampleData.activityHours)
                .replace(/@?\{\{actividad\.fechas\}\}/g, this.sampleData.activityDates)
                .replace(/@?\{\{certificado\.tipo\}\}/g, this.sampleData.certificateType)
                .replace(/@?\{\{certificado\.codigo\}\}/g, this.sampleData.certificateCode)
                .replace(/@?\{\{certificado\.lugar_fecha_emision\}\}/g, this.sampleData.issuePlaceDate)
                .replace(/@?\{\{certificado\.fecha_emision_larga\}\}/g, this.sampleData.issueDateLong)
                .replace(/@?\{\{acreditacion\.resolucion\}\}/g, this.sampleData.resolution)
                .replace(/@?\{\{actividad\.resolucion\}\}/g, this.sampleData.resolution);
        },

        updateSampleProfile() {
            if (this.sampleProfile === 'participant') {
                this.sampleData.beneficiaryName = 'Univ. Rodrigo Quispe Mamani';
                this.sampleData.beneficiaryDoc = '8493021 LP';
                this.sampleData.certificateType = 'ASISTENCIA Y APROBACIÓN';
            } else if (this.sampleProfile === 'speaker') {
                this.sampleData.beneficiaryName = 'Dr. Alejandro Morales Ramos';
                this.sampleData.beneficiaryDoc = '4729103 SC';
                this.sampleData.certificateType = 'EXPOSITOR Y DISERTANTE MAGISTRAL';
            } else if (this.sampleProfile === 'organizer') {
                this.sampleData.beneficiaryName = 'Lic. Carmen Vargas Luna';
                this.sampleData.beneficiaryDoc = '3892019 LP';
                this.sampleData.certificateType = 'COMITÉ ORGANIZADOR';
            } else if (this.sampleProfile === 'honor') {
                this.sampleData.beneficiaryName = 'Univ. Valeria Torrico Siles';
                this.sampleData.beneficiaryDoc = '9201834 CB';
                this.sampleData.certificateType = 'EXCELENCIA ACADÉMICA DE GRADO';
            }
        },

        // Element Selection
        selectElement(id) {
            this.selectedId = id;
            if (id && window.innerWidth < 1024) {
                this.activePanel = 'inspector';
            }
        },

        // Drag and Drop Logic
        startDrag(e, id) {
            const el = this.elements.find(item => item.id === id);
            if (!el || el.is_locked) return;

            this.selectedId = id;
            this.isDragging = true;
            this.activeElementId = id;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.initialElementX = el.x;
            this.initialElementY = el.y;

            e.preventDefault();
        },

        startResize(e, id) {
            const el = this.elements.find(item => item.id === id);
            if (!el || el.is_locked) return;

            this.selectedId = id;
            this.isResizing = true;
            this.activeElementId = id;
            this.dragStartX = e.clientX;
            this.dragStartY = e.clientY;
            this.initialElementW = el.width;
            this.initialElementH = el.height || 10;

            e.stopPropagation();
            e.preventDefault();
        },

        handleMouseMove(e) {
            if (!this.isDragging && !this.isResizing) return;
            const sheet = document.getElementById('certificateCanvasSheet');
            if (!sheet) return;

            const rect = sheet.getBoundingClientRect();
            const el = this.elements.find(item => item.id === this.activeElementId);
            if (!el) return;

            const deltaX = (e.clientX - this.dragStartX) / rect.width * 100;
            const deltaY = (e.clientY - this.dragStartY) / rect.height * 100;

            if (this.isDragging) {
                let newX = Math.round((this.initialElementX + deltaX) * 10) / 10;
                let newY = Math.round((this.initialElementY + deltaY) * 10) / 10;

                // Bounds clamp
                newX = Math.max(0, Math.min(100 - el.width, newX));
                newY = Math.max(0, Math.min(95, newY));

                // Snap to Center X (50%)
                const centerX = newX + (el.width / 2);
                if (Math.abs(centerX - 50) < 1.2) {
                    newX = Math.round((50 - (el.width / 2)) * 10) / 10;
                    this.showSnapX = true;
                } else {
                    this.showSnapX = false;
                }

                el.x = newX;
                el.y = newY;
                this.markDirty();
            } else if (this.isResizing) {
                let newW = Math.round((this.initialElementW + deltaX) * 10) / 10;
                let newH = Math.round((this.initialElementH + deltaY) * 10) / 10;

                newW = Math.max(5, Math.min(100 - el.x, newW));
                newH = Math.max(2, Math.min(100 - el.y, newH));

                el.width = newW;
                el.height = newH;
                this.markDirty();
            }
        },

        handleMouseUp() {
            if (this.isDragging || this.isResizing) {
                this.isDragging = false;
                this.isResizing = false;
                this.activeElementId = null;
                this.showSnapX = false;
                this.showSnapY = false;
                this.pushHistoryState();
            }
        },

        // Keyboard navigation & Shortcuts
        handleKeyDown(e) {
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(e.target.tagName)) return;

            // Undo: Ctrl+Z
            if (e.ctrlKey && e.key.toLowerCase() === 'z') {
                e.preventDefault();
                this.undo();
                return;
            }

            // Redo: Ctrl+Y
            if (e.ctrlKey && e.key.toLowerCase() === 'y') {
                e.preventDefault();
                this.redo();
                return;
            }

            if (!this.selectedElement || this.selectedElement.is_locked) return;

            const step = e.shiftKey ? 2 : 0.5;

            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                this.selectedElement.x = Math.max(0, Math.round((this.selectedElement.x - step) * 10) / 10);
                this.markDirty();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                this.selectedElement.x = Math.min(100 - this.selectedElement.width, Math.round((this.selectedElement.x + step) * 10) / 10);
                this.markDirty();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                this.selectedElement.y = Math.max(0, Math.round((this.selectedElement.y - step) * 10) / 10);
                this.markDirty();
            } else if (e.key === 'ArrowDown') {
                e.preventDefault();
                this.selectedElement.y = Math.min(95, Math.round((this.selectedElement.y + step) * 10) / 10);
                this.markDirty();
            } else if (e.key === 'Delete' || e.key === 'Backspace') {
                if (!this.selectedElement.is_mandatory) {
                    e.preventDefault();
                    this.deleteElement(this.selectedElement.id);
                }
            }
        },

        // Element Insertion Handlers
        addTextElement(type, defaultContent, fontSize = 12, isBold = false, fontStyle = 'normal') {
            const id = 'el_' + Date.now();
            const newEl = {
                id: id,
                type: type,
                name: defaultContent.slice(0, 20).toUpperCase(),
                x: 10,
                y: 40,
                width: 80,
                height: 8,
                z_index: 20,
                opacity: 1,
                rotation: 0,
                is_visible: true,
                is_locked: false,
                is_mandatory: false,
                content: defaultContent,
                styles: {
                    font_size: fontSize,
                    font_weight: isBold ? 'bold' : 'normal',
                    font_style: fontStyle,
                    text_align: 'center',
                    text_color: this.schema.text_color || '#1e293b'
                }
            };

            this.elements.push(newEl);
            this.selectedId = id;
            this.pushHistoryState();
            this.markDirty();
            if (window.innerWidth < 1024) this.activePanel = 'canvas';
        },

        addQrElement() {
            const id = 'el_qr_' + Date.now();
            const newEl = {
                id: id,
                type: 'qr',
                name: 'CÓDIGO QR OFICIAL',
                x: 82,
                y: 72,
                width: 12,
                height: 18,
                z_index: 25,
                opacity: 1,
                rotation: 0,
                is_visible: true,
                is_locked: false,
                is_mandatory: false,
                styles: {}
            };
            this.elements.push(newEl);
            this.selectedId = id;
            this.pushHistoryState();
            this.markDirty();
            if (window.innerWidth < 1024) this.activePanel = 'canvas';
        },

        addSignatureBlock() {
            const id = 'el_sig_' + Date.now();
            const firstSig = (this.assets.signatures && this.assets.signatures[0]) || null;
            const newEl = {
                id: id,
                type: 'signature',
                name: 'BLOQUE DE FIRMA OFICIAL',
                x: 35,
                y: 74,
                width: 30,
                height: 16,
                z_index: 22,
                opacity: 1,
                rotation: 0,
                is_visible: true,
                is_locked: false,
                is_mandatory: false,
                asset_id: firstSig ? firstSig.id : null,
                asset_url: firstSig ? firstSig.url : '',
                content: 'Ing. Aniceto Velarde Forest',
                subtitle: 'Vicedecano de Facultad',
                styles: {}
            };
            this.elements.push(newEl);
            this.selectedId = id;
            this.pushHistoryState();
            this.markDirty();
            if (window.innerWidth < 1024) this.activePanel = 'canvas';
        },

        addDecorativeLine() {
            const id = 'el_line_' + Date.now();
            const newEl = {
                id: id,
                type: 'divider',
                name: 'LÍNEA ORNAMENTAL',
                x: 25,
                y: 35,
                width: 50,
                height: 2,
                z_index: 15,
                opacity: 0.8,
                rotation: 0,
                is_visible: true,
                is_locked: false,
                is_mandatory: false,
                styles: { height: 2 }
            };
            this.elements.push(newEl);
            this.selectedId = id;
            this.pushHistoryState();
            this.markDirty();
            if (window.innerWidth < 1024) this.activePanel = 'canvas';
        },

        insertAssetToCanvas(asset) {
            const id = 'el_asset_' + Date.now();
            const isLogo = asset.category === 'logo';
            const isWatermark = asset.category === 'watermark';
            const isStamp = asset.category === 'stamp';

            const newEl = {
                id: id,
                type: isLogo ? 'logo' : (isWatermark ? 'watermark' : (isStamp ? 'stamp' : 'image')),
                name: asset.name,
                x: isWatermark ? 25 : (isLogo ? 8 : 75),
                y: isWatermark ? 25 : 8,
                width: isWatermark ? 50 : 12,
                height: isWatermark ? 50 : 12,
                z_index: isWatermark ? 2 : 25,
                opacity: isWatermark ? 0.08 : 1,
                rotation: 0,
                is_visible: true,
                is_locked: false,
                is_mandatory: false,
                asset_id: asset.id,
                asset_url: asset.url,
                styles: {
                    object_fit: 'contain'
                }
            };

            this.elements.push(newEl);
            this.selectedId = id;
            this.pushHistoryState();
            this.markDirty();
            if (window.innerWidth < 1024) this.activePanel = 'canvas';
        },

        // Element Actions
        deleteElement(id) {
            const index = this.elements.findIndex(e => e.id === id);
            if (index !== -1 && !this.elements[index].is_mandatory) {
                this.elements.splice(index, 1);
                if (this.selectedId === id) this.selectedId = null;
                this.pushHistoryState();
                this.markDirty();
            }
        },

        duplicateElement(id) {
            const original = this.elements.find(e => e.id === id);
            if (!original) return;

            const copy = JSON.parse(JSON.stringify(original));
            copy.id = 'el_' + Date.now();
            copy.name = (copy.name || copy.type) + ' (Copia)';
            copy.x = Math.min(90, copy.x + 2);
            copy.y = Math.min(90, copy.y + 2);
            copy.is_mandatory = false;

            this.elements.push(copy);
            this.selectedId = copy.id;
            this.pushHistoryState();
            this.markDirty();
        },

        toggleVisibility(id) {
            const el = this.elements.find(e => e.id === id);
            if (el) {
                el.is_visible = el.is_visible === false ? true : false;
                this.markDirty();
            }
        },

        toggleLock(id) {
            const el = this.elements.find(e => e.id === id);
            if (el) {
                el.is_locked = !el.is_locked;
                this.markDirty();
            }
        },

        alignElementCenter(axis) {
            if (!this.selectedElement) return;
            if (axis === 'x') {
                this.selectedElement.x = Math.round(((100 - this.selectedElement.width) / 2) * 10) / 10;
            } else if (axis === 'y') {
                this.selectedElement.y = Math.round(((100 - (this.selectedElement.height || 10)) / 2) * 10) / 10;
            }
            this.pushHistoryState();
            this.markDirty();
        },

        bringToFront(id) {
            const el = this.elements.find(e => e.id === id);
            if (!el) return;
            const maxZ = Math.max(...this.elements.map(e => e.z_index || 10), 10);
            el.z_index = maxZ + 1;
            this.pushHistoryState();
            this.markDirty();
        },

        sendToBack(id) {
            const el = this.elements.find(e => e.id === id);
            if (!el) return;
            const minZ = Math.min(...this.elements.map(e => e.z_index || 10), 10);
            el.z_index = Math.max(1, minZ - 1);
            this.pushHistoryState();
            this.markDirty();
        },

        changeFontSize(delta) {
            if (!this.selectedElement) return;
            if (!this.selectedElement.styles) this.selectedElement.styles = {};
            const cur = Number(this.selectedElement.styles.font_size || this.selectedElement.font_size || 12);
            const updated = Math.max(6, Math.min(72, cur + delta));
            this.selectedElement.styles.font_size = updated;
            this.selectedElement.font_size = updated;
            this.markDirty();
        },

        setTextAlign(align) {
            if (!this.selectedElement) return;
            if (!this.selectedElement.styles) this.selectedElement.styles = {};
            this.selectedElement.styles.text_align = align;
            this.selectedElement.text_align = align;
            this.markDirty();
        },

        insertVariable(variable) {
            if (!this.selectedElement) return;
            const current = (this.selectedElement.content || '').trim();
            this.selectedElement.content = current ? (current + ' ' + variable) : variable;
            this.markDirty();
        },

        toggleOrientation() {
            this.orientation = this.orientation === 'landscape' ? 'portrait' : 'landscape';
            this.markDirty();
        },

        autoAdaptLayout(forceOrientation = null) {
            const targetOrientation = forceOrientation || this.orientation;
            this.orientation = targetOrientation;

            let sigCount = 0;
            let logoCount = 0;

            this.elements.forEach(el => {
                if (this.isSignatureType(el.type)) sigCount++;
                if (el.type === 'logo') logoCount++;
            });

            let currentSigIndex = 0;
            let currentLogoIndex = 0;

            this.elements.forEach(el => {
                const type = el.type;

                if (targetOrientation === 'portrait') {
                    // Portrait layout adaptations
                    if (type === 'logo') {
                        if (currentLogoIndex === 0 && logoCount === 1) {
                            el.x = 42; el.y = 5.5; el.width = 16; el.height = 11;
                        } else if (currentLogoIndex === 0) {
                            el.x = 10; el.y = 6; el.width = 13; el.height = 12;
                        } else {
                            el.x = 77; el.y = 6; el.width = 13; el.height = 12;
                        }
                        currentLogoIndex++;
                    } else if (type === 'header_title') {
                        el.x = 16; el.y = 6.5; el.width = 68; el.height = 4;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 13;
                    } else if (type === 'header_subtitle') {
                        el.x = 16; el.y = 11; el.width = 68; el.height = 3.5;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 11;
                    } else if (type === 'certificate_type') {
                        el.x = 10; el.y = 24.5; el.width = 80; el.height = 6.5;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 23;
                    } else if (type === 'beneficiary_name') {
                        el.x = 10; el.y = 32.5; el.width = 80; el.height = 7;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 22;
                    } else if (type === 'beneficiary_document') {
                        el.x = 25; el.y = 40; el.width = 50; el.height = 3;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 11;
                    } else if (type === 'body_text') {
                        el.x = 12; el.y = 44.5; el.width = 76; el.height = 14;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 11.5;
                    } else if (type === 'activity_dates' || (el.content && el.content.includes('fecha_emision'))) {
                        el.x = 15; el.y = 63.5; el.width = 70; el.height = 4;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 10;
                    } else if (this.isSignatureType(type)) {
                        if (sigCount >= 2) {
                            if (currentSigIndex === 0) {
                                el.x = 14; el.y = 71; el.width = 33; el.height = 13;
                            } else {
                                el.x = 53; el.y = 71; el.width = 33; el.height = 13;
                            }
                        } else {
                            el.x = 34; el.y = 71; el.width = 32; el.height = 13;
                        }
                        currentSigIndex++;
                    } else if (type === 'stamp') {
                        el.x = 14; el.y = 85.5; el.width = 13; el.height = 10.5;
                    } else if (this.isQrType(type)) {
                        el.x = 30; el.y = 85.5; el.width = 56; el.height = 10.5;
                    } else if (type === 'watermark') {
                        el.x = 24; el.y = 27; el.width = 52; el.height = 44;
                        el.opacity = 0.07;
                    } else if (this.isLineType(type)) {
                        el.x = 20; el.width = 60;
                    } else if (el.content && el.content.includes('otorgan el presente')) {
                        el.x = 12; el.y = 18; el.width = 76; el.height = 5;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 10.5;
                    } else if (el.content && el.content.includes('resolucion')) {
                        el.x = 15; el.y = 59.5; el.width = 70; el.height = 3.5;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 9.5;
                    }
                } else {
                    // Landscape layout adaptations
                    if (type === 'logo') {
                        if (currentLogoIndex === 0 && logoCount === 1) {
                            el.x = 8; el.y = 6.5; el.width = 10; el.height = 14;
                        } else if (currentLogoIndex === 0) {
                            el.x = 8; el.y = 6.5; el.width = 10; el.height = 14;
                        } else {
                            el.x = 82; el.y = 6.5; el.width = 10; el.height = 14;
                        }
                        currentLogoIndex++;
                    } else if (type === 'header_title') {
                        el.x = 20; el.y = 6.5; el.width = 60; el.height = 5;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 13;
                    } else if (type === 'header_subtitle') {
                        el.x = 20; el.y = 12; el.width = 60; el.height = 4.5;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 11;
                    } else if (type === 'certificate_type') {
                        el.x = 15; el.y = 19; el.width = 70; el.height = 8.5;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 23;
                    } else if (type === 'beneficiary_name') {
                        el.x = 10; el.y = 29; el.width = 80; el.height = 8.5;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 22;
                    } else if (type === 'beneficiary_document') {
                        el.x = 25; el.y = 38; el.width = 50; el.height = 3.5;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 11;
                    } else if (type === 'body_text') {
                        el.x = 12; el.y = 43; el.width = 76; el.height = 16;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 11.5;
                    } else if (type === 'activity_dates' || (el.content && el.content.includes('fecha_emision'))) {
                        el.x = 15; el.y = 60.5; el.width = 70; el.height = 4;
                        if (!el.styles) el.styles = {};
                        el.styles.font_size = 10;
                    } else if (this.isSignatureType(type)) {
                        if (sigCount >= 2) {
                            if (currentSigIndex === 0) {
                                el.x = 16; el.y = 68; el.width = 28; el.height = 18;
                            } else {
                                el.x = 56; el.y = 68; el.width = 28; el.height = 18;
                            }
                        } else {
                            el.x = 36; el.y = 68; el.width = 28; el.height = 18;
                        }
                        currentSigIndex++;
                    } else if (type === 'stamp') {
                        el.x = 44; el.y = 69; el.width = 12; el.height = 17;
                    } else if (this.isQrType(type)) {
                        el.x = 84; el.y = 69; el.width = 10; el.height = 18;
                    } else if (type === 'watermark') {
                        el.x = 30; el.y = 20; el.width = 40; el.height = 55;
                        el.opacity = 0.07;
                    } else if (this.isLineType(type)) {
                        el.x = 25; el.width = 50;
                    }
                }
            });

            this.pushHistoryState();
            this.markDirty();
        },

        setZoom(val) {
            this.zoom = Math.max(50, Math.min(150, val));
        },

        markDirty() {
            this.isDirty = true;
        },

        // Undo / Redo
        pushHistoryState() {
            const snapshot = JSON.stringify(this.elements);
            this.undoStack.push(snapshot);
            if (this.undoStack.length > 30) this.undoStack.shift();
            this.redoStack = [];
        },

        undo() {
            if (this.undoStack.length <= 1) return;
            const current = this.undoStack.pop();
            this.redoStack.push(current);
            const prev = this.undoStack[this.undoStack.length - 1];
            this.elements = JSON.parse(prev);
            this.markDirty();
        },

        redo() {
            if (this.redoStack.length === 0) return;
            const next = this.redoStack.pop();
            this.undoStack.push(next);
            this.elements = JSON.parse(next);
            this.markDirty();
        },

        // Submission
        saveTemplate(createNewVersion = false) {
            const fullSchema = {
                ...this.schema,
                elements: this.elements
            };

            document.getElementById('createNewVersionInput').value = createNewVersion ? '1' : '0';
            document.getElementById('schemaJsonInput').value = JSON.stringify(fullSchema);
            document.getElementById('studioForm').submit();
        }
    };
}
</script>
@endsection
