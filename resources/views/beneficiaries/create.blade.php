@extends('layouts.app')

@section('page-title', 'Registrar Beneficiario')
@section('page-subtitle', 'Alta en el padrón institucional para la emisión de certificados')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        
        <div class="p-6 bg-[#024966] text-white flex items-center justify-between">
            <h3 class="text-base font-bold">Datos del Beneficiario</h3>
            <a href="{{ route('beneficiaries.index') }}" class="text-xs text-slate-200 hover:text-white">&larr; Volver</a>
        </div>

        <form method="POST" action="{{ route('beneficiaries.store') }}" class="p-6 sm:p-8 space-y-6">
            @csrf

            <!-- Documento -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="document_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tipo Doc. <span class="text-red-500">*</span>
                    </label>
                    <select name="document_type" id="document_type" required
                            class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                        <option value="CI" {{ old('document_type') === 'CI' ? 'selected' : '' }}>Cédula de Identidad (CI)</option>
                        <option value="DNI" {{ old('document_type') === 'DNI' ? 'selected' : '' }}>DNI</option>
                        <option value="PASAPORTE" {{ old('document_type') === 'PASAPORTE' ? 'selected' : '' }}>Pasaporte</option>
                        <option value="EXTRANJERIA" {{ old('document_type') === 'EXTRANJERIA' ? 'selected' : '' }}>Cédula Extranjería</option>
                    </select>
                </div>

                <div class="sm:col-span-2">
                    <label for="document_number" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Número de Documento <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="document_number" id="document_number" value="{{ old('document_number') }}" required
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                           placeholder="Ej: 8493021 LP">
                </div>
            </div>

            <!-- Nombres y Apellidos -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="academic_title" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Grado / Título
                    </label>
                    <input type="text" name="academic_title" id="academic_title" value="{{ old('academic_title') }}"
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                           placeholder="Univ. / Lic. / Ing. / Dr.">
                </div>

                <div>
                    <label for="first_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Nombres <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name') }}" required
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                           placeholder="Ej: Rodrigo">
                </div>

                <div>
                    <label for="last_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Apellidos <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name') }}" required
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                           placeholder="Ej: Quispe Mamani">
                </div>
            </div>

            <!-- Contacto & Unidad -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Correo Electrónico
                    </label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}"
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                           placeholder="correo@ejemplo.com">
                </div>

                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Teléfono / Celular
                    </label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                           placeholder="+591 ...">
                </div>

                <div>
                    <label for="institution_or_department" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Carrera / Unidad
                    </label>
                    <input type="text" name="institution_or_department" id="institution_or_department" value="{{ old('institution_or_department') }}"
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                           placeholder="Ej: Informática">
                </div>
            </div>

            <div>
                <label for="notes" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Observaciones / Notas
                </label>
                <textarea name="notes" id="notes" rows="2"
                          class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                          placeholder="Notas administrativas adicionales...">{{ old('notes') }}</textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('beneficiaries.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                    Guardar Beneficiario
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
