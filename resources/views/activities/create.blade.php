@extends('layouts.app')

@section('page-title', 'Registrar Actividad Académica')
@section('page-subtitle', 'Alta de curso, seminario, taller o evento universitario')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        
        <div class="p-6 bg-[#024966] text-white flex items-center justify-between">
            <h3 class="text-base font-bold">Nueva Actividad</h3>
            <a href="{{ route('activities.index') }}" class="text-xs text-slate-300 hover:text-white">&larr; Volver</a>
        </div>

        <form method="POST" action="{{ route('activities.store') }}" class="p-6 sm:p-8 space-y-6">
            @csrf

            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Nombre de la Actividad <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                       placeholder="Ej: Congreso Internacional de Tecnología e Innovación Industrial">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="activity_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Tipo de Evento <span class="text-red-500">*</span>
                    </label>
                    <select name="activity_type" id="activity_type" required
                            class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                        <option value="Curso" {{ old('activity_type') === 'Curso' ? 'selected' : '' }}>Curso</option>
                        <option value="Taller" {{ old('activity_type') === 'Taller' ? 'selected' : '' }}>Taller</option>
                        <option value="Seminario" {{ old('activity_type') === 'Seminario' ? 'selected' : '' }}>Seminario</option>
                        <option value="Congreso" {{ old('activity_type') === 'Congreso' ? 'selected' : '' }}>Congreso</option>
                        <option value="Capacitación" {{ old('activity_type') === 'Capacitación' ? 'selected' : '' }}>Capacitación</option>
                        <option value="Conferencia" {{ old('activity_type') === 'Conferencia' ? 'selected' : '' }}>Conferencia</option>
                        <option value="Diplomado" {{ old('activity_type') === 'Diplomado' ? 'selected' : '' }}>Diplomado</option>
                    </select>
                </div>

                <div>
                    <label for="academic_hours" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Carga Horaria (Horas Académicas) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="academic_hours" id="academic_hours" value="{{ old('academic_hours', 40) }}" min="0" required
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha de Inicio <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date', now()->toDateString()) }}" required
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                </div>

                <div>
                    <label for="end_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Fecha de Conclusión <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date', now()->toDateString()) }}" required
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="location" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Lugar / Modalidad
                    </label>
                    <input type="text" name="location" id="location" value="{{ old('location', 'Paraninfo Universitario y Modalidad Virtual') }}"
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                           placeholder="Ej: Auditorio Central">
                </div>

                <div>
                    <label for="status" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Estado del Evento <span class="text-red-500">*</span>
                    </label>
                    <select name="status" id="status" required
                            class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                        <option value="activa" {{ old('status') === 'activa' ? 'selected' : '' }}>Activa</option>
                        <option value="finalizada" {{ old('status') === 'finalizada' ? 'selected' : '' }}>Finalizada</option>
                        <option value="cancelada" {{ old('status') === 'cancelada' ? 'selected' : '' }}>Cancelada</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="organizer" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Unidad Organizadora
                    </label>
                    <input type="text" name="organizer" id="organizer" value="{{ old('organizer', 'Vicedecanato') }}"
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                </div>

                <div>
                    <label for="responsible_authority" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                        Autoridad Responsable (Firma)
                    </label>
                    <input type="text" name="responsible_authority" id="responsible_authority" value="{{ old('responsible_authority', 'Ing. Aniceto Velarde Forest - Vicedecano') }}"
                           class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                </div>
            </div>

            <div>
                <label for="description" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Descripción del Contenido Académico
                </label>
                <textarea name="description" id="description" rows="3"
                          class="w-full py-2 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]"
                          placeholder="Resumen temático que puede reflejarse en el certificado...">{{ old('description') }}</textarea>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('activities.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                    Guardar Actividad
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
