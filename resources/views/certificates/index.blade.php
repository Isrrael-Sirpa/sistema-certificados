@extends('layouts.app')

@section('page-title', 'Gestión de Certificados')
@section('page-subtitle', 'Consulta, filtrado, emisión y control de vigencia institucional')

@section('content')
<div class="space-y-6">

    <!-- Filters & Search Bar Card -->
    <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80">
        <form method="GET" action="{{ route('certificates.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
            
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Buscar</label>
                <div class="relative">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Código, beneficiario, CI..." 
                           class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#024966] focus:border-transparent">
                    <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                </div>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Estado</label>
                <select name="status" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#024966]">
                    <option value="">Todos los Estados</option>
                    <option value="VIGENTE" {{ request('status') === 'VIGENTE' ? 'selected' : '' }}>● Vigentes (Válidos)</option>
                    <option value="EXPIRADO" {{ request('status') === 'EXPIRADO' ? 'selected' : '' }}>● Expirados (> 3 años)</option>
                    <option value="REVOCADO" {{ request('status') === 'REVOCADO' ? 'selected' : '' }}>● Revocados / Anulados</option>
                </select>
            </div>

            <!-- Activity -->
            <div>
                <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">Actividad</label>
                <select name="activity_id" class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#024966]">
                    <option value="">Todas las Actividades</option>
                    @foreach($activities as $act)
                        <option value="{{ $act->id }}" {{ request('activity_id') == $act->id ? 'selected' : '' }}>{{ Str::limit($act->name, 35) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full py-2 px-4 bg-[#024966] hover:bg-[#013449] text-white text-xs font-semibold rounded-lg transition-all shadow-xs">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'status', 'activity_id', 'certificate_type_id']))
                    <a href="{{ route('certificates.index') }}" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-xs" title="Limpiar filtros">
                        ✕
                    </a>
                @endif
            </div>

        </form>
    </div>

    <!-- Certificates Table Card -->
    <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        
        <div class="p-5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-4">
            <div>
                <h3 class="text-sm font-bold text-[#024966]">Listado General de Certificados</h3>
                <p class="text-xs text-slate-500">Mostrando {{ $certificates->total() }} certificado(s) registrados</p>
            </div>
            
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-slate-50 hover:bg-slate-100 text-[#024966] text-xs font-semibold rounded-xl border border-slate-200/80 transition-all hover:border-slate-300 shadow-2xs">
                    <svg class="w-4 h-4 text-[#32639E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>Reportes y Estadísticas</span>
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Código</th>
                        <th class="px-5 py-3">Beneficiario / Doc</th>
                        <th class="px-5 py-3">Actividad</th>
                        <th class="px-5 py-3">Tipo</th>
                        <th class="px-5 py-3">Emisión</th>
                        <th class="px-5 py-3">Vencimiento (+3a)</th>
                        <th class="px-5 py-3">Estado</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($certificates as $cert)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3 font-mono font-bold text-[#32639E]">
                                <a href="{{ route('certificates.show', $cert) }}" class="hover:underline">
                                    {{ $cert->certificate_code }}
                                </a>
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('beneficiaries.show', $cert->beneficiary) }}" class="font-semibold text-slate-900 hover:text-[#32639E] block">
                                    {{ $cert->beneficiary->full_name }}
                                </a>
                                <span class="text-[10px] text-slate-400">{{ $cert->beneficiary->formatted_document }}</span>
                            </td>
                            <td class="px-5 py-3 text-slate-700 max-w-xs truncate" title="{{ $cert->activity->name }}">
                                {{ $cert->activity->name }}
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $cert->certificateType->name }}
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $cert->issue_date->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $cert->expiration_date->format('d/m/Y') }}
                            </td>
                            <td class="px-5 py-3">
                                @if($cert->isRevoked())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                                        ● REVOCADO
                                    </span>
                                @elseif($cert->isExpired())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                        ● EXPIRADO
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        ● VIGENTE
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('certificates.show', $cert) }}" class="p-1.5 text-slate-500 hover:text-[#32639E] hover:bg-slate-100 rounded inline-block" title="Detalle y QR">
                                    🔍
                                </a>
                                <a href="{{ route('certificates.preview', $cert) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-[#32639E] hover:bg-slate-100 rounded inline-block" title="Vista Previa PDF">
                                    👁️
                                </a>
                                <a href="{{ route('certificates.download', $cert) }}" class="p-1.5 text-slate-500 hover:text-green-700 hover:bg-slate-100 rounded inline-block" title="Descargar PDF">
                                    📥
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center text-slate-400">
                                No se encontraron certificados con los criterios seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($certificates->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $certificates->links() }}
            </div>
        @endif

    </div>

</div>
@endsection
