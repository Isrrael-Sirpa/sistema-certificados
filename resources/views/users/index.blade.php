@extends('layouts.app')

@section('page-title', 'Usuarios y Roles')
@section('page-subtitle', 'Control de acceso del personal del Vicedecanato')

@section('content')
<div class="space-y-6">

    <!-- Header Actions -->
    <div class="flex items-center justify-between bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
        <div>
            <h3 class="text-sm font-bold text-[#024966]">Personal Autorizado</h3>
            <p class="text-xs text-slate-500">Gestione las cuentas y privilegios de acceso</p>
        </div>

        <a href="{{ route('users.create') }}" 
           class="px-4 py-2 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-semibold rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <span>+ Crear Usuario</span>
        </a>
    </div>

    <!-- Users Table -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Usuario</th>
                        <th class="px-5 py-3">Correo Electrónico</th>
                        <th class="px-5 py-3">Rol</th>
                        <th class="px-5 py-3">Último Acceso</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3 font-semibold text-slate-900">
                                {{ $u->name }}
                            </td>
                            <td class="px-5 py-3 text-slate-600 font-mono">
                                {{ $u->email }}
                            </td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $u->isAdmin() ? 'bg-amber-100 text-amber-800' : 'bg-[#024966]/10 text-[#024966]' }}">
                                    {{ $u->role->display_name ?? 'Sin Rol' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-500">
                                {{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Nunca' }}
                            </td>
                            <td class="px-5 py-3">
                                @if($u->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        ● ACTIVO
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                        INACTIVO
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('users.edit', $u) }}" class="p-1.5 text-slate-500 hover:text-[#32639E] hover:bg-slate-100 rounded inline-block" title="Editar">
                                    ✏️
                                </a>

                                @if($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.toggle-status', $u) }}" class="inline-block">
                                        @csrf
                                        <button type="submit" class="p-1.5 {{ $u->is_active ? 'text-red-500 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded" title="{{ $u->is_active ? 'Desactivar' : 'Activar' }}">
                                            {{ $u->is_active ? '🔒' : '🔓' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                No hay usuarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $users->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
