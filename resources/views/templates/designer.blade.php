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
    currentVersion: {{ $template->current_version }},
    previewPdfUrl: @json(route('templates.preview', $template)),
    backUrl: @json(route('templates.index')),
    name: @json($template->name),
    category: @json($template->category),
    orientation: @json($template->orientation),
    description: @json($template->description ?? ''),
    initialSchema: @json($normalizedSchema),
    initialElements: @json($normalizedSchema['elements'] ?? []),
    assets: @json($assetsData),
    certificateTypes: @json($certificateTypes),
    sampleIssueDate: @json(now()->toDateString()),
    layoutPresets: @json($layoutPresets)
};
</script>

<div x-data="certificateStudio(window.__CERTIFICATE_STUDIO_CONFIG)"
     @keydown.window="handleKeyDown($event)"
     class="ft-studio">
    <h1 class="sr-only">Diseñador Visual de Certificados</h1>
    <header class="ft-studio-header">
        <div class="ft-studio-heading">
            <a href="{{ route('templates.index') }}" @click="confirmLeave($event)" class="ft-studio-icon-button" aria-label="Volver a Plantillas" title="Volver a Plantillas">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
            </a>
            <div class="min-w-0">
                <p class="ft-studio-eyebrow">FT UMSA <span> / Estudio de certificados</span></p>
                <label class="sr-only" for="studio-template-name">Nombre de la plantilla</label>
                <input id="studio-template-name" type="text" x-model="name" @input="markDirty()" maxlength="150" class="ft-studio-name" :title="name">
            </div>
            <span class="ft-studio-version">v{{ $template->current_version }}.0</span>
        </div>
        <div class="ft-studio-header-actions">
            <span class="ft-studio-save-status" role="status" :class="isDirty ? 'text-amber-700' : 'text-emerald-700'">
                <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                <span x-text="isDirty ? 'Cambios pendientes' : 'Guardado'"></span>
            </span>
            <button type="button" @click="previewPdf()" class="ft-studio-button ft-studio-preview" title="Ver el PDF de la plantilla guardada">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path stroke-linejoin="round" d="M14 3H6v18h12V7l-4-4Z"/><path d="M14 3v5h4M9 13h6m-6 4h4"/></svg><span>Ver PDF</span>
            </button>
            <div class="ft-studio-save-group" x-data="{ saveMenuOpen: false }" @click.outside="saveMenuOpen = false" @keydown.escape.stop="saveMenuOpen = false">
                <button type="button" @click="saveTemplate(false)" :disabled="isSaving || !isDirty" class="ft-studio-save" title="Guardar cambios en la versión actual (Ctrl+S)">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>
                    <span x-text="isSaving ? 'Guardando…' : 'Guardar cambios'"></span>
                </button>
                <button id="studio-version-trigger" type="button" @click="saveMenuOpen = !saveMenuOpen" :disabled="isSaving" :aria-expanded="saveMenuOpen" aria-label="Más opciones de guardado" class="ft-studio-save-more">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m7 10 5 5 5-5"/></svg>
                </button>
                <div x-cloak x-show="saveMenuOpen" class="ft-studio-save-menu">
                    <button type="button" @click="saveMenuOpen = false; openVersionDialog()">
                        <span class="font-semibold text-brand">Crear nueva versión</span>
                        <span class="block text-xs text-slate-500 mt-1">Conservar la revisión actual en el historial.</span>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <div class="ft-studio-toolbar" role="toolbar" aria-label="Herramientas del diseñador">
        <div class="ft-studio-tool-group">
            <button type="button" @click="toggleLeftPanel()" :aria-expanded="isDesktop ? leftPanelOpen : activePanel === 'tools'" aria-controls="studio-tools" class="ft-studio-button" :class="(isDesktop ? leftPanelOpen : activePanel === 'tools') && 'is-active'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M9 4v16m-3-11h0m0 4h0"/></svg><span>Recursos</span>
            </button>
            <span class="ft-studio-divider"></span>
            <button type="button" @click="undo()" :disabled="!canUndo" class="ft-studio-icon-button" aria-label="Deshacer" title="Deshacer (Ctrl+Z)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5-6 6 6 6M3 11h11a6 6 0 0 1 6 6v2"/></svg>
            </button>
            <button type="button" @click="redo()" :disabled="!canRedo" class="ft-studio-icon-button" aria-label="Rehacer" title="Rehacer (Ctrl+Mayús+Z)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="m15 5 6 6-6 6m6-6H10a6 6 0 0 0-6 6v2"/></svg>
            </button>
        </div>
        <div class="ft-studio-tool-group ft-studio-zoom">
            <button type="button" @click="setZoom(zoom - 10)" :disabled="zoom <= 20" class="ft-studio-icon-button" aria-label="Alejar" title="Alejar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14"/></svg></button>
            <button type="button" @click="setZoom(100)" class="ft-studio-zoom-label" title="Tamaño 100%" x-text="zoom + '%'"></button>
            <button type="button" @click="setZoom(zoom + 10)" :disabled="zoom >= 200" class="ft-studio-icon-button" aria-label="Acercar" title="Acercar"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12h14M12 5v14"/></svg></button>
            <button type="button" @click="fitCanvas()" class="ft-studio-button" :class="autoFit && 'is-active'" title="Encajar el certificado completo en el espacio disponible">Encajar</button>
            <span class="ft-studio-divider"></span>
            <button type="button" @click="showGrid = !showGrid" :aria-pressed="showGrid" :class="showGrid && 'is-active'" class="ft-studio-button" title="Mostrar guías de alineación">Guías</button>
        </div>
        <div class="ft-studio-tool-group ft-studio-toolbar-end">
            <button type="button" @click="autoAdaptLayout()" class="ft-studio-button ft-studio-auto-layout" title="Redistribuir los elementos desbloqueados; puedes deshacer este ajuste">Autoajustar</button>
            <button type="button" @click="toggleRightPanel()" :aria-expanded="isDesktop ? rightPanelOpen : activePanel === 'inspector'" aria-controls="studio-inspector" class="ft-studio-button" :class="(isDesktop ? rightPanelOpen : activePanel === 'inspector') && 'is-active'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 7h16M4 17h16"/><circle cx="9" cy="7" r="2" fill="white"/><circle cx="15" cy="17" r="2" fill="white"/></svg><span>Propiedades</span>
            </button>
        </div>
    </div>
    <div x-cloak x-show="previewNotice" class="ft-studio-notice" role="status">
        <p x-text="previewNotice"></p>
        <button type="button" @click="previewNotice = ''" aria-label="Cerrar aviso">✕</button>
    </div>
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
        <input type="hidden" name="change_summary" id="changeSummaryInput" :value="versionChangeSummary">
    </form>

    <div class="ft-studio-body">
        <button x-cloak x-show="!isDesktop && activePanel !== 'canvas'" @click="activePanel = 'canvas'" type="button" class="ft-studio-panel-overlay" aria-label="Cerrar panel y volver al lienzo"></button>
        <aside id="studio-tools" aria-label="Recursos de diseño" class="ft-studio-panel ft-studio-tools"
               :class="{ 'is-open': leftPanelOpen, 'is-mobile-open': activePanel === 'tools' }">
            <div class="ft-studio-panel-heading"><span>Recursos de diseño</span><button type="button" @click="toggleLeftPanel()" aria-label="Cerrar recursos">✕</button></div>

        <!-- Left Tabs Bar -->
            <div class="flex items-center border-b border-slate-200 bg-slate-50/80 p-1.5 gap-1">
                <button type="button" @click="leftTab = 'tools'"
                        :class="leftTab === 'tools' ? 'bg-white text-brand font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="min-w-0 flex-1 py-2 text-[10px] whitespace-nowrap rounded-lg transition-all text-center cursor-pointer">
                    Elementos
                </button>
                <button type="button" @click="leftTab = 'library'"
                        :class="leftTab === 'library' ? 'bg-white text-brand font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="min-w-0 flex-1 py-2 text-[10px] whitespace-nowrap rounded-lg transition-all text-center cursor-pointer">
                    Biblioteca
                </button>
                <button type="button" @click="leftTab = 'layers'"
                        :class="leftTab === 'layers' ? 'bg-white text-brand font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="min-w-0 flex-1 py-2 text-[10px] whitespace-nowrap rounded-lg transition-all text-center cursor-pointer">
                    Capas (<span x-text="elements.length"></span>)
                </button>
                <button type="button" @click="leftTab = 'data'" :class="leftTab === 'data' ? 'bg-white text-brand font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="min-w-0 flex-1 py-2 text-[10px] whitespace-nowrap rounded-lg transition-all text-center">Datos</button>
            </div>

            <!-- Tab Content (Scrollable) -->
            <div class="p-3.5 space-y-4 overflow-y-auto flex-1 text-xs">
                @include('templates.sample-data')

                <!-- -------------------------------------------------- -->
                <!-- SUB-TAB 1: HERRAMIENTAS DE INSERCIÓN              -->
                <!-- -------------------------------------------------- -->
                <div x-show="leftTab === 'tools'" class="space-y-4">
                    <section class="space-y-2" aria-label="Franjas y formas">
                        <h3 class="text-[11px] font-bold text-brand">Franjas y formas</h3>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="addShape('top')" class="rounded-xl border border-slate-200 p-2 text-left text-[11px] font-semibold text-brand hover:bg-brand-soft">Franja superior</button>
                            <button type="button" @click="addShape('bottom')" class="rounded-xl border border-slate-200 p-2 text-left text-[11px] font-semibold text-brand hover:bg-brand-soft">Franja inferior</button>
                            <button type="button" @click="addShape('side')" class="rounded-xl border border-slate-200 p-2 text-left text-[11px] font-semibold text-brand hover:bg-brand-soft">Franja lateral</button>
                            <button type="button" @click="addShape('rectangle')" class="rounded-xl border border-slate-200 p-2 text-left text-[11px] font-semibold text-brand hover:bg-brand-soft">Rectángulo</button>
                            <button type="button" @click="addShape('ellipse')" class="rounded-xl border border-slate-200 p-2 text-left text-[11px] font-semibold text-brand hover:bg-brand-soft">Elipse</button>
                            <button type="button" @click="addDecorativeLine()" class="rounded-xl border border-slate-200 p-2 text-left text-[11px] font-semibold text-brand hover:bg-brand-soft">Línea</button>
                        </div>
                    </section>

                    <section class="rounded-xl border border-brand/15 bg-brand-soft p-3 space-y-2" aria-label="Bloques académicos">
                        <h3 class="text-[11px] font-bold text-brand">Calificación y vigencia</h3>
                        <p class="text-[10px] text-slate-600">Añade estos campos a cualquier plantilla. Los valores se completan al emitir.</p>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="addCertificateField('qualification')" class="rounded-lg border border-brand/15 bg-white p-2 text-left text-[11px] font-semibold text-brand"><span x-text="hasAcademicField('qualification') ? 'Editar calificación' : '+ Calificación'"></span></button>
                            <button type="button" @click="addCertificateField('mention')" class="rounded-lg border border-brand/15 bg-white p-2 text-left text-[11px] font-semibold text-brand"><span x-text="hasAcademicField('mention') ? 'Editar mención' : '+ Mención'"></span></button>
                            <button type="button" @click="addCertificateField('validity')" class="rounded-lg border border-brand/15 bg-white p-2 text-left text-[11px] font-semibold text-brand"><span x-text="hasAcademicField('validity') ? 'Editar vigencia' : '+ Vigencia'"></span></button>
                            <button type="button" @click="addCertificateField('expiration')" class="rounded-lg border border-brand/15 bg-white p-2 text-left text-[11px] font-semibold text-brand"><span x-text="hasAcademicField('expiration') ? 'Editar vencimiento' : '+ Vencimiento'"></span></button>
                            <button type="button" @click="addCertificateField('modality')" class="rounded-lg border border-brand/15 bg-white p-2 text-left text-[11px] font-semibold text-brand"><span x-text="hasAcademicField('modality') ? 'Editar modalidad' : '+ Modalidad'"></span></button>
                            <button type="button" @click="addCertificateField('role')" class="rounded-lg border border-brand/15 bg-white p-2 text-left text-[11px] font-semibold text-brand"><span x-text="hasAcademicField('role') ? 'Editar rol' : '+ Rol'"></span></button>
                        </div>
                        <button type="button" @click="leftTab = 'data'" class="text-[10px] font-semibold text-brand underline underline-offset-2">Probar tipos, notas y vigencias →</button>
                    </section>

                    <!-- Text Blocks Category -->
                    <div>
                        <h3 class="text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-2 flex items-center gap-1.5">
                            <span>📝</span> Textos Dinámicos
                        </h3>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="addTextElement('text', 'UNIVERSIDAD MAYOR DE SAN ANDRÉS', 14, true)"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Título Superior</p>
                                <p class="text-[9px] text-slate-400">Universidad / Entidad</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'FACULTAD DE TECNOLOGÍA', 11, true)"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Subtítulo</p>
                                <p class="text-[9px] text-slate-400">Facultad / Carrera</p>
                            </button>

                            <button type="button" @click="addTextElement('text', '@{{certificado.tipo}}', 22, true)"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Tipo Certificado</p>
                                <p class="text-[9px] text-slate-400">Aprobación, Asistencia</p>
                            </button>

                            <button type="button" @click="addTextElement('text', '@{{beneficiario.nombre}}', 18, true)"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Beneficiario</p>
                                <p class="text-[9px] text-slate-400">Nombre completo</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'C.I. @{{beneficiario.documento}}', 11, false)"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Documento C.I.</p>
                                <p class="text-[9px] text-slate-400">Identificación</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'La Paz, @{{actividad.fechas}}', 10, false)"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Fechas / Lugar</p>
                                <p class="text-[9px] text-slate-400">Periodo académico</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'Por su destacada participación en la actividad académica \'@{{actividad.nombre}}\', con una carga horaria de @{{actividad.horas}} horas.', 11, false)"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group col-span-2">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Párrafo de Certificación</p>
                                <p class="text-[9px] text-slate-400">Texto libre con variables interpolables</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'El Vicedecano y las Autoridades de la Facultad de Tecnología de la Universidad Mayor de San Andrés, en uso de sus atribuciones confieren el presente:', 10, false, 'italic')"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group col-span-2">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Preámbulo Institucional</p>
                                <p class="text-[9px] text-slate-400">Fórmula solemne de otorgamiento en cursiva</p>
                            </button>

                            <button type="button" @click="addTextElement('text', 'Resolución H.C.F. Nº @{{acreditacion.resolucion}}', 9, false)"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Resolución / Aval</p>
                                <p class="text-[9px] text-slate-400">Res. HCF de acreditación</p>
                            </button>

                            <button type="button" @click="addTextElement('text', '@{{certificado.lugar_fecha_emision}}', 10, false)"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Lugar y Fecha Solemne</p>
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
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Código QR</p>
                                <p class="text-[9px] text-slate-400">Verificación pública</p>
                            </button>

                            <button type="button" @click="addSignatureBlock()"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Bloque de Firma</p>
                                <p class="text-[9px] text-slate-400">Línea + Nombre + Cargo</p>
                            </button>

                            <button type="button" @click="addDecorativeLine()"
                                    class="p-2 border border-slate-200 hover:border-brand hover:bg-slate-50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-slate-800 group-hover:text-brand text-[11px]">Línea Ornamental</p>
                                <p class="text-[9px] text-slate-400">Separador visual</p>
                            </button>

                            <button type="button" @click="leftTab = 'library'"
                                    class="p-2 border border-dashed border-blue-300 bg-blue-50/50 hover:bg-blue-100/50 rounded-xl text-left transition-all cursor-pointer group">
                                <p class="font-bold text-brand text-[11px]">+ Insertar Imagen</p>
                                <p class="text-[9px] text-slate-500">De la biblioteca</p>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Sample Toggle -->
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                        <p class="text-[10px] text-slate-500">Los datos del participante y las políticas de cada tipo se prueban desde la pestaña Datos.</p>
                        <button type="button" @click="leftTab = 'data'" class="text-xs font-semibold text-brand">Configurar muestra →</button>
                    </div>

                </div>

                <!-- -------------------------------------------------- -->
                <!-- SUB-TAB 2: BIBLIOTECA INSTITUCIONAL               -->
                <!-- -------------------------------------------------- -->
                <div x-show="leftTab === 'library'" class="space-y-3">

                    <!-- Category Filter -->
                    <div class="flex items-center gap-1 overflow-x-auto pb-1 text-[11px]">
                        <button type="button" @click="libFilter = 'all'"
                                :class="libFilter === 'all' ? 'bg-brand text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Todos
                        </button>
                        <button type="button" @click="libFilter = 'logo'"
                                :class="libFilter === 'logo' ? 'bg-brand text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Logos
                        </button>
                        <button type="button" @click="libFilter = 'signature'"
                                :class="libFilter === 'signature' ? 'bg-brand text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Firmas
                        </button>
                        <button type="button" @click="libFilter = 'stamp'"
                                :class="libFilter === 'stamp' ? 'bg-brand text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Sellos
                        </button>
                        <button type="button" @click="libFilter = 'watermark'"
                                :class="libFilter === 'watermark' ? 'bg-brand text-white font-bold' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'"
                                class="px-2 py-1 rounded-lg transition-all cursor-pointer whitespace-nowrap">
                            Marcas
                        </button>
                    </div>

                    <!-- Direct Upload Link -->
                    <a href="{{ route('assets.index') }}" target="_blank"
                       class="w-full py-2 px-3 border border-dashed border-slate-300 hover:border-brand text-slate-600 hover:text-brand rounded-xl text-center flex items-center justify-center gap-1.5 transition-all block">
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
                                        class="px-2.5 py-1.5 bg-blue-50 group-hover:bg-brand text-brand group-hover:text-white rounded-lg font-bold text-[10px] transition-all cursor-pointer shrink-0"
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
                                 :class="selectedId === el.id ? 'border-brand bg-blue-50/70 shadow-xs ring-1 ring-brand' : 'border-slate-200 bg-white hover:bg-slate-50'"
                                 >

                                <button type="button" @click="selectElement(el.id)" class="flex flex-1 min-w-0 items-center gap-2 overflow-hidden text-left" :aria-label="'Seleccionar ' + (el.name || el.type)" :aria-pressed="selectedId === el.id">
                                    <span class="text-xs" x-text="getElementIcon(el.type)"></span>
                                    <span class="font-medium text-[11px] text-slate-700 truncate" x-text="el.name || el.type"></span>
                                </button>

                                <div class="flex items-center gap-1 shrink-0">
                                    <!-- Visibility Toggle -->
                                    <button type="button" @click.stop="toggleVisibility(el.id)" :aria-label="(el.is_visible !== false ? 'Ocultar ' : 'Mostrar ') + (el.name || el.type)"
                                            class="p-1 text-slate-400 hover:text-slate-700 rounded transition-colors"
                                            :title="el.is_visible !== false ? 'Ocultar elemento' : 'Mostrar elemento'">
                                        <span x-text="el.is_visible !== false ? '👁️' : '🕶️'"></span>
                                    </button>

                                    <!-- Lock Toggle -->
                                    <button type="button" @click.stop="toggleLock(el.id)" :aria-label="(el.is_locked ? 'Desbloquear ' : 'Bloquear ') + (el.name || el.type)"
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
        <section class="ft-studio-canvas" aria-label="Lienzo del certificado">

            <div class="ft-studio-canvas-heading">
                <span class="font-semibold text-brand">Lienzo A4 <span class="ml-1 font-normal text-slate-500" x-text="orientation === 'landscape' ? '297 × 210 mm · Horizontal' : '210 × 297 mm · Vertical'"></span></span>
                <span class="ft-studio-canvas-hint">Arrastra para mover · Doble clic en el texto para editar</span>
            </div>
            <div x-ref="canvasViewport" class="ft-studio-viewport" @click.self="selectElement(null)">
                <div class="ft-studio-canvas-center" @click.self="selectElement(null)">
                    <div x-ref="canvasStage" class="ft-studio-stage" :style="{ width: stageWidthStyle, height: stageHeightStyle }">
                        <div id="certificateCanvasSheet" @click.self="selectElement(null)" class="ft-studio-sheet select-none"
                             :style="{ width: canvasWidthStyle, height: canvasHeightStyle, transform: 'scale(' + (zoom / 100) + ')', fontFamily: schema.font_family || 'Helvetica', color: schema.text_color || '#1e293b' }">

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
                        <div x-show="isElementVisible(el)"
                             :id="'canvas-element-' + el.id"
                             @pointerdown="startDrag($event, el.id)"
                             tabindex="0" role="button" :aria-label="el.name || el.type"
                             @keydown.enter.stop="selectElement(el.id)"
                             @dblclick.stop="selectElement(el.id); openInspector()"
                             @click.stop="selectElement(el.id)"
                             class="absolute transition-shadow group select-none cursor-move touch-none"
                             :class="{
                                 'ring-2 ring-brand ring-offset-1 shadow-lg bg-blue-50/25': selectedId === el.id,
                                 'hover:outline hover:outline-1 hover:outline-blue-400': selectedId !== el.id,
                                 'pointer-events-none': el.is_locked
                             }"
                             :style="{
                                 left: el.x + '%',
                                 top: el.y + '%',
                                 width: el.width + '%',
                                 height: el.height ? (el.height + '%') : 'auto',
                                 zIndex: el.z_index || 10,
                                 opacity: (el.opacity ?? 1) * (matchesVisibilityRule(el) ? 1 : 0.35),
                                 transform: 'rotate(' + (el.rotation || 0) + 'deg)'
                             }">

                            <!-- Selection Outline, Resize Handle & Element Type Badge -->
                            <template x-if="selectedId === el.id">
                                <div>
                                    <!-- Floating Element Badge -->
                                    <div class="absolute -top-6 left-0 px-2 py-0.5 bg-brand text-white text-[9px] font-bold rounded shadow pointer-events-none whitespace-nowrap z-50 flex items-center gap-1 border border-blue-400">
                                        <span x-text="getElementIcon(el.type)"></span>
                                        <span x-text="el.name || el.type"></span>
                                    </div>
                                    <!-- Resize Handle (SE Corner) -->
                                    <div @pointerdown.stop="startResize($event, el.id)"
                                         class="absolute -bottom-2 -right-2 w-5 h-5 touch-none bg-brand border-2 border-white rounded-full shadow-md cursor-se-resize z-50 hover:scale-125 transition-transform"
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
                                <div class="w-full h-full overflow-hidden whitespace-pre-line"
                                     :style="{
                                         fontFamily: el.styles?.font_family || el.font_family || schema.font_family || 'Helvetica',
                                         fontSize: fittedFontSize(el) + 'px',
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
                            <template x-if="isShapeType(el.type)">
                                <div class="w-full h-full pointer-events-none" :style="{ backgroundColor: el.styles?.fill || schema.primary_color || '#110b51', border: (el.styles?.border_width || 0) + 'px solid ' + (el.styles?.border_color || schema.secondary_color || '#efb43c'), borderRadius: el.shape === 'ellipse' ? '50%' : (el.styles?.border_radius || 0) + 'px' }"></div>
                            </template>
                            <template x-if="isQrType(el.type)">
                                <div class="w-full h-full overflow-hidden text-center pointer-events-none">
                                    <div class="h-[70%] flex justify-center">
                                        <svg class="h-full max-w-full aspect-square text-slate-900" viewBox="0 0 24 24" fill="currentColor" aria-label="QR de ejemplo">
                                            <path d="M3 3h8v8H3V3zm2 2v4h4V5H5zm8-2h8v8h-8V3zm2 2v4h4V5h-4zM3 13h8v8H3v-8zm2 2v4h4v-4H5zm13-2h3v2h-3v-2zm-3 2h2v2h-2v-2zm3 2h3v4h-3v-4zm-5 2h2v2h-2v-2zm2 2h3v-2h-3v2z"/>
                                        </svg>
                                    </div>
                                    <p class="text-[7px] leading-tight mt-0.5 text-brand">CERT-2026-000042</p>
                                    <p class="text-[6.5px] leading-tight mt-0.5 text-emerald-700" x-text="sampleData.validityType === 'permanent' ? 'VIGENCIA PERMANENTE' : 'VERIFICAR EN LÍNEA'"></p>
                                </div>
                            </template>

                            <!-- 4. SIGNATURE ELEMENT -->
                            <template x-if="isSignatureType(el.type)">
                                <div class="w-full h-full overflow-hidden text-center pointer-events-none">
                                    <div class="h-[36%] overflow-hidden flex justify-center">
                                        <template x-if="resolveAssetUrl(el)">
                                            <img :src="resolveAssetUrl(el)" class="max-w-[80%] object-contain" :style="{height: resolveAssetUrl(el).includes('_ft.svg') ? '140%' : '100%'}" alt="Firma">
                                        </template>
                                    </div>
                                    <div class="w-[88%] border-t border-slate-700 mx-auto mt-0.5 mb-0.75"></div>
                                    <p class="font-bold text-slate-900 leading-tight w-full" :style="{fontSize: (el.styles?.font_size || 11.5) + 'px'}" x-text="el.content || el.authority_name || el.name || 'Autoridad firmante'"></p>
                                    <p class="text-[9.5px] text-slate-600 leading-tight w-full" x-text="el.subtitle || el.authority_title || el.position || ''"></p>
                                    <p class="text-[8px] text-slate-500 leading-tight w-full" x-text="el.institution || 'Facultad de Tecnología · UMSA'"></p>
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

                </div>
            </div>
        </section>

        <aside id="studio-inspector" aria-label="Propiedades del diseño" class="ft-studio-panel ft-studio-inspector"
               :class="{ 'is-open': rightPanelOpen, 'is-mobile-open': activePanel === 'inspector' }">

            <!-- Right Inspector Header -->
            <div class="border-b border-slate-200 bg-slate-50/80 px-4 py-3 flex items-center justify-between">
                <div>
                    <h2 class="text-xs font-bold text-brand uppercase tracking-wider flex items-center gap-1.5">

                        <span x-text="selectedElement ? 'Propiedades' : 'Ajustes del documento'"></span>
                    </h2>
                    <p class="text-[10px] text-slate-400 mt-0.5" x-text="selectedElement ? (selectedElement.name || selectedElement.type) : 'Formato, tipografía y colores'"></p>
                </div>
                <button type="button" @click="toggleRightPanel()" class="ft-studio-icon-button" aria-label="Cerrar propiedades">✕</button>
            </div>

            <!-- Inspector Scrollable Body -->
            <div class="p-3.5 space-y-4 overflow-y-auto flex-1 text-xs">

                <!-- ================================================== -->
                <!-- SCENARIO A: AN ELEMENT IS SELECTED                 -->
                <!-- ================================================== -->
                <template x-if="selectedElement">
                    <fieldset :disabled="selectedElement.is_locked" class="space-y-4 min-w-0">
                        <p x-show="selectedElement.is_locked" class="rounded-lg bg-accent-soft px-3 py-2 text-amber-800">Elemento bloqueado. Desbloquéalo desde Capas para editarlo.</p>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-2">
                            <label for="element-visibility-rule" class="block text-[10px] font-semibold text-brand">Mostrar este bloque</label>
                            <select id="element-visibility-rule" :value="selectedElement.visibility_rule || 'always'" @change="selectedElement.visibility_rule = $event.target.value; markDirty()" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-1.5 text-xs">
                                <option value="always">En todos los certificados</option><option value="with_score">Cuando tenga calificación</option><option value="permanent">Solo con vigencia permanente</option><option value="temporary">Solo con vencimiento</option>
                            </select>
                            <p x-show="!matchesVisibilityRule(selectedElement)" class="text-[10px] leading-relaxed text-amber-800">Este bloque no corresponde a la muestra actual. Puedes cambiar el tipo desde Datos o activar «Ver todos los bloques» para editarlo.</p>
                        </div>

                        <!-- Element Quick Actions: Align, Layer, Clone -->
                        <template x-if="isQrType(selectedElement.type)">
                            <section class="rounded-xl border border-brand/15 bg-brand-soft p-3 space-y-3" aria-label="Tamaño del código QR">
                                <div class="flex items-center justify-between gap-2">
                                    <h3 class="text-xs font-bold text-brand">Tamaño del QR</h3>
                                    <output for="qr-size-range" class="text-xs font-semibold text-brand" x-text="qrSizeMm().toFixed(1) + ' mm'"></output>
                                </div>
                                <label for="qr-size-range" class="block text-[11px] text-slate-600">Lado del código al imprimir</label>
                                <input id="qr-size-range" type="range" min="20" max="60" step="0.5" :value="qrSizeMm()" @input="resizeQr($event.target.value)" @change="flushHistory()" class="w-full accent-brand">
                                <div class="grid grid-cols-3 gap-1.5">
                                    <template x-for="option in [{size:25, label:'Estándar'}, {size:32, label:'Grande'}, {size:40, label:'Extra grande'}]" :key="option.size">
                                        <button type="button" @click="resizeQr(option.size); flushHistory()" :aria-pressed="Math.abs(qrSizeMm() - option.size) < .2" :class="Math.abs(qrSizeMm() - option.size) < .2 ? 'border-brand bg-brand text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-brand'" class="rounded-lg border px-1.5 py-2 text-center text-[10px] leading-tight">
                                            <span class="block font-semibold" x-text="option.label"></span><span x-text="option.size + ' mm'"></span>
                                        </button>
                                    </template>
                                </div>
                                <p class="text-[10px] leading-relaxed text-slate-600">Conserva la proporción del QR y el espacio para su código. Puedes mover el bloque para que no cubra firmas o texto.</p>
                                <p class="text-[10px] leading-relaxed font-medium text-brand">Al guardar, el tamaño se aplica a los próximos certificados que usen esta plantilla.</p>
                            </section>
                        </template>
                        <template x-if="isShapeType(selectedElement.type)">
                            <section class="rounded-xl border border-slate-200 bg-slate-50 p-3 space-y-3" aria-label="Estilo de la forma">
                                <h3 class="text-[11px] font-bold text-brand">Franja o forma</h3>
                                <div class="flex items-center justify-between gap-3"><label for="shape-fill">Color de relleno</label><input id="shape-fill" type="color" x-model="selectedElement.styles.fill" @input="markDirty()" class="h-8 w-10 rounded border border-slate-300"></div>
                                <div class="flex items-center justify-between gap-3"><label for="shape-border-color">Color del borde</label><input id="shape-border-color" type="color" x-model="selectedElement.styles.border_color" @input="markDirty()" class="h-8 w-10 rounded border border-slate-300"></div>
                                <div><label for="shape-border-width" class="block mb-1">Grosor del borde</label><input id="shape-border-width" type="number" min="0" max="12" step="0.5" x-model.number="selectedElement.styles.border_width" @input="markDirty()" class="w-full rounded-lg border border-slate-300 px-2 py-1.5"></div>
                                <div x-show="selectedElement.shape !== 'ellipse'"><label for="shape-radius" class="block mb-1">Redondeado de esquinas</label><input id="shape-radius" type="number" min="0" max="200" x-model.number="selectedElement.styles.border_radius" @input="markDirty()" class="w-full rounded-lg border border-slate-300 px-2 py-1.5"></div>
                                <p class="text-[10px] text-slate-500">Ajusta posición, tamaño y opacidad abajo. Usa Fondo o Frente para cambiar su orden.</p>
                            </section>
                        </template>
                        <div class="grid grid-cols-5 gap-1 p-1 bg-slate-100 rounded-xl text-[10px] font-bold">
                            <button type="button" @click="alignElementCenter('x')"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-brand hover:bg-white transition-all text-center cursor-pointer"
                                    title="Centrar horizontalmente en el lienzo">
                                ↔ Centro X
                            </button>
                            <button type="button" @click="alignElementCenter('y')"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-brand hover:bg-white transition-all text-center cursor-pointer"
                                    title="Centrar verticalmente en el lienzo">
                                ↕ Centro Y
                            </button>
                            <button type="button" @click="duplicateElement(selectedElement.id)"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-brand hover:bg-white transition-all text-center cursor-pointer"
                                    title="Duplicar este elemento">
                                📋 Clonar
                            </button>
                            <button type="button" @click="bringToFront(selectedElement.id)"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-brand hover:bg-white transition-all text-center cursor-pointer"
                                    title="Traer al frente (capa superior)">
                                ⬆ Frente
                            </button>
                            <button type="button" @click="sendToBack(selectedElement.id)"
                                    class="p-1.5 rounded-lg text-slate-700 hover:text-brand hover:bg-white transition-all text-center cursor-pointer"
                                    title="Enviar al fondo (detrás de otros)">
                                ⬇ Fondo
                            </button>
                        </div>

                        <!-- 1. Geometry Coordinates (X, Y, W, H) -->
                        <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Geometría y Posición (%)</span>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label for="studio-control-2" class="block text-[10px] text-slate-500 mb-0.5">Posición X</label>
                                    <input id="studio-control-2" type="number" step="0.5" x-model.number="selectedElement.x" @input="markDirty()"
                                           class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white font-mono">
                                </div>
                                <div>
                                    <label for="studio-control-3" class="block text-[10px] text-slate-500 mb-0.5">Posición Y</label>
                                    <input id="studio-control-3" type="number" step="0.5" x-model.number="selectedElement.y" @input="markDirty()"
                                           class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white font-mono">
                                </div>
                                <div>
                                    <label for="studio-control-4" class="block text-[10px] text-slate-500 mb-0.5">Ancho (W)</label>
                                    <input id="studio-control-4" type="number" step="0.5" x-model.number="selectedElement.width" @input="markDirty()"
                                           class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white font-mono">
                                </div>
                                <div>
                                    <label for="studio-control-5" class="block text-[10px] text-slate-500 mb-0.5">Alto (H)</label>
                                    <input id="studio-control-5" type="number" step="0.5" x-model.number="selectedElement.height" @input="markDirty()"
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
                                       class="w-full accent-brand cursor-pointer">
                            </div>
                        </div>

                        <div class="rounded-xl border border-slate-200 p-3"><label for="element-rotation" class="block text-[10px] text-slate-500 mb-1">Rotación</label><input id="element-rotation" type="number" min="-180" max="180" step="1" :value="selectedElement.rotation || 0" @input="selectedElement.rotation = Number($event.target.value); markDirty()" class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-xs"></div>
                        <!-- 2. Text Content & Variables (For Text Elements) -->
                        <template x-if="isTextType(selectedElement.type)">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                                <label for="studio-control-6" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Contenido de Texto</label>
                                <textarea id="studio-control-6" rows="3" x-model="selectedElement.content" @input="markDirty()"
                                          class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded-lg bg-white"></textarea>

                                <!-- Categorized Dynamic Variable Chips -->
                                <div class="space-y-1.5 pt-1">
                                    <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-wider">Variables Dinámicas:</span>

                                    <div>
                                        <span class="text-[9px] text-slate-500 font-semibold block mb-0.5">👤 Beneficiario:</span>
                                        <div class="flex flex-wrap gap-1">
                                            <button type="button" @click="insertVariable('@{{beneficiario.nombre}}')"
                                                    class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-brand rounded-md text-[10px] font-mono cursor-pointer transition-all"
                                                    title="Nombre completo del participante">
                                                + @{{beneficiario.nombre}}
                                            </button>
                                            <button type="button" @click="insertVariable('@{{beneficiario.documento}}')"
                                                    class="px-2 py-0.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 text-brand rounded-md text-[10px] font-mono cursor-pointer transition-all"
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
                                            <button type="button" @click="insertVariable('@{{certificado.puntaje}}')" class="px-2 py-1 bg-brand-soft border border-brand/10 text-brand rounded-md text-[10px] cursor-pointer" title="Puntaje numérico de ejemplo en el lienzo">+ Puntaje numérico</button>
                                            <button type="button" @click="insertVariable('@{{certificado.calificacion}}')" class="px-2 py-1 bg-brand-soft border border-brand/10 text-brand rounded-md text-[10px] cursor-pointer" title="Calificación de ejemplo en el lienzo">+ Calificación</button>
                                            <button type="button" @click="insertVariable('@{{certificado.vigencia}}')" class="px-2 py-1 bg-brand-soft border border-brand/10 text-brand rounded-md text-[10px] cursor-pointer" title="Vigencia de ejemplo en el lienzo">+ Vigencia</button>
                                            <button type="button" @click="insertVariable('@{{certificado.fecha_vencimiento}}')" class="px-2 py-1 bg-brand-soft border border-brand/10 text-brand rounded-md text-[10px] cursor-pointer" title="Fecha de vencimiento de ejemplo en el lienzo">+ Fecha de vencimiento</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- 3. Typography Styles (For Text Elements) -->
                        <template x-if="isTextType(selectedElement.type)">
                            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2.5">
                                <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Tipografía & Estilos</span>
                                <label class="flex items-center gap-2 text-xs text-slate-700">
                                    <input type="checkbox" x-model="selectedElement.styles.fit_text" @change="markDirty()" class="rounded border-slate-300 text-brand">
                                    Ajustar texto al espacio disponible
                                </label>
                                <p class="text-[10px] leading-relaxed text-slate-500">Reduce la letra cuando un nombre o párrafo es largo. Amplía el bloque para conservar un tamaño mayor.</p>
                                <div>
                                    <label for="element-font-family" class="block text-[10px] text-slate-500 mb-1">Fuente del elemento</label>
                                    <select id="element-font-family" x-model="selectedElement.styles.font_family" @change="markDirty()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded bg-white">
                                        <option value="">Heredar del documento</option>
                                        <option value="Helvetica">Helvetica</option>
                                        <option value="DejaVu Serif">DejaVu Serif</option>
                                        <option value="DejaVu Sans">DejaVu Sans</option>
                                        <option value="Times-Roman">Times</option>
                                        <option value="Courier">Courier</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[10px] text-slate-500 mb-0.5">Tamaño de letra</label>
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
                                            <button type="button" @click="setTextAlign('left')" :class="(selectedElement.styles?.text_align || selectedElement.text_align) === 'left' ? 'bg-white shadow-xs text-brand font-bold' : 'text-slate-500 hover:text-slate-800'" class="py-0.5 text-xs rounded text-center cursor-pointer" title="Izquierda">⫷</button>
                                            <button type="button" @click="setTextAlign('center')" :class="(selectedElement.styles?.text_align || selectedElement.text_align || 'center') === 'center' ? 'bg-white shadow-xs text-brand font-bold' : 'text-slate-500 hover:text-slate-800'" class="py-0.5 text-xs rounded text-center cursor-pointer" title="Centro">≡</button>
                                            <button type="button" @click="setTextAlign('right')" :class="(selectedElement.styles?.text_align || selectedElement.text_align) === 'right' ? 'bg-white shadow-xs text-brand font-bold' : 'text-slate-500 hover:text-slate-800'" class="py-0.5 text-xs rounded text-center cursor-pointer" title="Derecha">⫸</button>
                                            <button type="button" @click="setTextAlign('justify')" :class="(selectedElement.styles?.text_align || selectedElement.text_align) === 'justify' ? 'bg-white shadow-xs text-brand font-bold' : 'text-slate-500 hover:text-slate-800'" class="py-0.5 text-xs rounded text-center cursor-pointer" title="Justificado">☵</button>
                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-2">
                                    <div><label for="element-line-height" class="block text-[10px] text-slate-500 mb-1">Interlineado</label><input id="element-line-height" type="number" min="0.5" max="3" step="0.1" placeholder="1.3" x-model.number="selectedElement.styles.line_height" @input="markDirty()" class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white"></div>
                                    <div><label for="element-tracking" class="block text-[10px] text-slate-500 mb-1">Espaciado (px)</label><input id="element-tracking" type="number" min="-2" max="20" step="0.5" placeholder="0" x-model.number="selectedElement.styles.letter_spacing" @input="markDirty()" class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white"></div>
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label for="studio-control-7" class="block text-[10px] text-slate-500 mb-0.5">Peso</label>
                                        <select id="studio-control-7" x-model="selectedElement.styles.font_weight"
                                                @change="selectedElement.font_weight = selectedElement.styles.font_weight; markDirty();"
                                                class="w-full py-1 px-2 text-xs border border-slate-300 rounded bg-white">
                                            <option value="normal">Normal</option>
                                            <option value="bold">Negrita (Bold)</option>
                                            <option value="800">Extra Bold</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label for="studio-control-8" class="block text-[10px] text-slate-500 mb-0.5">Estilo</label>
                                        <select id="studio-control-8" x-model="selectedElement.styles.font_style"
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
                                <label for="studio-control-9" class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Recurso Institucional</label>
                                <select id="studio-control-9" x-model="selectedElement.asset_id"
                                        @change="
                                            const found = allAssets.find(a => a.id == selectedElement.asset_id);
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
                                    <label for="studio-control-10" class="block text-[10px] text-slate-500 mb-0.5">Ajuste de imagen</label>
                                    <select id="studio-control-10" x-model="selectedElement.styles.object_fit" @change="markDirty()"
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

                    </fieldset>
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
                                <label for="studio-control-11" class="block text-[10px] text-slate-500 mb-0.5">Categoría</label>
                                <select id="studio-control-11" x-model="category" @change="markDirty()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded bg-white">
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
                                <label for="studio-control-12" class="block text-[10px] text-slate-500 mb-0.5">Orientación A4</label>
                                <select id="studio-control-12" x-model="orientation" @change="markDirty()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded bg-white">
                                    <option value="landscape">Horizontal (Landscape - 297×210 mm)</option>
                                    <option value="portrait">Vertical (Portrait - 210×297 mm)</option>
                                </select>
                            </div>

                            <div>
                                <label for="studio-control-13" class="block text-[10px] text-slate-500 mb-0.5">Marco Ornamental</label>
                                <select id="studio-control-13" x-model="schema.border_style" @change="markDirty()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded bg-white">
                                    <option value="ornamental-double">Doble Marco Clásico Institucional</option>
                                    <option value="gold-filigree">Filigrana Dorada de Honor</option>
                                    <option value="modern-line">Línea Sólida Moderna</option>
                                    <option value="none">Sin Marco</option>
                                </select>
                            </div>

                            <div>
                                <label for="studio-control-14" class="block text-[10px] text-slate-500 mb-0.5">Tipografía del documento</label>
                                <select id="studio-control-14" x-model="schema.font_family" @change="markDirty()" class="w-full py-1.5 px-2 text-xs border border-slate-300 rounded bg-white">
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
                            <span class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider">Colores del documento</span>

                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Color principal</label>
                                <div class="flex items-center gap-2">
                                    <input type="color" x-model="schema.primary_color" @input="markDirty()" class="w-8 h-8 rounded border border-slate-300 cursor-pointer">
                                    <input type="text" x-model="schema.primary_color" @input="markDirty()" class="flex-1 py-1 px-2 text-xs border border-slate-300 rounded font-mono uppercase bg-white">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] text-slate-500 mb-0.5">Color de acento</label>
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

    <footer class="ft-studio-statusbar">
        <span><span class="ft-studio-status-dot"></span><span x-text="elements.length + ' elementos'"></span><span class="ft-studio-status-selection" x-show="selectedElement" x-text="' · ' + (selectedElement?.name || selectedElement?.type || '')"></span></span>
        <span class="ft-studio-shortcuts">Flechas: mover · Mayús: mover 4× · Supr: eliminar · Ctrl+S: guardar</span>
        <span>Vista de diseño · Datos de ejemplo</span>
    </footer>
    <div x-cloak x-show="showVersionDialog" class="ft-studio-modal-backdrop" @click.self="closeVersionDialog()" @keydown.escape.stop="closeVersionDialog()">
        <section x-ref="versionDialog" role="dialog" aria-modal="true" aria-labelledby="studio-version-title" tabindex="-1" class="ft-studio-modal" @keydown.tab="trapVersionFocus($event)">
            <div class="flex justify-between items-start gap-4"><div><p class="ft-eyebrow">Historial de diseño</p><h2 id="studio-version-title" class="text-xl text-brand font-bold mt-2">Crear versión {{ $template->current_version + 1 }}.0</h2></div><button type="button" @click="closeVersionDialog()" class="ft-studio-icon-button" aria-label="Cerrar ventana">✕</button></div>
            <p class="text-sm text-slate-600 mt-4 leading-relaxed">Se guardará este diseño como una nueva revisión y la versión {{ $template->current_version }}.0 quedará en el historial. Para los ajustes habituales, utiliza <strong>Guardar cambios</strong>.</p>
            <label for="studio-version-summary" class="block text-sm font-semibold text-brand mt-5 mb-2">¿Qué cambió? <span class="font-normal text-slate-500">(opcional)</span></label>
            <textarea id="studio-version-summary" x-ref="versionSummary" x-model="versionChangeSummary" maxlength="255" rows="3" class="w-full rounded-xl border border-slate-300 p-3 text-sm" placeholder="Ej.: Nueva distribución de firmas y encabezado."></textarea>
            <div class="flex justify-end gap-3 mt-6"><button type="button" @click="closeVersionDialog()" class="ft-studio-button">Cancelar</button><button type="button" @click="saveTemplate(true)" :disabled="isSaving" class="ft-studio-save rounded-xl"><span x-text="isSaving ? 'Guardando…' : 'Crear nueva versión'"></span></button></div>
        </section>
    </div>
</div>

@include('templates.studio-controller')
@endsection
