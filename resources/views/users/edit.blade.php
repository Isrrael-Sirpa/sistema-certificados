@extends('layouts.app')

@section('page-title', 'Editar Usuario')
@section('page-subtitle', 'Actualización de datos y roles de personal')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        
        <div class="p-6 bg-[#024966] text-white flex items-center justify-between">
            <h3 class="text-base font-bold truncate">Editar: {{ $user->name }}</h3>
            <a href="{{ route('users.index') }}" class="text-xs text-slate-300 hover:text-white">&larr; Volver</a>
        </div>

        <form method="POST" action="{{ route('users.update', $user) }}" class="p-6 sm:p-8 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Nombre Completo <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                       class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
            </div>

            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Correo Institucional <span class="text-red-500">*</span>
                </label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                       class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
            </div>

            <div>
                <label for="role_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">
                    Rol y Nivel de Acceso <span class="text-red-500">*</span>
                </label>
                <select name="role_id" id="role_id" required
                        class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                    @foreach($roles as $r)
                        <option value="{{ $r->id }}" {{ old('role_id', $user->role_id) == $r->id ? 'selected' : '' }}>
                            {{ $r->display_name }} ({{ $r->description }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-4">
                <span class="text-xs font-bold text-slate-700 block">Cambiar Contraseña (opcional)</span>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="password" class="block text-[11px] font-medium text-slate-600 mb-1">Nueva Contraseña</label>
                        <input type="password" name="password" id="password"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#024966] bg-white"
                               placeholder="Dejar vacío para mantener">
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-[11px] font-medium text-slate-600 mb-1">Confirmar</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#024966] bg-white"
                               placeholder="Confirmar nueva">
                    </div>
                </div>
            </div>

            <div class="pt-2">
                <label class="flex items-center text-xs text-slate-700 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="w-4 h-4 text-[#024966] border-slate-300 rounded-sm">
                    <span class="ml-2 font-medium">Cuenta activa y habilitada</span>
                </label>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('users.index') }}" class="px-5 py-2.5 text-xs font-semibold text-slate-600 hover:text-slate-900">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
