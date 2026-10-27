@extends('layouts.app')

@section('page-title', 'Perfil del Beneficiario')
@section('page-subtitle', 'Historial académico y certificados oficiales emitidos')

@section('content')
<div class="space-y-6">

    <!-- Header Card -->
    <div class="ft-panel bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 flex flex-wrap items-center justify-between gap-4">
        <div class="flex min-w-0 items-center gap-4">
            <div class="w-14 h-14 shrink-0 rounded-2xl bg-brand text-white flex items-center justify-center font-bold text-xl shadow-md">
                {{ substr($beneficiary->first_name, 0, 1) }}{{ substr($beneficiary->last_name, 0, 1) }}
            </div>
            <div class="min-w-0 break-words">
                <h3 class="text-xl font-bold text-brand">{{ $beneficiary->full_title_name }}</h3>
                <p class="text-xs text-slate-500 font-mono">{{ $beneficiary->formatted_document }} • {{ $beneficiary->institution_or_department ?? 'Sin unidad especificada' }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('certificates.create', ['beneficiary_id' => $beneficiary->id]) }}"
               class="px-4 py-2 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-xs transition-all flex items-center gap-1.5">
                <span>+ Emitir Certificado</span>
            </a>
            <a href="{{ route('beneficiaries.edit', $beneficiary) }}"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all">
                ✏️ Editar
            </a>
        </div>
    </div>

    <!-- Details and Certificate History -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Beneficiary Info -->
        <div class="ft-panel bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 space-y-4">
            <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Información de Contacto</h4>

            <div class="space-y-3 break-words text-xs">
                <div>
                    <span class="text-slate-400 block font-semibold">Correo Electrónico</span>
                    <span class="text-slate-800 font-medium">{{ $beneficiary->email ?? 'No registrado' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-semibold">Teléfono</span>
                    <span class="text-slate-800 font-medium">{{ $beneficiary->phone ?? 'No registrado' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-semibold">Grado / Título Académico</span>
                    <span class="text-slate-800 font-medium">{{ $beneficiary->academic_title ?? 'Sin título' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 block font-semibold">Fecha de Registro</span>
                    <span class="text-slate-800 font-medium">{{ $beneficiary->created_at->format('d/m/Y H:i') }}</span>
                </div>
                @if($beneficiary->notes)
                    <div>
                        <span class="text-slate-400 block font-semibold">Notas</span>
                        <p class="text-slate-600 bg-slate-50 p-2.5 rounded-lg mt-1">{{ $beneficiary->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Certificate History -->
        <div class="lg:col-span-2 ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h4 class="text-xs font-bold text-brand uppercase tracking-wider">Historial de Certificados Emitidos</h4>
                    <p class="text-xs text-slate-400">{{ $beneficiary->certificates->count() }} certificado(s) registrados a este titular</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                        <tr>
                            <th class="px-5 py-3">Código</th>
                            <th class="px-5 py-3">Actividad</th>
                            <th class="px-5 py-3">Tipo</th>
                            <th class="px-5 py-3">Emisión</th>
                            <th class="px-5 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($beneficiary->certificates as $cert)
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-5 py-3 font-mono font-bold text-brand-hover">
                                    <a href="{{ route('certificates.show', $cert) }}" class="hover:underline">
                                        {{ $cert->certificate_code }}
                                    </a>
                                </td>
                                <td class="px-5 py-3 text-slate-800 max-w-xs truncate" title="{{ $cert->activity->name }}">
                                    {{ $cert->activity->name }}
                                </td>
                                <td class="px-5 py-3 text-slate-600">
                                    {{ $cert->certificateType->name }}
                                </td>
                                <td class="px-5 py-3 text-slate-600">
                                    {{ $cert->issue_date->format('d/m/Y') }}
                                </td>
                                <td class="px-5 py-3">
                                    @if($cert->isRevoked())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                            REVOCADO
                                        </span>
                                    @elseif($cert->isExpired())
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800">
                                            EXPIRADO
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                            VIGENTE
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-3 text-right space-x-1 whitespace-nowrap">
                                    <a href="{{ route('certificates.preview', $cert) }}" target="_blank" class="p-1.5 text-slate-500 hover:text-brand-hover hover:bg-slate-100 rounded inline-block" title="Vista Previa PDF">
                                        👁️
                                    </a>
                                    <a href="{{ route('certificates.download', $cert) }}" class="p-1.5 text-slate-500 hover:text-green-700 hover:bg-slate-100 rounded inline-block" title="Descargar">
                                        📥
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-slate-400">
                                    No se han emitido certificados para este beneficiario todavía.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</div>
@endsection
