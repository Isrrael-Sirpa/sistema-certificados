@extends('layouts.app')

@section('page-title', 'Panel de Control Principal')
@section('page-subtitle', 'Resumen ejecutivo de actividades, certificados y estado de vigencia')

@section('content')
<div class="space-y-6">

    <!-- Institutional Authority Banner -->
    <div class="relative overflow-hidden bg-gradient-to-r from-[#024966] via-[#023f59] to-[#01354a] rounded-3xl p-6 sm:p-7 text-white shadow-xl border border-white/10">
        <div class="absolute -right-8 -bottom-10 opacity-10 pointer-events-none">
            <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-64 h-64 object-contain" alt="">
        </div>

        <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
            <div class="flex items-start sm:items-center gap-4">
                <div class="flex items-center gap-2.5 bg-white/10 backdrop-blur-md p-2 rounded-2xl border border-white/20 shadow-md shrink-0">
                    <div class="w-12 h-14 flex items-center justify-center">
                        <img src="{{ asset('assets/institutional/logo_umsa.svg') }}" class="w-full h-full object-contain drop-shadow" alt="Escudo UMSA" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_umsa.png') }}';">
                    </div>
                    <div class="h-10 w-px bg-white/20"></div>
                    <div class="w-14 h-14 flex items-center justify-center">
                        <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-full h-full object-contain drop-shadow" alt="Escudo FT-UMSA" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_ft_umsa.png') }}';">
                    </div>
                </div>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-red-200 bg-red-900/40 px-2 py-0.5 rounded-full border border-red-500/30">UMSA • TECNOLOGÍA</span>
                        <span class="text-[11px] text-sky-200 italic font-serif">"Ciencia, cálculo y jerarquía Adelante Tecnología"</span>
                    </div>
                    <h2 class="text-xl sm:text-2xl font-black tracking-tight text-white mt-1">Facultad de Tecnología — Vicedecanato</h2>
                    <p class="text-xs text-slate-300 mt-0.5">
                        <span class="font-bold text-white">Decano:</span> Lic. Jorge Velasco Orellanos &nbsp;•&nbsp; 
                        <span class="font-bold text-white">Vicedecano:</span> Ing. Aniceto Velarde Forest
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                <a href="{{ route('beneficiaries.import') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-emerald-600/90 hover:bg-emerald-600 text-white text-xs font-semibold rounded-xl border border-emerald-400/30 backdrop-blur-xs transition-all shadow-xs hover:shadow cursor-pointer">
                    <svg class="w-4 h-4 text-emerald-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span>Importar Estudiantes</span>
                </a>

                <a href="{{ route('activities.create') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white/15 hover:bg-white/25 text-white text-xs font-semibold rounded-xl border border-white/20 backdrop-blur-xs transition-all shadow-xs hover:shadow cursor-pointer">
                    <svg class="w-4 h-4 text-amber-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    <span>Nueva Actividad</span>
                </a>

                <a href="{{ route('templates.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2.5 bg-white/10 hover:bg-white/20 text-white text-xs font-medium rounded-xl border border-white/20 backdrop-blur-xs transition-all shadow-xs hover:shadow cursor-pointer">
                    <svg class="w-4 h-4 text-sky-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"></path></svg>
                    <span>Plantillas</span>
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Total Emitidos -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Certificados</p>
                <h3 class="text-2xl font-bold text-[#024966] mt-1">{{ number_format($totalCertificates) }}</h3>
                <span class="text-[11px] text-slate-400">Histórico de emisiones</span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-[#024966] flex items-center justify-center border border-sky-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
        </div>

        <!-- Vigentes -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Certificados Vigentes</p>
                <h3 class="text-2xl font-bold text-emerald-700 mt-1">{{ number_format($totalActive) }}</h3>
                <span class="text-[11px] text-emerald-600 font-medium flex items-center gap-1 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 inline-block"></span>
                    Regla de 3 años activa
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center border border-emerald-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <!-- Expirados -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <p class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Certificados Expirados</p>
                <h3 class="text-2xl font-bold text-amber-700 mt-1">{{ number_format($totalExpired) }}</h3>
                <span class="text-[11px] text-amber-600 font-medium flex items-center gap-1 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 inline-block"></span>
                    Mayor a 3 años
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center border border-amber-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
        </div>

        <!-- Revocados -->
        <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex items-center justify-between hover:shadow-md transition-shadow">
            <div>
                <p class="text-xs font-semibold text-red-600 uppercase tracking-wider">Revocados / Anulados</p>
                <h3 class="text-2xl font-bold text-red-700 mt-1">{{ number_format($totalRevoked) }}</h3>
                <span class="text-[11px] text-red-600 font-medium flex items-center gap-1 mt-0.5">
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500 inline-block"></span>
                    Sin validez legal
                </span>
            </div>
            <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center border border-red-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
            </div>
        </div>

    </div>

    <!-- Quick Stats Secondary Row -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-red-50 text-[#BB2026] flex items-center justify-center border border-red-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-medium block">Beneficiarios Registrados</span>
                <span class="text-lg font-bold text-[#024966]">{{ number_format($totalBeneficiaries) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-blue-50 text-[#32639E] flex items-center justify-center border border-blue-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
            <div>
                <span class="text-xs text-slate-500 font-medium block">Actividades Académicas</span>
                <span class="text-lg font-bold text-[#024966]">{{ number_format($totalActivities) }}</span>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-4 shadow-xs border border-slate-200/80 flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-slate-100 text-[#024966] flex items-center justify-center border border-slate-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
            </div>
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
                <a href="{{ route('certificates.index') }}" class="text-xs font-semibold text-[#32639E] hover:underline flex items-center gap-1">
                    <span>Ver todos</span>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
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
                                    <a href="{{ route('certificates.preview', $cert) }}" target="_blank" title="Vista Previa PDF" class="p-1.5 text-slate-500 hover:text-[#32639E] hover:bg-slate-100 rounded-lg inline-block transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                    <a href="{{ route('certificates.download', $cert) }}" title="Descargar PDF" class="p-1.5 text-slate-500 hover:text-emerald-600 hover:bg-slate-100 rounded-lg inline-block transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
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
                        <span class="p-2 rounded-xl bg-slate-100 text-[#024966] border border-slate-200 shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-semibold text-slate-800 truncate font-mono">
                                {{ $ver->certificate->certificate_code }}
                            </p>
                            <p class="text-[11px] text-slate-500 truncate">
                                {{ $ver->certificate->beneficiary->full_name }}
                            </p>
                            <div class="flex items-center justify-between text-[10px] text-slate-400 mt-1">
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 font-medium">
                                    {{ $ver->verification_method === 'qr_scan' ? 'Escaneo QR' : 'Código Manual' }}
                                </span>
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
