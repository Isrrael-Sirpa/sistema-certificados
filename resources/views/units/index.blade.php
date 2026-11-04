@extends('layouts.app')

@section('page-title', 'Carreras y Departamentos')
@section('page-subtitle', 'Administración de unidades académicas y administrativas de la Facultad de Tecnología')

@section('content')
<div class="space-y-6" x-data="{
    createModalOpen: false,
    editModalOpen: false,
    editUnit: {
        id: null,
        name: '',
        code: '',
        type: 'carrera',
        faculty_name: 'Facultad de Tecnología',
        responsible_title: '',
        responsible_name: '',
        description: ''
    },
    openEdit(unit) {
        this.editUnit = {
            id: unit.id,
            name: unit.name,
            code: unit.code,
            type: unit.type,
            faculty_name: unit.faculty_name || 'Facultad de Tecnología',
            responsible_title: unit.responsible_title || '',
            responsible_name: unit.responsible_name || '',
            description: unit.description || ''
        };
        this.editModalOpen = true;
    }
}">

    <!-- Métricas Institucionales -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
            <span class="text-xs text-slate-500 font-medium">Carreras Oficiales</span>
            <div class="text-2xl font-black text-brand mt-1">{{ $stats['total_carreras'] ?? 0 }}</div>
            <span class="text-[10px] text-slate-400">Facultad de Tecnología</span>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
            <span class="text-xs text-slate-500 font-medium">Departamentos</span>
            <div class="text-2xl font-black text-brand mt-1">{{ $stats['total_departamentos'] ?? 0 }}</div>
            <span class="text-[10px] text-slate-400">Áreas administrativas</span>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
            <span class="text-xs text-emerald-600 font-medium">Unidades Activas</span>
            <div class="text-2xl font-black text-emerald-700 mt-1">{{ $stats['total_active'] ?? 0 }}</div>
            <span class="text-[10px] text-emerald-600/80">Habilitadas para certificar</span>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
            <span class="text-xs text-amber-600 font-medium">Unidades Inactivas</span>
            <div class="text-2xl font-black text-amber-700 mt-1">{{ $stats['total_inactive'] ?? 0 }}</div>
            <span class="text-[10px] text-amber-600/80">Historial resguardado</span>
        </div>
        <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80 col-span-2 md:col-span-1">
            <span class="text-xs text-brand font-medium">Certificados Totales</span>
            <div class="text-2xl font-black text-brand mt-1">{{ number_format($stats['total_certificates'] ?? 0) }}</div>
            <span class="text-[10px] text-slate-400">En todas las unidades</span>
        </div>
    </div>

    <!-- Header Actions & Filters -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-xs border border-slate-200/80">
        <div>
            <h3 class="text-sm font-bold text-brand">Padrón de Carreras y Departamentos</h3>
            <p class="text-xs text-slate-500">Espacios con soberanía para gestionar eventos, nóminas y certificar a sus estudiantes</p>
        </div>

        <button type="button" @click="createModalOpen = true"
                class="px-4 py-2 bg-brand hover:bg-brand-deep text-white text-xs font-semibold rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            <span>+ Nueva Carrera o Departamento</span>
        </button>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="bg-white p-4 rounded-2xl shadow-xs border border-slate-200/80">
        <form method="GET" action="{{ route('carreras-departamentos.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Buscar por nombre, sigla o responsable..."
                       class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-brand/20">
            </div>

            <div>
                <select name="type" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-brand/20">
                    <option value="all">Todos los tipos</option>
                    <option value="carrera" {{ request('type') === 'carrera' ? 'selected' : '' }}>Carreras</option>
                    <option value="departamento" {{ request('type') === 'departamento' ? 'selected' : '' }}>Departamentos</option>
                    <option value="instituto" {{ request('type') === 'instituto' ? 'selected' : '' }}>Institutos</option>
                    <option value="administrativo" {{ request('type') === 'administrativo' ? 'selected' : '' }}>Unidades administrativas</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <select name="status" class="w-full text-xs px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-brand/20">
                    <option value="all">Todos los estados</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Activas</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactivas</option>
                </select>

                <button type="submit" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl transition-all">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'type', 'status']))
                    <a href="{{ route('carreras-departamentos.index') }}" class="px-2 py-2 text-slate-400 hover:text-slate-600 text-xs" title="Limpiar filtros">✕</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table of Organizational Units -->
    <div class="ft-panel bg-white rounded-2xl shadow-xs border border-slate-200/80 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3.5">Sigla / Código</th>
                        <th class="px-5 py-3.5">Nombre de la Unidad</th>
                        <th class="px-5 py-3.5">Tipo</th>
                        <th class="px-5 py-3.5">Responsable / Cargo</th>
                        <th class="px-5 py-3.5 text-center">Actividades</th>
                        <th class="px-5 py-3.5 text-center">Certificados</th>
                        <th class="px-5 py-3.5 text-center">Usuarios</th>
                        <th class="px-5 py-3.5">Estado</th>
                        <th class="px-5 py-3.5 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($units as $unit)
                        <tr class="hover:bg-slate-50/80 transition-colors {{ ! $unit->is_active ? 'bg-slate-50/40 opacity-75' : '' }}">
                            <td class="px-5 py-3.5 font-mono font-bold">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs {{ $unit->type === 'carrera' ? 'bg-brand/10 text-brand border border-brand/20' : 'bg-slate-100 text-slate-700 border border-slate-300' }}">
                                    {{ $unit->code }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="font-bold text-slate-900 text-sm">{{ $unit->name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $unit->faculty_name }}</div>
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-bold {{ $unit->type === 'carrera' ? 'bg-blue-50 text-blue-700' : 'bg-purple-50 text-purple-700' }}">
                                    {{ $unit->type_label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-slate-700">
                                @if($unit->responsible_name)
                                    <div class="font-medium">{{ $unit->responsible_name }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $unit->responsible_title ?? 'Jefe de Unidad' }}</div>
                                @else
                                    <span class="text-slate-400 italic">No asignado</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-center font-semibold text-slate-800">
                                {{ $unit->activities_count }}
                            </td>
                            <td class="px-5 py-3.5 text-center font-semibold text-brand">
                                {{ number_format($unit->certificates_count) }}
                            </td>
                            <td class="px-5 py-3.5 text-center text-slate-600">
                                {{ $unit->users_count }}
                            </td>
                            <td class="px-5 py-3.5">
                                @if($unit->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                        ● ACTIVA
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800" title="Emisiones pausadas. Certificados históricos protegidos.">
                                        PAUSADA
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right space-x-1.5 whitespace-nowrap">
                                <button type="button" @click="openEdit({{ json_encode($unit) }})"
                                        class="px-2.5 py-1 text-xs font-medium text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors cursor-pointer"
                                        title="Editar datos de la unidad">
                                    Editar
                                </button>

                                <form method="POST" action="{{ route('carreras-departamentos.toggle', $unit) }}" class="inline"
                                      onsubmit="return confirm('{{ $unit->is_active ? '¿Está seguro de desactivar esta unidad? No se podrán registrar nuevas actividades ni emitir certificados para ella, pero todos los certificados ya emitidos seguirán 100% válidos y verificables vía QR.' : '¿Desea reactivar esta unidad para reanudar la emisión de certificados?' }}')">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                            class="px-2.5 py-1 text-xs font-medium rounded-lg transition-colors cursor-pointer {{ $unit->is_active ? 'text-amber-700 bg-amber-50 hover:bg-amber-100' : 'text-emerald-700 bg-emerald-50 hover:bg-emerald-100' }}">
                                        {{ $unit->is_active ? 'Pausar' : 'Activar' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-slate-400">
                                No se encontraron carreras o departamentos con los filtros aplicados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($units->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $units->links() }}
            </div>
        @endif
    </div>

    <!-- Modal: Nueva Carrera o Departamento -->
    <div x-show="createModalOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4" @click.away="createModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-brand">Registrar Nueva Unidad Organizacional</h3>
                <button type="button" @click="createModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
            </div>

            <form method="POST" action="{{ route('carreras-departamentos.store') }}" class="space-y-4 text-xs">
                @csrf

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Nombre Oficial de la Carrera / Departamento *</label>
                        <input type="text" name="name" required placeholder="Ej: Carrera de Química Industrial"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Sigla / Código *</label>
                        <input type="text" name="code" required maxlength="50" placeholder="Ej: QUI" uppercase
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl uppercase font-mono focus:ring-2 focus:ring-brand/20">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tipo de Unidad *</label>
                        <select name="type" required class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20">
                            <option value="carrera">Carrera Oficial</option>
                            <option value="departamento">Departamento Administrativo</option>
                            <option value="instituto">Instituto</option>
                            <option value="administrativo">Unidad administrativa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Facultad Perteneciente</label>
                        <input type="text" name="faculty_name" value="Facultad de Tecnología"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl bg-slate-50">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Cargo de la Autoridad</label>
                        <input type="text" name="responsible_title" placeholder="Ej: Director de Carrera"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nombre de la Autoridad / Jefe</label>
                        <input type="text" name="responsible_name" placeholder="Ej: Ing. Juan Pérez"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Descripción / Misión</label>
                    <textarea name="description" rows="2" placeholder="Breve descripción o alcance institucional..."
                              class="w-full px-3 py-2 border border-slate-200 rounded-xl"></textarea>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_active" id="create_is_active" value="1" checked class="rounded border-slate-300 text-brand focus:ring-brand">
                    <label for="create_is_active" class="text-xs text-slate-700 font-medium">Activar inmediatamente para la emisión de certificados</label>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="createModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-brand-deep text-white font-semibold rounded-xl">Guardar Unidad</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Editar Carrera o Departamento -->
    <div x-show="editModalOpen" style="display: none;"
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-4" @click.away="editModalOpen = false">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-brand">Editar Carrera o Departamento</h3>
                <button type="button" @click="editModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg">✕</button>
            </div>

            <form method="POST" :action="'{{ url('carreras-departamentos') }}/' + editUnit.id" class="space-y-4 text-xs">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Nombre Oficial *</label>
                        <input type="text" name="name" x-model="editUnit.name" required
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl focus:ring-2 focus:ring-brand/20">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Sigla / Código *</label>
                        <input type="text" name="code" x-model="editUnit.code" required maxlength="50" uppercase
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl uppercase font-mono focus:ring-2 focus:ring-brand/20">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Tipo de Unidad *</label>
                        <select name="type" x-model="editUnit.type" required class="w-full px-3 py-2 border border-slate-200 rounded-xl">
                            <option value="carrera">Carrera Oficial</option>
                            <option value="departamento">Departamento Administrativo</option>
                            <option value="instituto">Instituto</option>
                            <option value="administrativo">Unidad administrativa</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Facultad</label>
                        <input type="text" name="faculty_name" x-model="editUnit.faculty_name"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl bg-slate-50">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Cargo de la Autoridad</label>
                        <input type="text" name="responsible_title" x-model="editUnit.responsible_title"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Nombre de la Autoridad</label>
                        <input type="text" name="responsible_name" x-model="editUnit.responsible_name"
                               class="w-full px-3 py-2 border border-slate-200 rounded-xl">
                    </div>
                </div>

                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Descripción</label>
                    <textarea name="description" x-model="editUnit.description" rows="2"
                              class="w-full px-3 py-2 border border-slate-200 rounded-xl"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="editModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl">Cancelar</button>
                    <button type="submit" class="px-4 py-2 bg-brand hover:bg-brand-deep text-white font-semibold rounded-xl">Actualizar Datos</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

