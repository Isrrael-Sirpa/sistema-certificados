@extends('layouts.app')

@section('page-title', 'Panel de Control Principal')
@section('page-subtitle', 'Resumen ejecutivo de actividades, certificados y estado de vigencia')

@section('content')
<div class="space-y-6">

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Emitidos -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Certificados</p>
                <h3 class="text-2xl font-bold text-[#024966] mt-1">{{ number_format($totalCertificates) }}</h3>
                <span class="text-[11px] text-slate-400">Histórico de emisiones</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-[#024966] flex items-center justify-center text-xl">
                📜
            </div>
        </div>

        <!-- Vigentes -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Certificados Vigentes</p>
                <h3 class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($totalActive) }}</h3>
                <span class="text-[11px] text-emerald-600 font-medium">● Regla de 3 años activa</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl">
                ✅
            </div>
        </div>

        <!-- Expirados -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Certificados Expirados</p>
                <h3 class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($totalExpired) }}</h3>
                <span class="text-[11px] text-amber-600 font-medium">● Mayor a 3 años</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl">
                ⏳
            </div>
        </div>

        <!-- Revocados -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-red-600 uppercase tracking-wider">Revocados / Anulados</p>
                <h3 class="text-2xl font-bold text-red-700 mt-1">{{ number_format($totalRevoked) }}</h3>
                <span class="text-[11px] text-red-600 font-medium">● Sin validez legal</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center text-xl">
                🚫
            </div>
        </div>

    </div>

    <!-- Quick Stats Secondary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center gap-3">
            <div class="p-3 bg-red-50 text-[#BB2026] rounded-xl text-lg">👥</div>
            <div>
                <span class="text-xs text-slate-500 font-medium block">Beneficiarios Registrados</span>
                <span class="text-lg font-bold text-[#024966]">{{ number_format($totalBeneficiaries) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center gap-3">
            <div class="p-3 bg-blue-50 text-[#32639E] rounded-xl text-lg">🎯</div>
            <div>
                <span class="text-xs text-slate-500 font-medium block">Actividades Académicas</span>
                <span class="text-lg font-bold text-[#024966]">{{ number_format($totalActivities) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center gap-3">
            <div class="p-3 bg-slate-100 text-[#024966] rounded-xl text-lg">🔍</div>
            <div>
                <span class="text-xs text-slate-500 font-medium block">Verificaciones QR Consultadas</span>
                <span class="text-lg font-bold text-[#024966]">{{ number_format($totalVerifications) }}</span>
            </div>
        </div>
    </div>

    <!-- Recent Certificates Table & Verifications Feed -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Recent Certificates (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-[#024966]">Últimos Certificados Emitidos</h3>
                    <p class="text-xs text-slate-500">Documentos generados recientemente por el personal</p>
                </div>
                <a href="{{ route('certificates.index') }}" class="text-xs font-semibold text-[#32639E] hover:underline">
                    Ver todos &rarr;
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-100">
                        <tr>
                            <th class="px-5 py-3">Código</th>
                            <th class="px-5 py-3">Beneficiario</th>
                            <th class="px-5 py-3">Actividad</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($recentCertificates as $cert)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3 font-mono font-semibold text-[#32639E]">
                                    <a href="{{ route('certificates.show', $cert) }}" class="hover:underline">
                                        {{ $cert->certificate_code }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 font-medium text-slate-900">
                                    {{ $cert->beneficiary->full_name }}
                                </td>
                                <td class="px-5 py-3 text-slate-600 truncate max-w-xs" title="{{ $cert->activity->name }}">
                                    {{ $cert->activity->name }}
                                </td>
                                <td class="px-5 py-3">
                                    @if($cert->isRevoked())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-red-100 text-red-800">
                                            REVOCADO
                                        </span>
                                    @elseif($cert->isExpired())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-100 text-amber-800">
                                            EXPIRADO
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">
                                            VIGENTE
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right space-x-1">
                                    <a href="{{ route('certificates.preview', $cert) }}" target="_blank" title="Vista Previa PDF" class="p-1.5 text-slate-500 hover:text-[#32639E] hover:bg-slate-100 rounded inline-block">
                                        👁️
                                    </a>
                                    <a href="{{ route('certificates.download', $cert) }}" title="Descargar PDF" class="p-1.5 text-slate-500 hover:text-green-600 hover:bg-slate-100 rounded inline-block">
                                        📥
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-400">
                                    No hay certificados emitidos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Verifications (1 Col) -->
        <div class="bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="p-5 border-b border-slate-100">
                <h3 class="text-sm font-bold text-[#024966]">Actividad de Verificación QR</h3>
                <p class="text-xs text-slate-500">Últimas consultas públicas realizadas</p>
            </div>

            <div class="p-4 divide-y divide-slate-100">
                @forelse($recentVerifications as $ver)
                    <div class="py-3 flex items-start gap-3">
                        <span class="p-2 rounded-lg bg-slate-100 text-sm">📱</span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-800 truncate">
                                {{ $ver->certificate->certificate_code }}
                            </p>
                            <p class="text-[11px] text-slate-500 truncate">
                                {{ $ver->certificate->beneficiary->full_name }}
                            </p>
                            <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                                <span>{{ $ver->verification_method === 'qr_scan' ? 'Escaneo QR' : 'Código Manual' }}</span>
                                <span>{{ $ver->verified_at->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-8 text-center text-xs text-slate-400">
                        No hay consultas de verificación registradas aún.
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>
@endsection
