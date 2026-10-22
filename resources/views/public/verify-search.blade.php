@extends('layouts.guest')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Hero / Verification Card -->
    <div class="bg-white rounded-3xl shadow-2xl border border-slate-200/80 overflow-hidden mb-8">
        
        <div class="bg-gradient-to-b from-[#024966] via-[#023f59] to-[#01354a] px-8 py-10 text-center text-white relative">
            <div class="inline-flex items-center gap-3.5 p-3 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 mb-3 shadow-lg">
                <img src="{{ asset('assets/institutional/logo_umsa.svg') }}" class="w-11 h-16 object-contain drop-shadow" alt="Escudo Oficial UMSA" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_umsa.png') }}';">
                <div class="h-12 w-px bg-white/20"></div>
                <img src="{{ asset('assets/institutional/logo_ft_umsa.svg') }}" class="w-16 h-16 object-contain drop-shadow" alt="Emblema Facultad de Tecnología" onerror="this.onerror=null; this.src='{{ asset('assets/institutional/logo_ft_umsa.png') }}';">
            </div>
            <div class="text-[10px] font-extrabold uppercase tracking-widest text-red-200">Universidad Mayor de San Andrés • Facultad de Tecnología</div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-white mt-1">Verificación Oficial de Certificados</h1>
            <p class="text-xs sm:text-sm text-sky-100/90 mt-2 max-w-lg mx-auto">
                Consulte la autenticidad, vigencia reglamentaria (3 años) y respaldo institucional de certificados emitidos por el Vicedecanato.
            </p>
        </div>

        <div class="p-8 sm:p-10">
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 text-red-800 rounded-2xl border border-red-200 text-sm flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <div class="font-medium">{{ session('error') }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('verification.search') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="certificate_code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Código Único de Certificado
                    </label>
                    <div class="relative rounded-2xl shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-6 w-6 text-[#32639E]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <input type="text" name="certificate_code" id="certificate_code" value="{{ old('certificate_code') }}" required autofocus
                               class="block w-full pl-12 pr-4 py-3.5 text-base sm:text-lg font-mono tracking-wider uppercase border-2 border-slate-200 rounded-2xl focus:ring-2 focus:ring-[#024966] focus:border-[#024966] placeholder-slate-400 transition-colors"
                               placeholder="EJ: CERT-2026-000001">
                    </div>
                    <p class="text-xs text-slate-500 mt-2 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-sky-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Encuentre este código al pie de su certificado impreso o bajo el código QR institucional.</span>
                    </p>
                </div>

                <div class="pt-3">
                    <button type="submit"
                            class="w-full py-4 px-6 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-base font-bold rounded-2xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <span>Verificar Autenticidad Registrada</span>
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-8 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-500">
                <div class="flex items-start gap-3.5 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-[#024966] flex items-center justify-center shrink-0 border border-sky-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    </div>
                    <div>
                        <span class="font-bold text-slate-800 block text-xs">Escaneo QR Instantáneo</span>
                        Escanee con la cámara de su dispositivo móvil para validación directa e inmediata sin teclear.
                    </div>
                </div>
                <div class="flex items-start gap-3.5 p-4 bg-slate-50 rounded-2xl border border-slate-100">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center shrink-0 border border-amber-100">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <span class="font-bold text-slate-800 block text-xs">Vigencia Institucional</span>
                        Certificados con validez curricular de 3 años calendario reglamentarios según normativa facultativa.
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
