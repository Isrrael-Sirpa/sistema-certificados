@extends('layouts.guest')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 overflow-hidden">
        
        <!-- Verification Header Status Banner -->
        @if($certificate->isRevoked())
            <div class="bg-red-600 px-8 py-8 text-center text-white">
                <div class="inline-flex p-3 rounded-2xl bg-white/20 mb-3 shadow-inner">
                    <span class="text-4xl">🚫</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight uppercase font-serif">Certificado Revocado / Anulado</h1>
                <p class="text-sm text-red-100 mt-1 max-w-md mx-auto">
                    Este documento ha sido formalmente revocado o anulado por las autoridades del Vicedecanato. No posee validez legal ni académica.
                </p>
            </div>
        @elseif($certificate->isExpired())
            <div class="bg-amber-600 px-8 py-8 text-center text-white">
                <div class="inline-flex p-3 rounded-2xl bg-white/20 mb-3 shadow-inner">
                    <span class="text-4xl">⏳</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight uppercase font-serif">Certificado Expirado</h1>
                <p class="text-sm text-amber-100 mt-1 max-w-md mx-auto">
                    El documento fue auténtico pero ha superado el periodo reglamentario de vigencia de 3 años a partir de su emisión.
                </p>
            </div>
        @else
            <div class="bg-emerald-700 px-8 py-8 text-center text-white">
                <div class="inline-flex p-3 rounded-2xl bg-white/20 mb-3 shadow-inner">
                    <span class="text-4xl">✅</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight uppercase font-serif">Certificado Oficial Válido</h1>
                <p class="text-sm text-emerald-100 mt-1 max-w-md mx-auto">
                    Documento registrado y validado en la base de datos oficial del Vicedecanato.
                </p>
            </div>
        @endif

        <!-- Verified Metadata Body -->
        <div class="p-8 sm:p-10 space-y-6">

            <!-- Estado & Código -->
            <div class="flex flex-wrap items-center justify-between gap-4 pb-6 border-b border-slate-100">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Código Único Registrado</span>
                    <span class="text-xl sm:text-2xl font-mono font-bold text-[#024966]">{{ $certificate->certificate_code }}</span>
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block text-right">Estado Actual</span>
                    @if($certificate->isRevoked())
                        <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                            ● REVOCADO
                        </span>
                    @elseif($certificate->isExpired())
                        <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            ● EXPIRADO
                        </span>
                    @else
                        <span class="inline-flex items-center px-3.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            ● VIGENTE
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
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Tipo de Certificación</span>
                    <p class="text-base font-bold text-[#024966]">{{ $certificate->certificateType->name }}</p>
                </div>

                <div class="md:col-span-2">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider block mb-1">Actividad Académica</span>
                    <p class="text-base font-semibold text-slate-800">"{{ $certificate->activity->name }}"</p>
                    <p class="text-xs text-slate-500 mt-1">
                        {{ $certificate->activity->formatted_date_range }}
                        @if($certificate->activity->academic_hours > 0)
                            • {{ $certificate->activity->academic_hours }} horas académicas
                        @endif
                    </p>
                </div>
            </div>

            <!-- Fechas y Vigencia -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                <div class="p-4 bg-white rounded-xl border border-slate-200">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Fecha de Emisión</span>
                    <span class="text-sm font-bold text-slate-800">{{ \Carbon\Carbon::parse($certificate->issue_date)->translatedFormat('d/m/Y') }}</span>
                </div>

                <div class="p-4 bg-white rounded-xl border border-slate-200">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Fecha de Vencimiento</span>
                    <span class="text-sm font-bold text-slate-800">{{ \Carbon\Carbon::parse($certificate->expiration_date)->translatedFormat('d/m/Y') }}</span>
                    <span class="text-[10px] text-slate-400 block">(3 años calendario)</span>
                </div>

                <div class="p-4 bg-white rounded-xl border border-slate-200">
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">Autoridad Emisora</span>
                    <span class="text-sm font-bold text-slate-800">{{ $certificate->activity->responsible_authority ?? $settings['authority_name'] ?? 'Vicedecanato' }}</span>
                </div>
            </div>

            @if($certificate->isRevoked() && $certificate->revocation_reason)
                <div class="p-4 bg-red-50 rounded-xl border border-red-200 text-xs text-red-900">
                    <strong class="block font-bold mb-1">Motivo institucional de revocación:</strong>
                    {{ $certificate->revocation_reason }}
                </div>
            @endif

            <!-- Botones de Acción -->
            <div class="pt-6 flex flex-wrap items-center justify-between gap-4 border-t border-slate-100">
                <a href="{{ route('verification.index') }}" class="text-xs font-semibold text-[#32639E] hover:underline flex items-center gap-1">
                    &larr; Consultar otro certificado
                </a>

                <div class="text-[11px] text-slate-400">
                    Verificado de forma segura el {{ now()->translatedFormat('d/m/Y H:i:s') }}
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
