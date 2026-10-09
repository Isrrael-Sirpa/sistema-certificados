@extends('layouts.app')

@section('page-title', 'Auditoría del Sistema')
@section('page-subtitle', 'Trazabilidad y registro cronológico de operaciones administrativas')

@section('content')
<div class="space-y-6">

    <!-- Filters -->
    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80">
        <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Acción</label>
                <select name="action" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg">
                    <option value="">Todas las Acciones</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>{{ $act }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Usuario</label>
                <select name="user_id" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg">
                    <option value="">Todos los Usuarios</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Fecha</label>
                <input type="date" name="date" value="{{ request('date') }}" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 px-4 bg-[#024966] text-white text-xs font-semibold rounded-lg hover:bg-[#01354a]">
                    Filtrar Logs
                </button>
            </div>
        </form>
    </div>

    <!-- Audit Table -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Fecha y Hora</th>
                        <th class="px-5 py-3">Usuario</th>
                        <th class="px-5 py-3">Acción</th>
                        <th class="px-5 py-3">Descripción</th>
                        <th class="px-5 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-5 py-3 font-mono text-slate-500">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="px-5 py-3 font-semibold text-slate-900">{{ $log->user->name ?? 'Sistema' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-mono font-bold bg-slate-100 text-slate-700">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-slate-700 max-w-md truncate" title="{{ $log->description }}">{{ $log->description ?? '-' }}</td>
                            <td class="px-5 py-3 font-mono text-slate-400">{{ $log->ip_address }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-8 text-center text-slate-400">
                                No se encontraron registros de auditoría.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
