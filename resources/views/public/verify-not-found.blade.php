@extends('layouts.guest')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="bg-white rounded-3xl shadow-xl border border-slate-200/80 p-8 sm:p-10 text-center">
        <div class="w-16 h-16 bg-red-100 text-red-600 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-4">
            ❌
        </div>
        <h1 class="text-2xl font-bold text-slate-900 font-serif">Certificado No Encontrado</h1>
        <p class="text-sm text-slate-600 mt-2">
            El identificador o código QR consultado (<code class="bg-slate-100 px-2 py-0.5 rounded text-xs">{{ $token }}</code>) no corresponde a ningún certificado registrado en nuestra base de datos.
        </p>

        <div class="mt-8 p-4 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 text-left space-y-1">
            <span class="font-bold block">Posibles motivos:</span>
            <p>• El enlace escaneado fue alterado o está incompleto.</p>
            <p>• El documento no fue emitido a través del sistema oficial del Vicedecanato.</p>
        </div>

        <div class="mt-8">
            <a href="{{ route('verification.index') }}" class="inline-flex items-center gap-2 px-6 py-3 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-sm font-semibold rounded-xl shadow-md transition-all">
                <span>&larr; Intentar búsqueda manual por código</span>
            </a>
        </div>
    </div>
</div>
@endsection
