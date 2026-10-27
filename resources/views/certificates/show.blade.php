@extends('layouts.app')

@section('page-title', 'Detalle del Certificado')
@section('page-subtitle', 'Información completa, código QR, trazabilidad y estado de vigencia')

@section('content')
<div class="space-y-6" x-data="{ revokeModal: false }">

    <!-- Top Action Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
        <div class="flex min-w-0 items-center gap-3">
            <div class="w-12 h-12 shrink-0 rounded-xl bg-red-50 text-action font-mono font-bold flex items-center justify-center text-xl">
                📜
            </div>
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-lg font-bold font-mono text-brand">{{ $certificate->certificate_code }}</h3>
                    @if($certificate->isRevoked())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800">
                            ● REVOCADO
                        </span>
                    @elseif($certificate->isExpired())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                            ● EXPIRADO
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800">
                            ● VIGENTE
                        </span>
                    @endif
                </div>
                <p class="break-all text-xs text-slate-400 font-mono">Token: {{ $certificate->verification_token }}</p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('certificates.preview', $certificate) }}" target="_blank"
               class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-brand text-xs font-semibold rounded-lg border border-slate-200 transition-all flex items-center gap-1.5">
                <span>👁️ Vista Previa PDF</span>
            </a>

            <a href="{{ route('certificates.download', $certificate) }}"
               class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg shadow-xs transition-all flex items-center gap-1.5">
                <span>📥 Descargar PDF</span>
            </a>

            @if(auth()->user()->isAdmin() && !$certificate->isRevoked())
                <button @click="revokeModal = true"
                        class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-semibold rounded-lg border border-red-200 transition-all flex items-center gap-1.5">
                    <span>🚫 Anular / Revocar</span>
                </button>
            @endif
        </div>
    </div>

    @if($certificate->isRevoked())
        <div class="p-5 bg-red-50 rounded-2xl border border-red-200 text-sm text-red-900 flex items-start gap-4">
            <span class="text-2xl">⚠️</span>
            <div>
                <h4 class="font-bold text-red-950">Certificado Revocado</h4>
                <p class="text-xs mt-0.5">Revocado el {{ $certificate->revoked_at->format('d/m/Y H:i') }} por <strong>{{ $certificate->revokedBy->name ?? 'Administrador' }}</strong>.</p>
                <p class="text-xs mt-1.5 bg-red-100/60 p-2.5 rounded-lg"><strong>Motivo:</strong> {{ $certificate->revocation_reason }}</p>
            </div>
        </div>
    @endif

    <!-- Main Grid: Info + QR Card -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Details Column (2 Cols) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Information Card -->
            <div class="ft-panel bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 space-y-5">
                <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider border-b border-slate-100 pb-2">Información del Certificado</h4>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                    <div>
                        <span class="text-slate-400 block font-semibold">Beneficiario</span>
                        <a href="{{ route('beneficiaries.show', $certificate->beneficiary) }}" class="text-sm font-bold text-brand-hover hover:underline block mt-0.5">
                            {{ $certificate->beneficiary->full_title_name }}
                        </a>
                        <span class="text-slate-500">{{ $certificate->beneficiary->formatted_document }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block font-semibold">Tipo de Certificación</span>
                        <span class="text-sm font-bold text-slate-800 block mt-0.5">{{ $certificate->certificateType->name }}</span>
                        <span class="text-slate-500">{{ $certificate->certificateTemplate->name ?? 'Plantilla Oficial' }}</span>
                    </div>

                    <div class="sm:col-span-2">
                        <span class="text-slate-400 block font-semibold">Actividad Académica</span>
                        <a href="{{ route('activities.show', $certificate->activity) }}" class="text-sm font-semibold text-slate-900 hover:underline block mt-0.5">
                            {{ $certificate->activity->name }}
                        </a>
                        <span class="text-slate-500">{{ $certificate->activity->formatted_date_range }} • {{ $certificate->activity->academic_hours }} horas</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block font-semibold">Fecha de Emisión</span>
                        <span class="text-sm font-semibold text-slate-800 block mt-0.5">{{ $certificate->issue_date->format('d/m/Y') }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block font-semibold">Fecha de Expiración</span>
                        <span class="text-sm font-semibold text-slate-800 block mt-0.5">{{ $certificate->expiration_date->format('d/m/Y') }}</span>
                        <span class="text-[10px] text-emerald-600 font-medium">Vigencia estándar de 3 años</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block font-semibold">Emitido Por</span>
                        <span class="text-slate-800 font-medium block mt-0.5">{{ $certificate->generatedBy->name ?? 'Personal del Vicedecanato' }}</span>
                        <span class="text-[10px] text-slate-400">{{ $certificate->created_at->format('d/m/Y H:i') }}</span>
                    </div>

                    <div>
                        <span class="text-slate-400 block font-semibold">Hash SHA-256 de Integridad</span>
                        <span class="font-mono text-[10px] text-slate-600 block mt-0.5 break-all">{{ $certificate->integrity_hash }}</span>
                    </div>
                </div>
            </div>

            <!-- Frozen Documental Snapshot Card -->
            <div class="ft-panel bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <div class="flex items-center gap-2">
                        <span class="text-base">🧊</span>
                        <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Estado Documental Congelado (Inmutabilidad)</h4>
                    </div>
                    <span class="bg-emerald-50 text-emerald-800 text-[10px] font-bold px-2.5 py-0.5 rounded-full border border-emerald-200">
                        ✓ Inalterable
                    </span>
                </div>

                <p class="text-xs text-slate-500">
                    Este certificado cuenta con un snapshot criptográfico inmutable. Cualquier edición posterior en la plantilla o en las autoridades institucionales no afectará el diseño, firmas ni redacción original de este documento emitido.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs pt-1">
                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-slate-400 block text-[11px] font-semibold">Plantilla Utilizada</span>
                        <span class="font-bold text-slate-800 block mt-0.5">
                            {{ $certificate->snapshot_data['template_name'] ?? $certificate->certificateTemplate->name ?? 'Plantilla Oficial' }}
                        </span>
                        <span class="text-[10px] text-slate-500 font-mono">
                            Versión: v{{ $certificate->snapshot_data['template_version'] ?? ($certificate->templateVersion->version_number ?? 1) }}.0
                        </span>
                    </div>

                    <div class="p-3 bg-slate-50 rounded-xl border border-slate-200">
                        <span class="text-slate-400 block text-[11px] font-semibold">Autoridad(es) Firmante(s)</span>
                        @if(!empty($certificate->snapshot_data['signatures']))
                            @foreach($certificate->snapshot_data['signatures'] as $sig)
                                <div class="mt-1 text-slate-800 font-medium">
                                    • {{ $sig['name'] ?? '' }} ({{ $sig['position'] ?? '' }})
                                </div>
                            @endforeach
                        @else
                            <span class="font-medium text-slate-800 mt-0.5 block">Ing. Aniceto Velarde Forest (Vicedecano)</span>
                        @endif
                    </div>
                </div>

                @if(!empty($certificate->custom_fields_data['nota_especial']))
                    <div class="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs">
                        <span class="text-amber-800 font-bold block">Personalización Especial de este Certificado:</span>
                        <span class="text-amber-900 mt-0.5 block">{{ $certificate->custom_fields_data['nota_especial'] }}</span>
                    </div>
                @endif
            </div>

            <!-- Verification History Log -->
            <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
                <div class="p-5 border-b border-slate-100">
                    <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider">Registro de Consultas Públicas (Escaneos QR)</h4>
                    <p class="text-xs text-slate-400 mt-0.5">Total de comprobaciones: {{ $certificate->verifications->count() }}</p>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-500 uppercase font-semibold">
                            <tr>
                                <th class="px-5 py-2.5">Fecha y Hora</th>
                                <th class="px-5 py-2.5">Método</th>
                                <th class="px-5 py-2.5">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($certificate->verifications()->latest('id')->take(5)->get() as $ver)
                                <tr>
                                    <td class="px-5 py-2.5 font-medium text-slate-800">{{ $ver->verified_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="px-5 py-2.5">{{ $ver->verification_method === 'qr_scan' ? 'Escaneo QR' : 'Código Manual' }}</td>
                                    <td class="px-5 py-2.5 font-mono text-slate-500">{{ $ver->ip_address }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-5 py-6 text-center text-slate-400">
                                        Aún no se han registrado escaneos del código QR para este certificado.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- QR Column (1 Col) -->
        <div class="space-y-6">

            <div class="ft-panel bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 text-center space-y-4">
                <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Código QR de Autenticidad</h4>

                <div class="p-4 bg-slate-50 rounded-2xl inline-block border border-slate-100 shadow-inner">
                    {!! SimpleSoftwareIO\QrCode\Facades\QrCode::size(170)->generate($certificate->verification_url) !!}
                </div>

                <div class="text-xs text-slate-500">
                    <span class="font-bold text-slate-700 block">URL de Verificación Segura</span>
                    <input type="text" readonly value="{{ $certificate->verification_url }}"
                           class="mt-1 w-full text-[11px] font-mono py-1.5 px-2 bg-slate-50 border border-slate-200 rounded-lg text-slate-600 text-center select-all">
                </div>

                <div class="pt-2">
                    <a href="{{ $certificate->verification_url }}" target="_blank"
                       class="w-full py-2.5 px-4 bg-action hover:bg-action-hover text-white text-xs font-bold rounded-xl shadow-xs transition-all inline-block">
                        Probar Verificación Pública &rarr;
                    </a>
                </div>
            </div>

        </div>

    </div>

    <!-- Revocation Modal -->
    <div x-show="revokeModal"
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/80 backdrop-blur-xs p-4"
         style="display: none;">

        <div class="ft-panel bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[calc(100dvh-2rem)] overflow-y-auto p-6 space-y-4" @click.away="revokeModal = false">
            <div class="flex items-center gap-3 text-red-600">
                <span class="text-2xl">🚫</span>
                <h3 class="text-base font-bold text-slate-900">Anular / Revocar Certificado</h3>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                Está a punto de anular el certificado <strong class="font-mono text-slate-900">{{ $certificate->certificate_code }}</strong>. Una vez revocado, la página pública de verificación mostrará inmediatamente el estado <strong>REVOCADO</strong>.
            </p>

            <form method="POST" action="{{ route('certificates.revoke', $certificate) }}" class="space-y-4">
                @csrf
                <div>
                    <label for="revocation_reason" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Motivo de la revocación <span class="text-red-500">*</span>
                    </label>
                    <textarea name="revocation_reason" id="revocation_reason" rows="3" required
                              class="w-full text-xs p-3 border border-slate-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-transparent"
                              placeholder="Indique claramente la justificación institucional..."></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="revokeModal = false" class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900">
                        Cancelar
                    </button>
                    <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md transition-all">
                        Confirmar Revocación
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
