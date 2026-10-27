@extends('layouts.app')

@section('page-title', 'Emitir Nuevo Certificado Institucional')
@section('page-subtitle', 'Personalización gráfica, selección de logos y marcas de agua, y generación con código QR')

@section('content')
<div x-data="{
    issueDate: '{{ now()->toDateString() }}',
    selectedBeneficiaryId: '{{ old('beneficiary_id', $selectedBeneficiaryId) }}',
    selectedActivity: '{{ old('activity_id', $selectedActivityId) }}',
    selectedTypeId: '{{ old('certificate_type_id') }}',
    selectedTemplate: '{{ old('certificate_template_id', $templates->where('is_default', true)->first()?->id ?? $templates->first()?->id) }}',

    // Graphic Customization (.png, .jpg, .svg)
    showCustomSection: true,
    selectedLogoLeft: '{{ old('logo_left_id') }}',
    selectedLogoRight: '{{ old('logo_right_id') }}',
    selectedWatermark: '{{ old('watermark_asset_id') }}',
    specialNote: '{{ old('special_note') }}',

    // Data Maps for Instant Preview
    activitiesData: {{ Js::from($activities->keyBy('id')) }},
    templatesData: {{ Js::from($templates->keyBy('id')) }},
    beneficiariesData: {{ Js::from($beneficiaries->keyBy('id')) }},
    typesData: {{ Js::from($types->keyBy('id')) }},
    logosData: {{ Js::from($logos->keyBy('id')) }},
    watermarksData: {{ Js::from($watermarks->keyBy('id')) }},

    get expirationDate() {
        if (!this.issueDate) return '';
        const d = new Date(this.issueDate + 'T00:00:00');
        d.setFullYear(d.getFullYear() + 3);
        return d.toISOString().split('T')[0];
    },

    get currentBeneficiary() {
        return this.beneficiariesData[this.selectedBeneficiaryId] || null;
    },

    get currentActivity() {
        return this.activitiesData[this.selectedActivity] || null;
    },

    get currentTemplate() {
        return this.templatesData[this.selectedTemplate] || null;
    },

    get currentType() {
        return this.typesData[this.selectedTypeId] || null;
    },

    onActivityChange() {
        const act = this.currentActivity;
        if (act && act.certificate_config && act.certificate_config.certificate_template_id) {
            this.selectedTemplate = act.certificate_config.certificate_template_id;
        }
    }
}" class="space-y-6">

    <!-- Top Breadcrumb & Return -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('certificates.index') }}" class="hover:text-brand-hover font-medium transition-colors">Certificados</a>
            <span>/</span>
            <span class="text-slate-800 font-bold">Emisión y Personalización</span>
        </div>
        <a href="{{ route('certificates.index') }}" class="text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors flex items-center gap-1">
            &larr; Volver al catálogo
        </a>
    </div>

    <!-- Main Grid Layout: Form Left (7-8 cols) & Summary Sticky Right (4-5 cols) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- ============================================== -->
        <!-- LEFT COLUMN: FORM & SELECTION (7-8 COLS)       -->
        <!-- ============================================== -->
        <div class="lg:col-span-7 xl:col-span-8 space-y-6">

            <form id="issueForm" method="POST" action="{{ route('certificates.store') }}" class="space-y-6">
                @csrf

                <!-- CARD 1: BENEFICIARIO Y ACTIVIDAD -->
                <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 space-y-5">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-brand flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-red-50 text-action flex items-center justify-center text-xs font-bold">1</span>
                            <span>Participante y Evento Académico</span>
                        </h3>
                        <span class="text-[11px] text-slate-400 font-medium">* Campos obligatorios</span>
                    </div>

                    <!-- 1. Beneficiario -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="beneficiary_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Beneficiario del Certificado <span class="text-red-500">*</span>
                            </label>
                            <a href="{{ route('beneficiaries.create') }}" target="_blank" class="text-xs text-brand-hover font-bold hover:underline flex items-center gap-1">
                                + Nuevo participante
                            </a>
                        </div>
                        <select name="beneficiary_id" id="beneficiary_id" x-model="selectedBeneficiaryId" required
                                class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                            <option value="">-- Seleccione un beneficiario registrado --</option>
                            @foreach($beneficiaries as $ben)
                                <option value="{{ $ben->id }}" {{ (old('beneficiary_id', $selectedBeneficiaryId) == $ben->id) ? 'selected' : '' }}>
                                    {{ $ben->last_name }} {{ $ben->first_name }} ({{ $ben->formatted_document }}) {{ $ben->academic_title ? '— ' . $ben->academic_title : '' }}
                                </option>
                            @endforeach
                        </select>
                        <template x-if="currentBeneficiary">
                            <div class="mt-2 p-2.5 bg-slate-50 rounded-xl border border-slate-200 text-xs flex items-center justify-between">
                                <span class="text-slate-600">
                                    <strong class="text-slate-900" x-text="currentBeneficiary.first_name + ' ' + currentBeneficiary.last_name"></strong>
                                    (<span x-text="currentBeneficiary.document_type + ' ' + currentBeneficiary.document_number"></span>)
                                </span>
                                <span class="text-[11px] text-slate-500 font-medium" x-text="currentBeneficiary.institution_or_department || 'Carrera Universitaria'"></span>
                            </div>
                        </template>
                    </div>

                    <!-- 2. Actividad Académica -->
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label for="activity_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Actividad Académica / Evento <span class="text-red-500">*</span>
                            </label>
                            <a href="{{ route('activities.create') }}" target="_blank" class="text-xs text-brand-hover font-bold hover:underline flex items-center gap-1">
                                + Nueva actividad
                            </a>
                        </div>
                        <select name="activity_id" id="activity_id" x-model="selectedActivity" @change="onActivityChange()" required
                                class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                            <option value="">-- Seleccione la actividad académica correspondiente --</option>
                            @foreach($activities as $act)
                                <option value="{{ $act->id }}" {{ (old('activity_id', $selectedActivityId) == $act->id) ? 'selected' : '' }}>
                                    [{{ $act->activity_type }}] {{ $act->name }} ({{ $act->academic_hours }} hrs)
                                    @if($act->certificateConfig && $act->certificateConfig->template)
                                        ★ (Plantilla: {{ $act->certificateConfig->template->name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <template x-if="currentActivity">
                            <div class="mt-2 p-2.5 bg-blue-50/60 rounded-xl border border-blue-100 text-xs flex flex-wrap items-center justify-between gap-2">
                                <span class="text-slate-700">
                                    <strong>Carga Horaria:</strong> <span x-text="currentActivity.academic_hours"></span> hrs académicas |
                                    <strong>Modalidad:</strong> <span x-text="currentActivity.location || 'Paraninfo / Virtual'"></span>
                                </span>
                                <span class="text-[11px] bg-blue-100 text-brand font-bold px-2 py-0.5 rounded-full" x-text="currentActivity.activity_type"></span>
                            </div>
                        </template>
                    </div>

                    <!-- 3. Tipo de Mérito / Certificado -->
                    <div>
                        <label for="certificate_type_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                            Tipo de Mérito o Otorgación <span class="text-red-500">*</span>
                        </label>
                        <select name="certificate_type_id" id="certificate_type_id" x-model="selectedTypeId" required
                                class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand bg-white">
                            <option value="">-- Seleccione el tipo de mérito académico --</option>
                            @foreach($types as $tp)
                                <option value="{{ $tp->id }}" {{ old('certificate_type_id') == $tp->id ? 'selected' : '' }}>
                                    {{ $tp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <!-- CARD 2: SELECTOR VISUAL DE PLANTILLAS INSTITUCIONALES -->
                <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 space-y-4">
                    <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-brand flex items-center gap-2">
                                <span class="w-6 h-6 rounded-lg bg-red-50 text-action flex items-center justify-center text-xs font-bold">2</span>
                                <span>Plantilla de Diseño Institucional</span>
                            </h3>
                            <p class="text-xs text-slate-500 mt-0.5">Haga clic sobre una plantilla para aplicarla instantáneamente al certificado</p>
                        </div>
                        <a href="{{ route('templates.index') }}" target="_blank" class="text-xs font-bold text-brand-hover hover:underline flex items-center gap-1">
                            Ver diseñador &rarr;
                        </a>
                    </div>

                    <!-- Hidden input to submit selected template -->
                    <input type="hidden" name="certificate_template_id" :value="selectedTemplate">

                    <!-- Visual Grid of Template Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5 pt-1">
                        @foreach($templates as $tpl)
                            <div @click="selectedTemplate = '{{ $tpl->id }}'"
                                 :class="selectedTemplate == '{{ $tpl->id }}' ? 'border-brand bg-blue-50/30 ring-2 ring-brand/30 shadow-xs' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60 bg-white'"
                                 class="p-3.5 rounded-xl border transition-all cursor-pointer flex flex-col justify-between group relative">

                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400" x-text="'v{{ $tpl->current_version }}.0'"></span>
                                        <div class="flex items-center gap-1">
                                            @if($tpl->is_default)
                                                <span class="bg-red-100 text-action text-[9px] font-bold px-1.5 py-0.2 rounded-full">Oficial</span>
                                            @endif
                                            <span class="text-[9px] font-bold px-1.5 py-0.2 rounded-full uppercase {{ $tpl->orientation === 'landscape' ? 'bg-slate-100 text-slate-600' : 'bg-purple-100 text-purple-700' }}">
                                                {{ $tpl->orientation === 'landscape' ? 'A4 Horiz.' : 'A4 Vert.' }}
                                            </span>
                                        </div>
                                    </div>

                                    <h4 class="text-xs font-bold text-slate-900 group-hover:text-brand line-clamp-1">
                                        {{ $tpl->name }}
                                    </h4>
                                    <p class="text-[11px] text-slate-500 line-clamp-2 leading-tight">
                                        {{ $tpl->description ?? 'Diseño institucional formal con bordes y tipografía universitaria.' }}
                                    </p>
                                </div>

                                <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-[10px] font-semibold text-slate-400 capitalize">{{ $tpl->category }}</span>
                                    <span :class="selectedTemplate == '{{ $tpl->id }}' ? 'text-brand font-bold' : 'text-slate-300'" class="text-xs">
                                        ✓ Seleccionada
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- CARD 3: PERSONALIZACIÓN VISUAL (LOGOS .PNG/.JPG Y MARCA DE AGUA) -->
                <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 space-y-4">
                    <div class="flex items-center justify-between cursor-pointer" @click="showCustomSection = !showCustomSection">
                        <h3 class="text-sm font-bold text-brand flex items-center gap-2">
                            <span class="w-6 h-6 rounded-lg bg-red-50 text-action flex items-center justify-center text-xs font-bold">3</span>
                            <span>Personalización Visual (Logos e Imágenes .JPG / .PNG / Marca de Agua)</span>
                        </h3>
                        <span class="text-xs text-slate-400 flex items-center gap-1 font-medium">
                            <span x-text="showCustomSection ? 'Ocultar ajustes' : 'Configurar logos'"></span>
                            <svg class="w-4 h-4 transform transition-transform" :class="showCustomSection ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </div>

                    <div x-show="showCustomSection" class="pt-4 border-t border-slate-100 space-y-5" style="display: none;">
                        <p class="text-xs text-slate-500">
                            Personalice las imágenes que figurarán en el documento. Si no selecciona ninguna, el sistema utilizará los escudos y logos predeterminados de la plantilla.
                        </p>

                        <!-- Logos Selection Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                            <!-- Logo Izquierdo -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Logo Superior Izquierdo (.png, .jpg, .svg)
                                </label>
                                <select name="logo_left_id" x-model="selectedLogoLeft"
                                        class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-brand">
                                    <option value="">-- Usar Logo Predeterminado (Escudo UMSA) --</option>
                                    @foreach($logos as $lg)
                                        <option value="{{ $lg->id }}" {{ old('logo_left_id') == $lg->id ? 'selected' : '' }}>
                                            🛡️ {{ $lg->name }} ({{ strtoupper(pathinfo($lg->file_path, PATHINFO_EXTENSION)) }})
                                        </option>
                                    @endforeach
                                </select>
                                <template x-if="logosData[selectedLogoLeft]">
                                    <div class="p-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-3">
                                        <img :src="logosData[selectedLogoLeft].url" class="w-10 h-10 object-contain" alt="Logo preview">
                                        <div class="text-[11px] text-slate-600 truncate">
                                            <p class="font-bold truncate" x-text="logosData[selectedLogoLeft].name"></p>
                                            <span class="text-slate-400">Incrustado en Base64</span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <!-- Logo Derecho -->
                            <div class="space-y-2">
                                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                    Logo Superior Derecho (.png, .jpg, .svg)
                                </label>
                                <select name="logo_right_id" x-model="selectedLogoRight"
                                        class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-brand">
                                    <option value="">-- Usar Logo Predeterminado (Logo Facultad) --</option>
                                    @foreach($logos as $lg)
                                        <option value="{{ $lg->id }}" {{ old('logo_right_id') == $lg->id ? 'selected' : '' }}>
                                            🛡️ {{ $lg->name }} ({{ strtoupper(pathinfo($lg->file_path, PATHINFO_EXTENSION)) }})
                                        </option>
                                    @endforeach
                                </select>
                                <template x-if="logosData[selectedLogoRight]">
                                    <div class="p-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-3">
                                        <img :src="logosData[selectedLogoRight].url" class="w-10 h-10 object-contain" alt="Logo preview">
                                        <div class="text-[11px] text-slate-600 truncate">
                                            <p class="font-bold truncate" x-text="logosData[selectedLogoRight].name"></p>
                                            <span class="text-slate-400">Incrustado en Base64</span>
                                        </div>
                                    </div>
                                </template>
                            </div>

                        </div>

                        <!-- Marca de Agua Gráfica -->
                        <div class="space-y-2 pt-2 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                                Marca de Agua Gráfica de Fondo (.png transparente / .jpg / escudo)
                            </label>
                            <select name="watermark_asset_id" x-model="selectedWatermark"
                                    class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-brand">
                                <option value="">-- Sin Marca de Agua Gráfica (o usar texto de plantilla) --</option>
                                @foreach($watermarks as $wm)
                                    <option value="{{ $wm->id }}" {{ old('watermark_asset_id') == $wm->id ? 'selected' : '' }}>
                                        💧 {{ $wm->name }} ({{ strtoupper(pathinfo($wm->file_path, PATHINFO_EXTENSION)) }})
                                    </option>
                                @endforeach
                            </select>
                            <template x-if="watermarksData[selectedWatermark]">
                                <div class="p-2 bg-slate-50 rounded-xl border border-slate-200 flex items-center gap-3">
                                    <img :src="watermarksData[selectedWatermark].url" class="w-10 h-10 object-contain opacity-40" alt="Watermark preview">
                                    <div class="text-[11px] text-slate-600">
                                        <p class="font-bold" x-text="watermarksData[selectedWatermark].name"></p>
                                        <span class="text-slate-400">Se estampará centrada con opacidad elegante sin tapar los textos</span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Special Note -->
                        <div class="pt-2 border-t border-slate-100">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Mención de Honor / Nota Aclaratoria Específica (Opcional)
                            </label>
                            <input type="text" name="special_note" x-model="specialNote"
                                   placeholder="Ej: Con mención de honor por obtener el primer lugar del certamen"
                                   class="w-full py-2.5 px-3 text-xs border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-brand">
                        </div>

                    </div>
                </div>

                <!-- CARD 4: FECHAS Y VIGENCIA -->
                <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 space-y-4">
                    <h3 class="text-sm font-bold text-brand flex items-center gap-2">
                        <span class="w-6 h-6 rounded-lg bg-purple-50 text-purple-800 flex items-center justify-center text-xs font-bold">4</span>
                        <span>Fecha de Emisión y Control Legal de Vigencia</span>
                    </h3>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 p-4 bg-slate-50 rounded-xl border border-slate-200/80">
                        <div>
                            <label for="issue_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Fecha Formal de Emisión <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="issue_date" id="issue_date" x-model="issueDate" required
                                   class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl bg-white focus:ring-2 focus:ring-brand">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                                Vencimiento Automático (+3 Años)
                            </label>
                            <div class="py-2 px-3 bg-white border border-slate-200 rounded-xl flex items-center justify-between text-sm">
                                <span class="font-bold text-slate-800" x-text="expirationDate || '---'"></span>
                                <span class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                    Vigente
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

            </form>

        </div>

        <!-- ============================================== -->
        <!-- RIGHT COLUMN: EXECUTIVE SUMMARY (4-5 COLS)     -->
        <!-- ============================================== -->
        <div class="lg:col-span-5 xl:col-span-4 space-y-5 lg:sticky lg:top-6">

            <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 p-6 space-y-5">
                <div class="border-b border-slate-100 pb-3 flex items-center justify-between">
                    <h3 class="text-sm font-bold text-brand flex items-center gap-2">
                        <span>📜</span> Resumen del Documento
                    </h3>
                    <span class="text-[10px] bg-slate-100 text-slate-600 font-bold px-2 py-0.5 rounded-full uppercase">Borrador</span>
                </div>

                <!-- Live Summary Badges -->
                <div class="space-y-3.5 text-xs">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Beneficiario Titular:</span>
                        <p class="font-bold text-slate-900" x-text="currentBeneficiary ? (currentBeneficiary.first_name + ' ' + currentBeneficiary.last_name) : 'No seleccionado'"></p>
                        <p class="text-[11px] text-slate-500" x-text="currentBeneficiary ? (currentBeneficiary.document_type + ' ' + currentBeneficiary.document_number) : ''"></p>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Actividad Académica:</span>
                        <p class="font-bold text-slate-900 line-clamp-2" x-text="currentActivity ? currentActivity.name : 'No seleccionada'"></p>
                        <p class="text-[11px] text-slate-500" x-text="currentActivity ? (currentActivity.academic_hours + ' hrs académicas') : ''"></p>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-100 space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Plantilla y Formato:</span>
                        <p class="font-bold text-slate-900" x-text="currentTemplate ? currentTemplate.name : 'Predeterminada'"></p>
                        <p class="text-[11px] text-brand font-semibold" x-text="currentTemplate ? ('Orientación: ' + currentTemplate.orientation.toUpperCase()) : ''"></p>
                    </div>

                    <div class="flex items-center justify-between p-3 bg-emerald-50/60 rounded-xl border border-emerald-100 text-emerald-900">
                        <div>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Vigencia Oficial:</span>
                            <p class="font-bold text-xs" x-text="expirationDate ? ('Hasta ' + expirationDate) : '3 años calendario'"></p>
                        </div>
                        <span class="text-xs">🛡️ Válido</span>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 border-t border-slate-100 space-y-3">

                    <!-- 1. Live PDF Draft Preview -->
                    <button type="submit"
                            form="issueForm"
                            formaction="{{ route('certificates.preview-draft') }}"
                            formtarget="_blank"
                            class="w-full py-3 px-4 border-2 border-brand text-brand hover:bg-slate-100 text-xs font-bold rounded-xl transition-all flex items-center justify-center gap-2 cursor-pointer shadow-xs">
                        <svg class="w-4 h-4 text-brand" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <span>[ 👁️ PREVISUALIZAR BORRADOR EN PDF ]</span>
                    </button>
                    <p class="text-[10px] text-slate-400 text-center">Abre el PDF en una pestaña nueva sin guardar en la BD ni gastar correlativo</p>

                    <!-- 2. Final Issue Certificate -->
                    <button type="submit"
                            form="issueForm"
                            class="w-full py-3.5 px-4 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-md hover:shadow-lg transition-all transform hover:-translate-y-0.5 flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        <span>[ ⚡ GENERAR CERTIFICADO OFICIAL ]</span>
                    </button>
                </div>

            </div>

        </div>

    </div>

</div>
@endsection
