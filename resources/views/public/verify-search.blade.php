@extends('layouts.guest')

@section('content')
<div class="max-w-2xl mx-auto">
    <!-- Hero / Verification Card -->
    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 overflow-hidden mb-8">
        
        <div class="bg-[#024966] px-8 py-10 text-center text-white relative">
            <div class="inline-flex p-3 rounded-2xl bg-white/10 backdrop-blur-xs border border-white/20 mb-4 shadow-inner">
                <span class="text-4xl">🎓</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight font-serif">Verificación de Certificados Académicos</h1>
            <p class="text-sm text-red-200 mt-2 max-w-lg mx-auto">
                Compruebe la autenticidad, vigencia y validez institucional de certificados emitidos por el Vicedecanato.
            </p>
        </div>

        <div class="p-8 sm:p-10">
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-50 text-red-800 rounded-xl border border-red-200 text-sm flex items-start gap-3">
                    <span class="text-xl">⚠️</span>
                    <div>{{ session('error') }}</div>
                </div>
            @endif

            <form method="POST" action="{{ route('verification.search') }}" class="space-y-4">
                @csrf

                <div>
                    <label for="certificate_code" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                        Ingrese el Código del Certificado
                    </label>
                    <div class="relative rounded-xl shadow-xs">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <input type="text" name="certificate_code" id="certificate_code" value="{{ old('certificate_code') }}" required autofocus
                               class="block w-full pl-12 pr-4 py-3.5 text-base sm:text-lg font-mono tracking-wider uppercase border-2 border-slate-200 rounded-xl focus:ring-2 focus:ring-[#024966] focus:border-[#024966] placeholder-slate-400"
                               placeholder="EJ: CERT-2026-000001">
                    </div>
                    <p class="text-xs text-slate-400 mt-2">
                        💡 Encuentre el código en la esquina inferior derecha o bajo el código QR de su documento impreso/digital.
                    </p>
                </div>

                <div class="pt-3">
                    <button type="submit"
                            class="w-full py-4 px-6 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-base font-bold rounded-xl shadow-lg hover:shadow-xl transition-all flex items-center justify-center gap-2">
                        <span>🔍 Verificar Autenticidad</span>
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-8 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs text-slate-500">
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-2xl">📱</span>
                    <div>
                        <span class="font-bold text-slate-700 block">Escaneo QR Directo</span>
                        Escanee con la cámara de su teléfono para verificación instantánea.
                    </div>
                </div>
                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                    <span class="text-2xl">⏳</span>
                    <div>
                        <span class="font-bold text-slate-700 block">Vigencia Institucional</span>
                        Certificados válidos por 3 años calendario desde su emisión.
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
