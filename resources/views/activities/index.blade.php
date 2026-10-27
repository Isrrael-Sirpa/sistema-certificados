@extends('layouts.app')

@section('page-title', 'Actividades Académicas')
@section('page-subtitle', 'Catálogo de cursos, talleres, congresos y eventos institucionales')

@section('content')
<div class="space-y-6">

    <!-- Filters and Create Button -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
        <form method="GET" action="{{ route('activities.index') }}" class="flex min-w-0 flex-wrap items-center gap-3 flex-1">
            <div class="relative w-full sm:w-auto sm:min-w-[220px]">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Buscar por nombre o lugar..."
                       class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
            </div>

            <select name="activity_type" class="py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                <option value="">Todos los Tipos</option>
                <option value="Curso" {{ request('activity_type') === 'Curso' ? 'selected' : '' }}>Curso</option>
                <option value="Taller" {{ request('activity_type') === 'Taller' ? 'selected' : '' }}>Taller</option>
                <option value="Seminario" {{ request('activity_type') === 'Seminario' ? 'selected' : '' }}>Seminario</option>
                <option value="Congreso" {{ request('activity_type') === 'Congreso' ? 'selected' : '' }}>Congreso</option>
                <option value="Capacitación" {{ request('activity_type') === 'Capacitación' ? 'selected' : '' }}>Capacitación</option>
            </select>

            <select name="status" class="py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-brand">
                <option value="">Todos los Estados</option>
                <option value="activa" {{ request('status') === 'activa' ? 'selected' : '' }}>Activas</option>
                <option value="finalizada" {{ request('status') === 'finalizada' ? 'selected' : '' }}>Finalizadas</option>
                <option value="cancelada" {{ request('status') === 'cancelada' ? 'selected' : '' }}>Canceladas</option>
            </select>

            <button type="submit" class="py-2 px-4 bg-brand text-white text-xs font-semibold rounded-xl hover:bg-brand-deep">
                Filtrar
            </button>
        </form>

        <a href="{{ route('activities.create') }}"
           class="px-4 py-2 bg-action hover:bg-action-hover text-white text-xs font-semibold rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <span>+ Nueva Actividad</span>
        </a>
    </div>

    <!-- Activities Grid Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($activities as $act)
            <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 p-5 flex flex-col justify-between hover:shadow-md transition-shadow">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-brand/10 text-brand border border-brand/20">
                            {{ $act->activity_type }}
                        </span>

                        @if($act->status === 'activa')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                ● ACTIVA
                            </span>
                        @elseif($act->status === 'finalizada')
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                FINALIZADA
                            </span>
                        @else
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800">
                                CANCELADA
                            </span>
                        @endif
                    </div>

                    <h4 class="text-sm font-bold text-brand leading-snug">
                        <a href="{{ route('activities.show', $act) }}" class="hover:text-brand-hover">
                            {{ $act->name }}
                        </a>
                    </h4>

                    <p class="text-xs text-slate-500 mt-2 line-clamp-2">
                        {{ $act->description ?? 'Sin descripción adicional.' }}
                    </p>

                    <div class="mt-4 pt-3 border-t border-slate-100 space-y-1.5 text-xs text-slate-600">
                        <div class="flex items-center gap-2">
                            <span>📅</span>
                            <span>{{ $act->formatted_date_range }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span>⏱️</span>
                            <span><strong>{{ $act->academic_hours }}</strong> horas académicas</span>
                        </div>
                        @if($act->location)
                            <div class="flex items-center gap-2 truncate">
                                <span>📍</span>
                                <span class="truncate">{{ $act->location }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="mt-5 pt-4 border-t border-slate-100 flex items-center justify-between">
                    <div class="text-[11px] text-slate-400">
                        <strong class="text-slate-800 font-bold">{{ $act->certificates_count }}</strong> certificados
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('activities.show', $act) }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-all">
                            Ver Detalle &rarr;
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-3 ft-panel bg-white rounded-2xl p-12 text-center text-slate-400 border border-slate-200">
                No se encontraron actividades registradas.
            </div>
        @endforelse
    </div>

    @if($activities->hasPages())
        <div class="p-4 ft-panel bg-white rounded-2xl shadow-xs border border-slate-200">
            {{ $activities->links() }}
        </div>
    @endif

</div>
@endsection
