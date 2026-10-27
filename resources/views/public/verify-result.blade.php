@extends('layouts.guest')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="ft-panel bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden">

        <!-- Verification Header Status Banner -->
        @if($certificate->isRevoked())
            <div class="bg-gradient-to-r from-red-600 to-red-800 px-8 py-8 text-center text-white relative">
                <div class="inline-flex p-3 rounded-2xl bg-white/20 mb-3 shadow-inner">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                </div>
                <div class="text-[10px] font-extrabold uppercase tracking-widest text-red-200">Facultad de Tecnología • UMSA</div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight uppercase">Certificado Revocado / Anulado</h1>
                <p class="text-xs sm:text-sm text-red-100 mt-1 max-w-md mx-auto">
                    Este documento ha sido formalmente revocado o anulado por las autoridades del Vicedecanato. No posee validez legal ni curricular.
                </p>
            </div>
        @elseif($certificate->isExpired())
            <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-8 py-8 text-center text-white relative">
                <div class="inline-flex p-3 rounded-2xl bg-white/20 mb-3 shadow-inner">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div class="text-[10px] font-extrabold uppercase tracking-widest text-amber-200">Facultad de Tecnología • UMSA</div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight uppercase">Certificado Expirado</h1>
                <p class="text-xs sm:text-sm text-amber-100 mt-1 max-w-md mx-auto">
                    El documento fue auténtico pero ha superado el periodo reglamentario de vigencia de 3 años a partir de su emisión institucional.
                </p>
            </div>
        @else
            <div class="bg-gradient-to-r from-emerald-600 via-emerald-700 to-brand px-8 py-8 text-center text-white relative">
                <div class="inline-flex p-3 rounded-2xl bg-white/20 mb-3 shadow-inner">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                </div>
                <div class="text-[10px] font-extrabold uppercase tracking-widest text-emerald-200">Facultad de Tecnología • UMSA</div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight uppercase">Certificado Oficial Válido</h1>
                <p class="text-xs sm:text-sm text-emerald-100 mt-1 max-w-md mx-auto">
                    Documento debidamente registrado y validado en la base de datos oficial del Vicedecanato de la Facultad de Tecnología.
                </p>
            </div>
        @endif

        <!-- Verified Metadata Body -->
        <div class="p-8 sm:p-10 space-y-6">

            <!-- Estado & Código con Sello Oficial -->
            <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('assets/institutional/sello_vicedecanato_ft.svg') }}" class="w-14 h-14 object-contain opacity-90 hidden sm:block" alt="Sello Oficial">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Código Único Registrado</span>
                        <span class="text-xl sm:text-2xl font-mono font-black text-brand">{{ $certificate->certificate_code }}</span>
                    </div>
                </div>

                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block text-right">Estado Actual</span>
                    @if($certificate->isRevoked())
                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                            <span class="w-2 h-2 rounded-full bg-red-500 mr-1.5 inline-block"></span>
                            REVOCADO
                        </span>
                    @elseif($certificate->isExpired())
                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            <span class="w-2 h-2 rounded-full bg-amber-500 mr-1.5 inline-block"></span>
                            EXPIRADO
                        </span>
                    @else
                        <span class="inline-flex items-center px-3.5 py-1.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 mr-1.5 inline-block"></span>
                            VIGENTE
                        </span>
                    @endif
                </div>
            </div>

            <!-- Datos del Beneficiario y Actividad -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 bg-slate-50 p-6 rounded-2xl border border-slate-200/60">
                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Beneficiario / Titular</span>
                    <p class="text-lg font-bold text-slate-900">{{ $certificate->beneficiary->full_title_name }}</p>
                    <p class="text-xs text-slate-500 mt-0.5">{{ $certificate->beneficiary->formatted_document }}</p>
                </div>

                <div>
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Modalidad de Certificación</span>
                    <p class="text-base font-bold text-brand">{{ $certificate->certificateType->name }}</p>
                </div>

                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Actividad Académica Registrada</span>
                    <p class="text-base font-semibold text-slate-800">"{{ $certificate->activity->name }}"</p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $certificate->activity->formatted_date_range }}
                        @if($certificate->activity->academic_hours > 0)
                            • {{ $certificate->activity->academic_hours }} horas académicas curriculares
                        @endif
                    </p>
                </div>
            </div>

            <!-- Fechas y Vigencia -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div class="p-4 ft-panel bg-white rounded-2xl border border-slate-200">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Fecha de Emisión</span>
                    <span class="text-sm font-bold text-slate-800">{{ \Carbon\Carbon::parse($certificate->issue_date)->translatedFormat('d/m/Y') }}</span>
                </div>

                <div class="p-4 ft-panel bg-white rounded-2xl border border-slate-200">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Fecha de Vencimiento</span>
                    <span class="text-sm font-bold text-slate-800">{{ \Carbon\Carbon::parse($certificate->expiration_date)->translatedFormat('d/m/Y') }}</span>
                    <span class="text-[10px] text-slate-400 block">(3 años calendario)</span>
                </div>

                <div class="p-4 ft-panel bg-white rounded-2xl border border-slate-200">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">Autoridad Emisora</span>
                    <span class="text-sm font-bold text-slate-800">{{ $certificate->activity->responsible_authority ?? $settings['authority_name'] ?? 'Ing. Aniceto Velarde Forest (Vicedecano)' }}</span>
                </div>
            </div>

            @if($certificate->isRevoked() && $certificate->revocation_reason)
                <div class="p-4 bg-red-50 rounded-2xl border border-red-200 text-xs text-red-900">
                    <strong class="block font-bold mb-1">Motivo institucional de revocación:</strong>
                    {{ $certificate->revocation_reason }}
                </div>
            @endif

            <!-- Botones de Acción -->
            <div class="pt-6 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100">
                <a href="{{ route('verification.index') }}" class="text-xs font-bold text-brand-hover hover:underline flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    <span>Consultar otro certificado</span>
                </a>

                <div class="text-[11px] text-slate-400 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span>Verificado con el servidor central de la Facultad de Tecnología el {{ now()->translatedFormat('d/m/Y H:i:s') }}</span>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
