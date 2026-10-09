@extends('layouts.app')

@section('page-title', 'Configuración Institucional')
@section('page-subtitle', 'Parámetros institucionales, autoridades firmantes y plantillas de diseño')

@section('content')
<div class="space-y-6">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Institutional Settings Form (2 Cols) -->
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
            <div class="p-6 bg-[#024966] text-white">
                <h3 class="text-base font-bold">Parámetros del Vicedecanato</h3>
                <p class="text-xs text-slate-300 mt-0.5">Estos datos se reflejan automáticamente en los certificados emitidos</p>
            </div>

            <form method="POST" action="{{ route('settings.update') }}" class="p-6 sm:p-8 space-y-5">
                @csrf

                @foreach($settings as $group => $items)
                    <div class="pt-2">
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4 pb-1 border-b border-slate-100">
                            Grupo: {{ ucfirst($group) }}
                        </h4>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach($items as $stg)
                                <div class="{{ in_array($stg->key, ['institution_name', 'faculty_name']) ? 'sm:col-span-2' : '' }}">
                                    <label for="{{ $stg->key }}" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                                        {{ $stg->display_name ?? $stg->key }}
                                    </label>
                                    <input type="text" name="{{ $stg->key }}" id="{{ $stg->key }}" value="{{ old($stg->key, $stg->value) }}"
                                           class="w-full py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="pt-4 flex items-center justify-end border-t border-slate-100">
                    <button type="submit" class="px-6 py-2.5 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                        Guardar Configuración Institucional
                    </button>
                </div>
            </form>
        </div>

        <!-- Templates Management Card (1 Col) -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 space-y-4">
                <h4 class="text-xs font-bold text-[#024966] uppercase tracking-wider border-b border-slate-100 pb-2">Plantillas de Certificados</h4>

                <div class="space-y-3">
                    @foreach($templates as $tpl)
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-900">{{ $tpl->name }}</span>
                                <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $tpl->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $tpl->is_active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </div>
                            <p class="text-[11px] text-slate-500">{{ $tpl->description }}</p>
                            <div class="flex items-center justify-between text-[10px] text-slate-400 pt-1 border-t border-slate-200">
                                <span>Orientación: <strong>{{ strtoupper($tpl->orientation) }}</strong></span>
                                <span class="font-mono">{{ $tpl->view_path }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    </div>

</div>
@endsection
