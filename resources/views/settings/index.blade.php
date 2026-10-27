@extends('layouts.app')

@section('page-title', 'Configuración del Sistema')
@section('page-subtitle', 'Personalización del portal público, parámetros institucionales y autoridades firmantes')

@section('content')
<div class="space-y-6" x-data="{ activeTab: '{{ request('tab', 'portal') }}' }">

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
            <span>Vigencia y Plantillas</span>
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
                                      class="w-full py-2.5 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">{{ old('portal_verify_subtitle', $allSettings['portal_verify_subtitle']->value ?? 'Consulte la autenticidad, vigencia reglamentaria (3 años) y respaldo institucional de certificados emitidos por el Vicedecanato.') }}</textarea>
                            <p class="text-[11px] text-slate-400 mt-1">Explicación breve mostrada debajo del título principal.</p>
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

                <!-- 3. Tarjetas Informativas Inferiores -->
                <div class="space-y-4 pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-bold text-brand uppercase tracking-wider pb-2 border-b border-slate-100">
                        3. Tarjetas Informativas del Pie de Página
                    </h4>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Tarjeta QR -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-brand flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-sky-100 text-brand flex items-center justify-center text-xs">📷</span>
                                    <span>Tarjeta de Escaneo QR / Cámara</span>
                                </span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="portal_verify_show_qr_card" value="1" class="sr-only peer"
                                           {{ old('portal_verify_show_qr_card', $allSettings['portal_verify_show_qr_card']->value ?? '1') == '1' ? 'checked' : '' }}>
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand"></div>
                                </label>
                            </div>
                            <div>
                                <label for="portal_verify_qr_card_title" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Título</label>
                                <input type="text" name="portal_verify_qr_card_title" id="portal_verify_qr_card_title"
                                       value="{{ old('portal_verify_qr_card_title', $allSettings['portal_verify_qr_card_title']->value ?? 'Escaneo QR Instantáneo') }}"
                                       class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label for="portal_verify_qr_card_desc" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Descripción</label>
                                <textarea name="portal_verify_qr_card_desc" id="portal_verify_qr_card_desc" rows="2"
                                          class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">{{ old('portal_verify_qr_card_desc', $allSettings['portal_verify_qr_card_desc']->value ?? 'Escanee con la cámara de su dispositivo móvil o computadora para validación directa e inmediata sin teclear.') }}</textarea>
                            </div>
                        </div>

                        <!-- Tarjeta Vigencia -->
                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-brand flex items-center gap-2">
                                    <span class="w-6 h-6 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs">⏱️</span>
                                    <span>Tarjeta de Vigencia Institucional</span>
                                </span>
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" name="portal_verify_show_validity_card" value="1" class="sr-only peer"
                                           {{ old('portal_verify_show_validity_card', $allSettings['portal_verify_show_validity_card']->value ?? '1') == '1' ? 'checked' : '' }}>
                                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand"></div>
                                </label>
                            </div>
                            <div>
                                <label for="portal_verify_validity_card_title" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Título</label>
                                <input type="text" name="portal_verify_validity_card_title" id="portal_verify_validity_card_title"
                                       value="{{ old('portal_verify_validity_card_title', $allSettings['portal_verify_validity_card_title']->value ?? 'Vigencia Institucional') }}"
                                       class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">
                            </div>
                            <div>
                                <label for="portal_verify_validity_card_desc" class="block text-[11px] font-bold text-slate-600 uppercase mb-1">Descripción</label>
                                <textarea name="portal_verify_validity_card_desc" id="portal_verify_validity_card_desc" rows="2"
                                          class="w-full py-1.5 px-2.5 text-xs border border-slate-300 rounded-lg">{{ old('portal_verify_validity_card_desc', $allSettings['portal_verify_validity_card_desc']->value ?? 'Certificados con validez curricular de 3 años calendario reglamentarios según normativa facultativa.') }}</textarea>
                            </div>
                        </div>
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

                    <div class="sm:col-span-2">
                        <label for="faculty_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Facultad</label>
                        <input type="text" name="faculty_name" id="faculty_name"
                               value="{{ old('faculty_name', $allSettings['faculty_name']->value ?? 'FACULTAD DE TECNOLOGÍA') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="department_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Unidad o Vicedecanato</label>
                        <input type="text" name="department_name" id="department_name"
                               value="{{ old('department_name', $allSettings['department_name']->value ?? 'VICEDECANATO') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="faculty_motto" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Lema Facultativo</label>
                        <input type="text" name="faculty_motto" id="faculty_motto"
                               value="{{ old('faculty_motto', $allSettings['faculty_motto']->value ?? 'Ciencia, cálculo y jerarquía Adelante Tecnología') }}"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                    </div>

                    <div>
                        <label for="dean_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre del Decano</label>
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
                        <label for="authority_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Nombre del Vicedecano</label>
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
                        <label for="institution_address" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Dirección Facultativa</label>
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

    <!-- TAB 3: PLANTILLAS Y VIGENCIA -->
    <div x-show="activeTab === 'certificates'" x-cloak class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Políticas de Certificación -->
            <div class="lg:col-span-2 ft-panel bg-white rounded-3xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="p-6 bg-brand text-white">
                    <h3 class="text-base font-bold">Reglas de Emisión y Vigencia</h3>
                    <p class="text-xs text-slate-300 mt-0.5">Control reglamentario de caducidad para los certificados emitidos</p>
                </div>

                <form method="POST" action="{{ route('settings.update') }}" class="p-6 sm:p-8 space-y-6">
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
                        <p class="text-[11px] text-slate-400 mt-2">Por normativa de la Facultad de Tecnología de la UMSA, la regla estándar es de 3 años.</p>
                    </div>

                    <div class="pt-4 flex items-center justify-end border-t border-slate-100">
                        <button type="submit" class="px-6 py-2.5 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-xs transition-all cursor-pointer">
                            Guardar Reglas de Vigencia
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

</div>
@endsection

