@extends('layouts.app')

@section('page-title', 'Reportes y Estadísticas')
@section('page-subtitle', 'Consolidado de emisiones, estados de vigencia y exportación')

@section('content')
<div class="space-y-6">

    <!-- Filter Card -->
    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Desde</label>
                <input type="date" name="start_date" value="{{ $startDate }}" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Hasta</label>
                <input type="date" name="end_date" value="{{ $endDate }}" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg">
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Actividad</label>
                <select name="activity_id" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg">
                    <option value="">Todas</option>
                    @foreach($activities as $act)
                        <option value="{{ $act->id }}" {{ $activityId == $act->id ? 'selected' : '' }}>{{ Str::limit($act->name, 30) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tipo</label>
                <select name="certificate_type_id" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg">
                    <option value="">Todos</option>
                    @foreach($types as $tp)
                        <option value="{{ $tp->id }}" {{ $typeId == $tp->id ? 'selected' : '' }}>{{ $tp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 px-4 bg-[#BB2026] text-white text-xs font-semibold rounded-lg hover:bg-[#9e1a1f]">
                    Generar Reporte
                </button>
                <button type="button" onclick="window.print()" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs" title="Imprimir Reporte">
                    🖨️
                </button>
            </div>

        </form>
    </div>

    <!-- Summary Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-xl border border-slate-200 text-center">
            <span class="text-xs text-slate-500 block">Total en Periodo</span>
            <span class="text-2xl font-bold text-slate-900">{{ $total }}</span>
        </div>
        <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-100 text-center">
            <span class="text-xs text-emerald-700 block">Vigentes</span>
            <span class="text-2xl font-bold text-emerald-800">{{ $totalVigentes }}</span>
        </div>
        <div class="bg-amber-50 p-4 rounded-xl border border-amber-100 text-center">
            <span class="text-xs text-amber-700 block">Expirados</span>
            <span class="text-2xl font-bold text-amber-800">{{ $totalExpirados }}</span>
        </div>
        <div class="bg-red-50 p-4 rounded-xl border border-red-100 text-center">
            <span class="text-xs text-red-700 block">Revocados</span>
            <span class="text-2xl font-bold text-red-800">{{ $totalRevocados }}</span>
        </div>
    </div>

    <!-- Report Table -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Código</th>
                        <th class="px-5 py-3">Beneficiario</th>
                        <th class="px-5 py-3">CI</th>
                        <th class="px-5 py-3">Actividad</th>
                        <th class="px-5 py-3">Tipo</th>
                        <th class="px-5 py-3">Emisión</th>
                        <th class="px-5 py-3">Vencimiento</th>
                        <th class="px-5 py-3">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($certificates as $cert)
                        <tr class="hover:bg-slate-50/80">
                            <td class="px-5 py-2.5 font-mono font-bold text-[#024966]">{{ $cert->certificate_code }}</td>
                            <td class="px-5 py-2.5 font-semibold text-slate-900">{{ $cert->beneficiary->full_name }}</td>
                            <td class="px-5 py-2.5 font-mono text-slate-600">{{ $cert->beneficiary->formatted_document }}</td>
                            <td class="px-5 py-2.5 text-slate-700 max-w-xs truncate">{{ $cert->activity->name }}</td>
                            <td class="px-5 py-2.5 text-slate-600">{{ $cert->certificateType->name }}</td>
                            <td class="px-5 py-2.5 text-slate-600">{{ $cert->issue_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-2.5 text-slate-600">{{ $cert->expiration_date->format('d/m/Y') }}</td>
                            <td class="px-5 py-2.5">
                                @if($cert->isRevoked())
                                    <span class="text-red-700 font-bold">REVOCADO</span>
                                @elseif($cert->isExpired())
                                    <span class="text-amber-700 font-bold">EXPIRADO</span>
                                @else
                                    <span class="text-emerald-700 font-bold">VIGENTE</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                No hay certificados que coincidan con los filtros del reporte.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
