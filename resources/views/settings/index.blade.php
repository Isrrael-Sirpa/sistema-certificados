@extends('layouts.app')

@section('page-title', 'Configuración del Sistema')
@section('page-subtitle', 'Personalización del portal público, parámetros institucionales y catálogo de tipos de certificado')

@section('content')
<div class="space-y-6" x-data="{
    activeTab: '{{ request('tab', 'portal') }}',
    newTypeModal: false,
    editTypeModal: false,
    editingType: {
        id: null,
        name: '',
        code: '',
        description: '',
        validity_mode: 'years',
        default_validity_years: 3,
        has_score: false,
        score_scale: 'Sobre 100 puntos',
        min_passing_score: 51,
        icon: '🎓',
        badge_color: 'emerald',
        is_active: true,
        updateUrl: ''
    }
}">

    <!-- Tab Navigation Bar -->
    <div class="bg-white p-2 rounded-2xl shadow-xs border border-slate-200/80 flex flex-wrap gap-2">
        <button type="button"
                @click="activeTab = 'portal'"
                :class="activeTab === 'portal' ? 'bg-brand text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                class="flex w-full sm:w-auto items-center gap-2 px-4 py-2.5 rounded-xl text-left text-xs transition-all cursor-pointer">
            <svg class="w-4 h-4" :class="activeTab === 'portal' ? 'text-indigo-200' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"></path></svg>
            <span class="min-w-0 break-words">Portal Público de Verificación</span>
            <span class="px-2 py-0.5 text-[10px] rounded-full" :class="activeTab === 'portal' ? 'bg-white/20 text-white' : 'bg-slate-200/80 text-slate-600'">Público</span>
        </button>

        <button type="button"
                @click="activeTab = 'institutional'"
                :class="activeTab === 'institutional' ? 'bg-brand text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs transition-all cursor-pointer">
            <svg class="w-4 h-4" :class="activeTab === 'institutional' ? 'text-indigo-200' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
            <span>Autoridades y Vicedecanato</span>
        </button>

        <button type="button"
                @click="activeTab = 'certificates'"
                :class="activeTab === 'certificates' ? 'bg-brand text-white shadow-xs font-bold' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100 font-semibold'"
                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs transition-all cursor-pointer">
            <svg class="w-4 h-4" :class="activeTab === 'certificates' ? 'text-indigo-200' : 'text-slate-400'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            <span>Políticas y Tipos de Certificado</span>
            <span class="px-2 py-0.5 text-[10px] rounded-full bg-blue-100 text-blue-800 font-bold">{{ $types->count() }}</span>
        </button>
    </div>

    <!-- TAB 1: PERSONALIZACIÓN DEL PORTAL PÚBLICO (/verificar) -->
    <div x-show="activeTab === 'portal'" x-cloak class="space-y-6">
        <div class="ft-panel bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="p-6 bg-gradient-to-r from-brand via-brand to-brand-deep text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-red-200 bg-red-900/40 px-2.5 py-0.5 rounded-full border border-red-500/30">MÓDULO DE VERIFICACIÓN</span>
                        <span class="text-xs text-indigo-100">Ruta pública: <strong>/verificar</strong></span>
                    </div>
                    <h3 class="text-lg font-bold text-white mt-1">Personalización de la Pantalla de Verificación y Escáner QR</h3>
                    <p class="text-xs text-slate-300 mt-0.5">Configure los textos, logotipos institucionales y tarjetas visibles para estudiantes y público general.</p>
                </div>
                <a href="{{ route('verification.index') }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/20 backdrop-blur-xs transition-all shadow-xs shrink-0">
                    <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    <span>Ver Portal Público en vivo</span>
                </a>
            </div>

            <form method="POST" action="{{ route('settings.update') }}" class="p-6 sm:p-8 space-y-6">
                @csrf
                <input type="hidden" name="tab_verification_portal" value="1">

                <!-- 1. Textos e Identidad del Encabezado -->
                <div class="space-y-4">
                    <h4 class="text-xs font-bold text-brand uppercase tracking-wider pb-2 border-b border-slate-100 flex items-center gap-2">
                        <span>1. Encabezado y Textos Principales del Portal</span>
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label for="portal_verify_title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Título Principal del Portal <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="portal_verify_title" id="portal_verify_title"
                                   value="{{ old('portal_verify_title', $allSettings['portal_verify_title']->value ?? 'Verificación Oficial de Certificados') }}" required
                                   class="w-full py-2.5 px-3 text-sm font-semibold border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                            <p class="text-[11px] text-slate-400 mt-1">Título central en letras grandes de la tarjeta de verificación.</p>
                        </div>

                        <div class="md:col-span-2">
                            <label for="portal_verify_subtitle" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Subtítulo e Instrucciones de Consulta
                            </label>
                            <textarea name="portal_verify_subtitle" id="portal_verify_subtitle" rows="2"
                                      class="w-full py-2.5 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">{{ old('portal_verify_subtitle', $allSettings['portal_verify_subtitle']->value ?? 'Consulte la autenticidad, mérito académico, calificaciones y vigencia oficial de certificados emitidos por el Vicedecanato.') }}</textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Explicación breve mostrada debajo del título principal en el portal público.</p>
                        </div>

                        <div>
                            <label for="portal_verify_tagline" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Etiqueta Superior Institucional
                            </label>
                            <input type="text" name="portal_verify_tagline" id="portal_verify_tagline"
                                   value="{{ old('portal_verify_tagline', $allSettings['portal_verify_tagline']->value ?? 'Universidad Mayor de San Andrés • Facultad de Tecnología') }}"
                                   class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                            <p class="text-[11px] text-slate-400 mt-1">Texto en mayúsculas pequeñas sobre el título.</p>
                        </div>

                        <div>
                            <label for="portal_verify_code_placeholder" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                Texto de Ejemplo en el Buscador
                            </label>
                            <input type="text" name="portal_verify_code_placeholder" id="portal_verify_code_placeholder"
                                   value="{{ old('portal_verify_code_placeholder', $allSettings['portal_verify_code_placeholder']->value ?? 'EJ: CERT-2026-000001') }}"
                                   class="w-full py-2 px-3 text-xs font-mono uppercase border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                            <p class="text-[11px] text-slate-400 mt-1">Guía visual en el campo donde se digita el código.</p>
                        </div>
                    </div>
                </div>

                <!-- 2. Emblemas Institucionales -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-brand uppercase tracking-wider pb-2 border-b border-slate-100">
                        2. Emblemas y Logotipos Visibles en la Cabecera
                    </h4>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Escudo UMSA -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-white p-1 border border-slate-200 flex items-center justify-center">
                                    <img src="{{ asset('assets/institutional/logo_umsa.svg') }}" class="w-full h-full object-contain" alt="UMSA">
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">Escudo Oficial UMSA</span>
                                    <span class="text-[11px] text-slate-500">Emblema de la Universidad</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="portal_verify_show_umsa_logo" value="1" class="sr-only peer"
                                       {{ old('portal_verify_show_umsa_logo', $allSettings['portal_verify_show_umsa_logo']->value ?? '1') == '1' ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand"></div>
                            </label>
                        </div>

                        <!-- Escudo FT-UMSA -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl bg-white p-1 border border-slate-200 flex items-center justify-center">
                                    <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-full h-full object-contain" alt="FT-UMSA">
                                </div>
                                <div>
                                    <span class="text-xs font-bold text-slate-800 block">Escudo Facultad de Tecnología</span>
                                    <span class="text-[11px] text-slate-500">Emblema facultativo oficial</span>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="portal_verify_show_ft_logo" value="1" class="sr-only peer"
                                       {{ old('portal_verify_show_ft_logo', $allSettings['portal_verify_show_ft_logo']->value ?? '1') == '1' ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand"></div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 3. Tarjetas Informativas Inferiores (Buscador) -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-brand uppercase tracking-wider pb-2 border-b border-slate-100">
                        3. Tarjetas Informativas del Pie de Página (En el Buscador /verificar)
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <!-- Tarjeta QR -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-brand flex items-center gap-1.5">
                                    <span>📷</span>
                                    <span>Escaneo QR</span>
                                </span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="portal_verify_show_qr_card" value="1" class="sr-only peer"
                                           {{ old('portal_verify_show_qr_card', $allSettings['portal_verify_show_qr_card']->value ?? '1') == '1' ? 'checked' : '' }}>
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand"></div>
                                </label>
                            </div>
                            <div>
                                <label for="portal_verify_qr_card_title" class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Título</label>
                                <input type="text" name="portal_verify_qr_card_title" id="portal_verify_qr_card_title"
                                       value="{{ old('portal_verify_qr_card_title', $allSettings['portal_verify_qr_card_title']->value ?? 'Escaneo QR Instantáneo') }}"
                                       class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label for="portal_verify_qr_card_desc" class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Descripción</label>
                                <textarea name="portal_verify_qr_card_desc" id="portal_verify_qr_card_desc" rows="2"
                                          class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">{{ old('portal_verify_qr_card_desc', $allSettings['portal_verify_qr_card_desc']->value ?? 'Escanee con la cámara de su dispositivo móvil o computadora para validación directa e inmediata sin teclear.') }}</textarea>
                            </div>
                        </div>

                        <!-- Tarjeta Vigencia -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-brand flex items-center gap-1.5">
                                    <span>⏱️</span>
                                    <span>Vigencia Oficial</span>
                                </span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="portal_verify_show_validity_card" value="1" class="sr-only peer"
                                           {{ old('portal_verify_show_validity_card', $allSettings['portal_verify_show_validity_card']->value ?? '1') == '1' ? 'checked' : '' }}>
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand"></div>
                                </label>
                            </div>
                            <div>
                                <label for="portal_verify_validity_card_title" class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Título</label>
                                <input type="text" name="portal_verify_validity_card_title" id="portal_verify_validity_card_title"
                                       value="{{ old('portal_verify_validity_card_title', $allSettings['portal_verify_validity_card_title']->value ?? 'Vigencia Institucional') }}"
                                       class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label for="portal_verify_validity_card_desc" class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Descripción</label>
                                <textarea name="portal_verify_validity_card_desc" id="portal_verify_validity_card_desc" rows="2"
                                          class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">{{ old('portal_verify_validity_card_desc', $allSettings['portal_verify_validity_card_desc']->value ?? 'Certificados con vigencia permanente o validez reglamentaria según el tipo de mérito académico acreditado.') }}</textarea>
                            </div>
                        </div>

                        <!-- Tarjeta Tipos de Certificado -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-brand flex items-center gap-1.5">
                                    <span>🎓</span>
                                    <span>Tipos de Certificado</span>
                                </span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="portal_verify_show_types_card" value="1" class="sr-only peer"
                                           {{ old('portal_verify_show_types_card', $allSettings['portal_verify_show_types_card']->value ?? '1') == '1' ? 'checked' : '' }}>
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand"></div>
                                </label>
                            </div>
                            <div>
                                <label for="portal_verify_types_card_title" class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Título</label>
                                <input type="text" name="portal_verify_types_card_title" id="portal_verify_types_card_title"
                                       value="{{ old('portal_verify_types_card_title', $allSettings['portal_verify_types_card_title']->value ?? 'Tipos de Certificación Institucional') }}"
                                       class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label for="portal_verify_types_card_desc" class="block text-[10px] font-bold text-slate-600 uppercase mb-1">Descripción</label>
                                <textarea name="portal_verify_types_card_desc" id="portal_verify_types_card_desc" rows="2"
                                          class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">{{ old('portal_verify_types_card_desc', $allSettings['portal_verify_types_card_desc']->value ?? 'Acreditaciones oficiales emitidas por la Facultad: Aprobación con evaluación, Asistencia, Disertante y Reconocimientos Vitalicios.') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 4. Parámetros del Resultado de Verificación (Al escanear QR / Consultar) -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <div class="border-b border-slate-100 pb-2">
                        <h4 class="text-xs font-bold text-brand uppercase tracking-wider flex items-center gap-2">
                            <span>4. Visualización del Certificado al Escanear QR / Consultar</span>
                        </h4>
                        <p class="text-xs text-slate-500 mt-0.5">Controle la información y tarjetas que se muestran en la pantalla pública de verificación oficial</p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <!-- Conmutador Calificaciones -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">Placa de Calificación / Notas</span>
                                <span class="text-[11px] text-slate-500">Mostrar notas y menciones acreditadas</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="portal_verify_show_score_badge" value="1" class="sr-only peer"
                                       {{ old('portal_verify_show_score_badge', $allSettings['portal_verify_show_score_badge']->value ?? '1') == '1' ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand"></div>
                            </label>
                        </div>

                        <!-- Conmutador Modalidad / Tipo -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">Insignia de Modalidad</span>
                                <span class="text-[11px] text-slate-500">Mostrar tipo de mérito e icono</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="portal_verify_show_type_badge" value="1" class="sr-only peer"
                                       {{ old('portal_verify_show_type_badge', $allSettings['portal_verify_show_type_badge']->value ?? '1') == '1' ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand"></div>
                            </label>
                        </div>

                        <!-- Conmutador Vigencia -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 flex items-center justify-between">
                            <div>
                                <span class="text-xs font-bold text-slate-800 block">Tarjeta de Vigencia Oficial</span>
                                <span class="text-[11px] text-slate-500">Distintivo Vitalicio / Vencimiento</span>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" name="portal_verify_show_validity_badge" value="1" class="sr-only peer"
                                       {{ old('portal_verify_show_validity_badge', $allSettings['portal_verify_show_validity_badge']->value ?? '1') == '1' ? 'checked' : '' }}>
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand"></div>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label for="portal_verify_footer_note" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Mensaje Institucional al Pie del Resultado Verificado
                        </label>
                        <textarea name="portal_verify_footer_note" id="portal_verify_footer_note" rows="2"
                                  class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">{{ old('portal_verify_footer_note', $allSettings['portal_verify_footer_note']->value ?? 'Documento oficial debidamente validado y registrado en la base de datos institucional del Vicedecanato de la Facultad de Tecnología - UMSA.') }}</textarea>
                        <p class="text-[11px] text-slate-400 mt-1">Texto de respaldo legal y facultativo mostrado al final de la pantalla de verificación.</p>
                    </div>
                </div>

                <div class="pt-4 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100">
                    <a href="{{ route('verification.index') }}" target="_blank" class="text-xs text-brand hover:underline font-semibold flex items-center gap-1">
                        <span>👁️ Probar portal público</span>
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer">
                        Guardar Personalización del Portal
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 2: AUTORIDADES Y VICEDECANATO -->
    <div x-show="activeTab === 'institutional'" x-cloak class="space-y-6">
        <div class="ft-panel bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="p-6 bg-brand text-white">
                <h3 class="text-base font-bold">Parámetros Institucionales y Autoridades</h3>
                <p class="text-xs text-slate-300 mt-0.5">Estos datos se reflejan automáticamente en los certificados impresos y descargados en PDF</p>
            </div>

            <form method="POST" action="{{ route('settings.update') }}" class="p-6 sm:p-8 space-y-6">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="sm:col-span-2">
                        <label for="institution_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre de la Institución</label>
                        <input type="text" name="institution_name" id="institution_name"
                               value="{{ old('institution_name', $allSettings['institution_name']->value ?? 'UNIVERSIDAD MAYOR DE SAN ANDRÉS') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="faculty_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Facultad</label>
                        <input type="text" name="faculty_name" id="faculty_name"
                               value="{{ old('faculty_name', $allSettings['faculty_name']->value ?? 'FACULTAD DE TECNOLOGÍA') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="department_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Unidad Académica</label>
                        <input type="text" name="department_name" id="department_name"
                               value="{{ old('department_name', $allSettings['department_name']->value ?? 'VICEDECANATO') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="authority_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre de la Autoridad (Firma)</label>
                        <input type="text" name="authority_name" id="authority_name"
                               value="{{ old('authority_name', $allSettings['authority_name']->value ?? 'Ing. Aniceto Velarde Forest') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="authority_position" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Cargo de la Autoridad</label>
                        <input type="text" name="authority_position" id="authority_position"
                               value="{{ old('authority_position', $allSettings['authority_position']->value ?? 'Vicedecano') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="dean_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre del Decano (Firma Principal)</label>
                        <input type="text" name="dean_name" id="dean_name"
                               value="{{ old('dean_name', $allSettings['dean_name']->value ?? 'Lic. Jorge Velasco Orellanos') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="dean_position" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Cargo del Decano</label>
                        <input type="text" name="dean_position" id="dean_position"
                               value="{{ old('dean_position', $allSettings['dean_position']->value ?? 'Decano') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="faculty_motto" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Lema Facultativo</label>
                        <input type="text" name="faculty_motto" id="faculty_motto"
                               value="{{ old('faculty_motto', $allSettings['faculty_motto']->value ?? 'Ciencia, cálculo y jerarquía Adelante Tecnología') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="institution_city" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Ciudad y País</label>
                        <input type="text" name="institution_city" id="institution_city"
                               value="{{ old('institution_city', $allSettings['institution_city']->value ?? 'La Paz - Bolivia') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="institution_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Dirección Institucional</label>
                        <input type="text" name="institution_address" id="institution_address"
                               value="{{ old('institution_address', $allSettings['institution_address']->value ?? 'Av. Arce No. 2295') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="system_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Correo de Contacto</label>
                        <input type="email" name="system_email" id="system_email"
                               value="{{ old('system_email', $allSettings['system_email']->value ?? 'vicedecanato.tecnologia@umsa.bo') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer">
                        Guardar Parámetros Institucionales
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- TAB 3: POLÍTICAS Y TIPOS DE CERTIFICADO -->
    <div x-show="activeTab === 'certificates'" x-cloak class="space-y-6">

        <!-- Sección Principal: Catálogo y Políticas de Tipos de Certificado -->
        <div class="ft-panel bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="p-6 bg-brand text-white flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h3 class="text-base font-bold flex items-center gap-2">
                        <span>Tipos Oficiales de Certificado Institucional</span>
                        <span class="bg-white/20 text-white text-xs px-2.5 py-0.5 rounded-full font-mono">{{ $types->count() }} registrados</span>
                    </h3>
                    <p class="text-xs text-slate-300 mt-0.5">Defina las políticas de validez (vitalicia o temporal), escalas de calificación y apariencia de cada mérito.</p>
                </div>
                <button type="button"
                        @click="newTypeModal = true"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5 shrink-0">
                    <span>+ Nuevo Tipo de Certificado</span>
                </button>
            </div>

            <!-- Grid de Tipos de Certificado -->
            <div class="p-6 sm:p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($types as $tp)
                        <div class="p-5 bg-slate-50/80 hover:bg-white rounded-2xl border-2 border-slate-200/90 hover:border-brand/40 shadow-2xs hover:shadow-sm transition-all flex flex-col justify-between space-y-4 group">
                            <div>
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex items-center gap-3">
                                        <div class="w-12 h-12 rounded-xl bg-white border border-slate-200/80 flex items-center justify-center text-2xl shadow-2xs shrink-0">
                                            {{ $tp->icon ?? '📜' }}
                                        </div>
                                        <div>
                                            <h4 class="text-sm font-bold text-slate-900 group-hover:text-brand transition-colors">{{ $tp->name }}</h4>
                                            <span class="text-[10px] font-mono text-slate-400">código: {{ $tp->code }}</span>
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 {{ $tp->is_active ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : 'bg-slate-200 text-slate-600' }}">
                                        {{ $tp->is_active ? '● Activo' : '○ Inactivo' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-3 line-clamp-2">{{ $tp->description ?? 'Sin descripción adicional registrada.' }}</p>
                            </div>

                            <div class="space-y-3 pt-3 border-t border-slate-200/70">
                                <!-- Badges de Políticas -->
                                <div class="flex flex-wrap gap-1.5 text-[11px]">
                                    @if($tp->validity_mode === 'permanent')
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                            ♾️ Vitalicio / Permanente
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                            ⏳ Temporal ({{ $tp->default_validity_years ?? 3 }} años)
                                        </span>
                                    @endif

                                    @if($tp->has_score)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            📊 Con Notas (Mín: {{ $tp->min_passing_score ?? 51 }})
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-200/80 text-slate-700">
                                            🛡️ Acreditación Directa
                                        </span>
                                    @endif
                                </div>

                                <!-- Botones de Gestión -->
                                <div class="flex items-center justify-between pt-2 border-t border-slate-200/60">
                                    <button type="button"
                                            @click="editingType = {
                                                id: {{ $tp->id }},
                                                name: '{{ addslashes($tp->name) }}',
                                                code: '{{ $tp->code }}',
                                                description: '{{ addslashes($tp->description ?? '') }}',
                                                validity_mode: '{{ $tp->validity_mode ?? 'years' }}',
                                                default_validity_years: {{ $tp->default_validity_years ?? 3 }},
                                                has_score: {{ $tp->has_score ? 'true' : 'false' }},
                                                score_scale: '{{ addslashes($tp->score_scale ?? 'Sobre 100 puntos') }}',
                                                min_passing_score: {{ $tp->min_passing_score ?? 51 }},
                                                icon: '{{ $tp->icon ?? '📜' }}',
                                                badge_color: '{{ $tp->badge_color ?? 'slate' }}',
                                                is_active: {{ $tp->is_active ? 'true' : 'false' }},
                                                updateUrl: '{{ route('settings.certificate-types.update', $tp) }}'
                                            }; editTypeModal = true;"
                                            class="px-3 py-1.5 bg-white hover:bg-slate-100 text-slate-700 hover:text-brand border border-slate-200 text-xs font-semibold rounded-xl shadow-2xs transition-all flex items-center gap-1">
                                        <span>✏️ Editar Políticas</span>
                                    </button>

                                    <form method="POST" action="{{ route('settings.certificate-types.toggle', $tp) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-[11px] font-semibold {{ $tp->is_active ? 'text-amber-700 hover:underline' : 'text-emerald-700 hover:underline' }}">
                                            {{ $tp->is_active ? 'Desactivar' : 'Activar' }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Parámetros Secundarios: Vigencia Estándar y Plantillas -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Políticas de Certificación -->
            <div class="lg:col-span-2 ft-panel bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="p-6 bg-slate-900 text-white">
                    <h3 class="text-base font-bold">Vigencia Temporal de Respaldo (Fallback Global)</h3>
                    <p class="text-xs text-slate-300 mt-0.5">Años reglamentarios asignados por defecto a certificados temporales cuando una actividad no defina una duración específica.</p>
                </div>

                <form method="POST" action="{{ route('settings.update') }}" class="p-6 sm:p-8 space-y-4">
                    @csrf
                    <div>
                        <label for="certificate_validity_years" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                            Años de Vigencia Reglamentaria Estándar <span class="text-red-500">*</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="number" name="certificate_validity_years" id="certificate_validity_years" min="1" max="10"
                                   value="{{ old('certificate_validity_years', $allSettings['certificate_validity_years']->value ?? '3') }}" required
                                   class="w-32 py-2 px-3 text-sm font-bold border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                            <span class="text-xs text-slate-500">años calendario a partir de la fecha de emisión</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mt-2">Aplica únicamente a los tipos de certificados configurados con vencimiento temporal (no aplica a certificados vitalicios).</p>
                    </div>

                    <div class="pt-4 flex items-center justify-end border-t border-slate-100">
                        <button type="submit" class="px-6 py-2.5 bg-brand hover:bg-brand-deep text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer">
                            Guardar Vigencia Estándar
                        </button>
                    </div>
                </form>
            </div>

            <!-- Plantillas Registradas -->
            <div class="ft-panel bg-white rounded-3xl p-6 shadow-xs border border-slate-200/80 space-y-4">
                <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                    <h4 class="text-xs font-bold text-brand uppercase tracking-wider">Plantillas Registradas</h4>
                    <a href="{{ route('templates.index') }}" class="text-xs text-brand-hover font-semibold hover:underline">Ver todas &rarr;</a>
                </div>

                <div class="space-y-3">
                    @foreach($templates as $tpl)
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">{{ $tpl->name }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $tpl->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $tpl->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500">{{ $tpl->description }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL 1: NUEVO TIPO DE CERTIFICADO -->
    <div x-show="newTypeModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
         style="display: none;">
        <div class="ft-panel bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto" @click.away="newTypeModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-800 flex items-center justify-center text-xl font-bold">
                        ✨
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Registrar Nuevo Tipo de Certificado</h3>
                        <p class="text-xs text-slate-500">Defina el mérito, esquema de validez y políticas de evaluación</p>
                    </div>
                </div>
                <button @click="newTypeModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl p-1">&times;</button>
            </div>

            <form method="POST" action="{{ route('settings.certificate-types.store') }}" class="space-y-4"
                  x-data="{ newValidityMode: 'permanent', newHasScore: false }">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nombre Oficial del Certificado <span class="text-red-500">*</span></label>
                        <input type="text" name="name" required placeholder="Ej: Certificado de Tutoría de Grado"
                               class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Código / Identificador (Opcional)</label>
                        <input type="text" name="code" placeholder="Ej: tutoria"
                               class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand font-mono">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Icono</label>
                            <input type="text" name="icon" value="🎓" maxlength="5"
                                   class="w-full text-center text-lg py-1 border border-slate-300 rounded-xl">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Color</label>
                            <select name="badge_color" class="w-full text-xs py-2 px-2 border border-slate-300 rounded-xl bg-white">
                                <option value="emerald">Verde</option>
                                <option value="blue">Azul</option>
                                <option value="purple">Morado</option>
                                <option value="amber">Dorado</option>
                                <option value="slate">Gris</option>
                            </select>
                        </div>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Descripción del Mérito</label>
                        <textarea name="description" rows="2" placeholder="Explicación del mérito que acredita este tipo de certificado..."
                                  class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand"></textarea>
                    </div>
                </div>

                <!-- Esquema de Validez -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Régimen de Validez por Defecto</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer">
                            <input type="radio" name="validity_mode" value="permanent" x-model="newValidityMode" class="text-brand">
                            <span class="text-xs font-bold text-slate-800">♾️ Vitalicio / Permanente</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer">
                            <input type="radio" name="validity_mode" value="years" x-model="newValidityMode" class="text-brand">
                            <span class="text-xs font-bold text-slate-800">⏳ Con Vencimiento Temporal</span>
                        </label>
                    </div>

                    <div x-show="newValidityMode === 'years'" class="pt-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Años de Vigencia por Defecto</label>
                        <input type="number" name="default_validity_years" value="3" min="1" max="50"
                               class="w-24 text-xs py-1.5 px-3 border border-slate-300 rounded-xl">
                    </div>
                </div>

                <!-- Políticas de Calificación -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="has_score" value="1" x-model="newHasScore" class="rounded text-brand focus:ring-brand">
                        <span class="text-xs font-bold text-slate-800">📊 Admite Calificaciones y Notas Académicas</span>
                    </label>

                    <div x-show="newHasScore" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Escala de Evaluación</label>
                            <input type="text" name="score_scale" value="Sobre 100 puntos"
                                   class="w-full text-xs py-1.5 px-3 border border-slate-300 rounded-xl">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Nota Mínima de Aprobación</label>
                            <input type="number" step="0.5" name="min_passing_score" value="51"
                                   class="w-full text-xs py-1.5 px-3 border border-slate-300 rounded-xl">
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="newTypeModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                        Crear Tipo de Certificado
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL 2: EDITAR POLÍTICAS DE TIPO DE CERTIFICADO -->
    <div x-show="editTypeModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
         style="display: none;">
        <div class="ft-panel bg-white rounded-3xl shadow-2xl max-w-xl w-full p-6 space-y-4 max-h-[calc(100dvh-2rem)] overflow-y-auto" @click.away="editTypeModal = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-800 flex items-center justify-center text-xl font-bold">
                        ⚙️
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-800">Modificar Políticas del Certificado</h3>
                        <p class="text-xs text-slate-500" x-text="'Tipo: ' + editingType.name"></p>
                    </div>
                </div>
                <button @click="editTypeModal = false" class="text-slate-400 hover:text-slate-700 font-bold text-xl p-1">&times;</button>
            </div>

            <form method="POST" :action="editingType.updateUrl" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Nombre Oficial del Certificado <span class="text-red-500">*</span></label>
                        <input type="text" name="name" x-model="editingType.name" required
                               class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Icono</label>
                            <input type="text" name="icon" x-model="editingType.icon" maxlength="5"
                                   class="w-full text-center text-lg py-1 border border-slate-300 rounded-xl">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 mb-1">Color</label>
                            <select name="badge_color" x-model="editingType.badge_color" class="w-full text-xs py-2 px-2 border border-slate-300 rounded-xl bg-white">
                                <option value="emerald">Verde</option>
                                <option value="blue">Azul</option>
                                <option value="purple">Morado</option>
                                <option value="amber">Dorado</option>
                                <option value="slate">Gris</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Estado</label>
                        <label class="flex items-center gap-2 p-2 bg-slate-50 rounded-xl border border-slate-200 cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" x-model="editingType.is_active" class="rounded text-brand focus:ring-brand">
                            <span class="text-xs font-bold text-slate-800">Tipo Activo en el Sistema</span>
                        </label>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Descripción del Mérito</label>
                        <textarea name="description" x-model="editingType.description" rows="2"
                                  class="w-full text-xs py-2 px-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand"></textarea>
                    </div>
                </div>

                <!-- Esquema de Validez -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Régimen de Validez por Defecto</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer"
                               :class="editingType.validity_mode === 'permanent' ? 'border-brand ring-2 ring-brand/20' : ''">
                            <input type="radio" name="validity_mode" value="permanent" x-model="editingType.validity_mode" class="text-brand">
                            <span class="text-xs font-bold text-slate-800">♾️ Vitalicio / Permanente</span>
                        </label>
                        <label class="flex items-center gap-2 p-2.5 bg-white rounded-xl border border-slate-200 cursor-pointer"
                               :class="editingType.validity_mode === 'years' ? 'border-brand ring-2 ring-brand/20' : ''">
                            <input type="radio" name="validity_mode" value="years" x-model="editingType.validity_mode" class="text-brand">
                            <span class="text-xs font-bold text-slate-800">⏳ Con Vencimiento Temporal</span>
                        </label>
                    </div>

                    <div x-show="editingType.validity_mode === 'years'" class="pt-2">
                        <label class="block text-xs font-bold text-slate-700 mb-1">Años de Vigencia por Defecto</label>
                        <input type="number" name="default_validity_years" x-model.number="editingType.default_validity_years" min="1" max="50"
                               class="w-24 text-xs py-1.5 px-3 border border-slate-300 rounded-xl">
                    </div>
                </div>

                <!-- Políticas de Calificación -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input type="checkbox" name="has_score" value="1" x-model="editingType.has_score" class="rounded text-brand focus:ring-brand">
                        <span class="text-xs font-bold text-slate-800">📊 Admite Calificaciones y Notas Académicas</span>
                    </label>

                    <div x-show="editingType.has_score" class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Escala de Evaluación</label>
                            <input type="text" name="score_scale" x-model="editingType.score_scale"
                                   class="w-full text-xs py-1.5 px-3 border border-slate-300 rounded-xl">
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold text-slate-700 mb-1">Nota Mínima de Aprobación</label>
                            <input type="number" step="0.5" name="min_passing_score" x-model="editingType.min_passing_score"
                                   class="w-full text-xs py-1.5 px-3 border border-slate-300 rounded-xl">
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2">
                    <button type="button" @click="editTypeModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-brand hover:bg-brand-deep text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                        Guardar Cambios del Tipo
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
