@extends('layouts.app')

@section('page-title', 'Padrón de Beneficiarios')
@section('page-subtitle', 'Registro y consulta de participantes, estudiantes, docentes y expositores')

@section('content')
<div class="space-y-6">

    <!-- Actions & Search Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
        <form method="GET" action="{{ route('beneficiaries.index') }}" class="w-full min-w-0 sm:flex-1 sm:min-w-64 max-w-md">
            <div class="relative">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Buscar por nombre, apellido, CI o correo..."
                       class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
            </div>
        </form>

        <div class="flex w-full flex-col sm:w-auto sm:flex-row sm:flex-wrap sm:items-center gap-2">
            <a href="{{ route('beneficiaries.import') }}"
               class="px-4 py-2 bg-brand hover:bg-brand-deep text-white text-xs font-semibold rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                <svg class="w-4 h-4 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                <span>Importación Masiva (Excel/CSV)</span>
            </a>

            <a href="{{ route('beneficiaries.create') }}"
               class="px-4 py-2 bg-action hover:bg-action-hover text-white text-xs font-semibold rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
                <span>+ Registrar Beneficiario</span>
            </a>
        </div>
    </div>

    <!-- Beneficiaries Table -->
    <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3">Documento</th>
                        <th class="px-5 py-3">Nombre Completo</th>
                        <th class="px-5 py-3">Título / Grado</th>
                        <th class="px-5 py-3">Contacto</th>
                        <th class="px-5 py-3">Unidad / Institución</th>
                        <th class="px-5 py-3 text-center">Certificados</th>
                        <th class="px-5 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($beneficiaries as $ben)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-3 font-mono font-semibold text-slate-800">
                                {{ $ben->formatted_document }}
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('beneficiaries.show', $ben) }}" class="font-bold text-brand-hover hover:underline">
                                    {{ $ben->full_name }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $ben->academic_title ?? '-' }}
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                <div>{{ $ben->email ?? '-' }}</div>
                                <div class="text-[10px] text-slate-400">{{ $ben->phone }}</div>
                            </td>
                            <td class="px-5 py-3 text-slate-600">
                                {{ $ben->institution_or_department ?? '-' }}
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-brand/10 text-brand border border-brand/20">
                                    {{ $ben->certificates_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right space-x-1 whitespace-nowrap">
                                <a href="{{ route('beneficiaries.show', $ben) }}" class="p-1.5 text-slate-500 hover:text-blue-700 hover:bg-slate-100 rounded inline-block" title="Ver Historial y Perfil">
                                    👁️
                                </a>
                                <a href="{{ route('beneficiaries.edit', $ben) }}" class="p-1.5 text-slate-500 hover:text-amber-700 hover:bg-slate-100 rounded inline-block" title="Editar">
                                    ✏️
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-12 text-center text-slate-400">
                                No se encontraron beneficiarios registrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($beneficiaries->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $beneficiaries->links() }}
            </div>
        @endif
    </div>

</div>
@endsection
