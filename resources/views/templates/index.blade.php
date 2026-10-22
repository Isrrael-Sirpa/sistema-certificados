@extends('layouts.app')

@section('page-title', 'Gestión de Plantillas de Certificados')
@section('page-subtitle', 'Diseño, versionado, duplicación y configuración de estilos institucionales del Vicedecanato')

@section('content')
<div class="space-y-6" x-data="{ duplicateModalOpen: false, targetTemplateId: null, targetTemplateName: '', newTemplateName: '' }">

    <!-- Top Action Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h2 class="text-lg font-bold text-[#024966] flex items-center gap-2">
                <span class="w-8 h-8 rounded-xl bg-sky-50 text-[#024966] flex items-center justify-center border border-sky-100">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </span>
                <span>Catálogo de Plantillas Institucionales (Facultad de Tecnología)</span>
            </h2>
            <p class="text-xs text-slate-500 mt-1">Configure diferentes diseños según las 9 carreras, cursos pre-universitarios, postgrado e institutos de investigación</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('templates.create') }}" 
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all transform hover:-translate-y-0.5 cursor-pointer">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Nueva Plantilla</span>
            </a>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200/80">
        <form method="GET" action="{{ route('templates.index') }}" class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <div class="relative min-w-[240px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por nombre o descripción..."
                       class="w-full pl-9 pr-3 py-2 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <select name="category" onchange="this.form.submit()" class="py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                <option value="">Todas las categorías</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                @endforeach
            </select>

            <select name="orientation" onchange="this.form.submit()" class="py-2 px-3 text-xs border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                <option value="">Cualquier orientación</option>
                <option value="landscape" {{ request('orientation') === 'landscape' ? 'selected' : '' }}>Horizontal (Landscape)</option>
                <option value="portrait" {{ request('orientation') === 'portrait' ? 'selected' : '' }}>Vertical (Portrait)</option>
            </select>

            @if(request()->hasAny(['search', 'category', 'orientation']))
                <a href="{{ route('templates.index') }}" class="text-xs text-red-500 font-semibold hover:underline">Limpiar filtros</a>
            @endif
        </form>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($templates as $tpl)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group">
                <!-- Preview Canvas Card Header -->
                <div class="h-44 p-4 relative flex flex-col justify-between overflow-hidden"
                     style="background: linear-gradient(135deg, {{ $tpl->configuration['primary_color'] ?? '#024966' }} 0%, #01354a 100%);">
                    
                    <!-- Decorative Certificate Mini Border -->
                    <div class="absolute inset-3 border {{ ($tpl->configuration['border_style'] ?? '') === 'ornamental-double' ? 'border-2 border-dashed' : 'border' }} rounded-lg opacity-40 pointer-events-none"
                         style="border-color: {{ $tpl->configuration['secondary_color'] ?? '#d4af37' }};"></div>

                    <!-- Watermark Ghost in Mini Preview -->
                    <div class="absolute inset-0 flex items-center justify-center opacity-10 font-serif font-black text-2xl text-white tracking-widest pointer-events-none select-none">
                        {{ $tpl->configuration['watermark_text'] ?? 'UMSA' }}
                    </div>

                    <!-- Badges Top -->
                    <div class="relative z-10 flex items-center justify-between">
                        <div class="flex items-center gap-1.5">
                            @if($tpl->is_default)
                                <span class="bg-amber-400 text-[#024966] text-[10px] font-black px-2 py-0.5 rounded-full shadow-xs flex items-center gap-1">
                                    <svg class="w-3 h-3 text-[#024966]" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <span>OFICIAL FT</span>
                                </span>
                            @endif
                            <span class="bg-white/20 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-full">
                                v{{ $tpl->current_version }}.0
                            </span>
                        </div>

                        <span class="text-[10px] font-bold px-2 py-0.5 rounded-full {{ $tpl->is_active ? 'bg-emerald-400/20 text-emerald-200 border border-emerald-400/40' : 'bg-red-400/20 text-red-200 border border-red-400/40' }}">
                            {{ $tpl->is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                    </div>

                    <!-- Mini Center Preview Content -->
                    <div class="relative z-10 text-center py-2">
                        <p class="text-[10px] uppercase tracking-widest text-amber-300 font-bold">
                            {{ $tpl->configuration['header_title'] ?? 'UNIVERSIDAD MAYOR DE SAN ANDRÉS' }}
                        </p>
                        <h4 class="text-sm font-bold text-white tracking-wide mt-1 truncate">
                            {{ $tpl->name }}
                        </h4>
                        <p class="text-[10px] text-slate-300 italic mt-0.5">
                            Orientación: {{ strtoupper($tpl->orientation) }}
                        </p>
                    </div>

                    <!-- Bottom Tag Bar -->
                    <div class="relative z-10 flex items-center justify-between text-[10px] text-slate-300">
                        <span class="bg-black/30 px-2 py-0.5 rounded">Cat: {{ ucfirst($tpl->category) }}</span>
                        <span>{{ $tpl->certificates_count }} certificados emitidos</span>
                    </div>
                </div>

                <!-- Template Details Body -->
                <div class="p-5 space-y-4 flex-1 flex flex-col justify-between">
                    <div>
                        <p class="text-xs text-slate-600 line-clamp-2">{{ $tpl->description }}</p>

                        <!-- Specialization Tag -->
                        @if(str_contains($tpl->slug, 'industrial'))
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-sky-50 text-sky-800 border border-sky-200">
                                    ⚙️ Áreas Mecánica / Eléctrica / Civil
                                </span>
                            </div>
                        @elseif(str_contains($tpl->slug, 'telecom'))
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200">
                                    📡 Telecomunicaciones / Electrónica
                                </span>
                            </div>
                        @elseif(str_contains($tpl->slug, 'iiat'))
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                    🔬 Instituto de Investigaciones (IIAT)
                                </span>
                            </div>
                        @elseif(str_contains($tpl->slug, 'postgrado'))
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-purple-50 text-purple-800 border border-purple-200">
                                    🎓 Unidad de Postgrado (UPG-FT)
                                </span>
                            </div>
                        @elseif(str_contains($tpl->slug, 'cpu'))
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                    📚 Admisión C.P.U. Facultativo
                                </span>
                            </div>
                        @endif

                        <!-- Color Palette Preview Dots -->
                        <div class="flex items-center gap-2 mt-3 pt-2 border-t border-slate-100">
                            <span class="text-[11px] text-slate-400 font-medium">Paleta:</span>
                            <div class="flex items-center gap-1.5">
                                <span class="w-4 h-4 rounded-full border border-slate-300 shadow-xs" title="Color Primario" style="background-color: {{ $tpl->configuration['primary_color'] ?? '#024966' }};"></span>
                                <span class="w-4 h-4 rounded-full border border-slate-300 shadow-xs" title="Color Secundario" style="background-color: {{ $tpl->configuration['secondary_color'] ?? '#d4af37' }};"></span>
                                <span class="w-4 h-4 rounded-full border border-slate-300 shadow-xs" title="Color Acento" style="background-color: {{ $tpl->configuration['accent_color'] ?? '#b45309' }};"></span>
                            </div>
                            <span class="text-[10px] text-slate-400 ml-auto font-mono">
                                {{ count($tpl->configuration['signatures'] ?? []) }} firma(s)
                            </span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <div class="grid grid-cols-2 gap-2">
                            <a href="{{ route('templates.designer', $tpl) }}" 
                               class="inline-flex items-center justify-center gap-1.5 py-2 px-3 bg-[#024966] hover:bg-[#01354a] text-white text-xs font-bold rounded-xl shadow-xs transition-all">
                                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                <span>Diseñador</span>
                            </a>

                            <button @click="targetTemplateId = {{ $tpl->id }}; targetTemplateName = '{{ addslashes($tpl->name) }}'; newTemplateName = 'Copia de {{ addslashes($tpl->name) }}'; duplicateModalOpen = true;"
                                    class="inline-flex items-center justify-center gap-1.5 py-2 px-3 border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl transition-all cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
                                <span>Duplicar</span>
                            </button>
                        </div>

                        <!-- Secondary Actions Row -->
                        <div class="flex items-center justify-between text-[11px] pt-1 text-slate-500">
                            <!-- Toggle Active Form -->
                            <form method="POST" action="{{ route('templates.toggle-status', $tpl) }}">
                                @csrf
                                <button type="submit" class="hover:text-slate-800 font-medium">
                                    {{ $tpl->is_active ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>

                            <!-- Set Default Form -->
                            @if(!$tpl->is_default)
                                <form method="POST" action="{{ route('templates.set-default', $tpl) }}">
                                    @csrf
                                    <button type="submit" class="text-amber-600 hover:text-amber-700 font-semibold">
                                        Hacer Predeterminada
                                    </button>
                                </form>
                            @endif

                            <!-- Delete Form (Only when 0 certs) -->
                            @if($tpl->certificates_count == 0)
                                <form method="POST" action="{{ route('templates.destroy', $tpl) }}" onsubmit="return confirm('¿Seguro que desea eliminar esta plantilla?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 font-medium">
                                        Eliminar
                                    </button>
                                </form>
                            @else
                                <span class="text-slate-400 italic text-[10px]" title="No se puede eliminar porque tiene certificados emitidos">En uso</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 text-center border border-slate-200">
                <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto text-2xl mb-3">
                    🎨
                </div>
                <h3 class="text-sm font-bold text-slate-800">No se encontraron plantillas</h3>
                <p class="text-xs text-slate-500 mt-1">Cree una nueva plantilla o utilice una de las predeterminadas institucionales.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $templates->links() }}
    </div>

    <!-- Duplicate Modal -->
    <div x-show="duplicateModalOpen" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden"
             @click.away="duplicateModalOpen = false">
            
            <div class="bg-[#024966] text-white p-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold">Duplicar Plantilla</h3>
                    <p class="text-xs text-slate-200 mt-0.5">Copia íntegra de diseño, logos, firmas y estilos</p>
                </div>
                <button @click="duplicateModalOpen = false" class="text-slate-300 hover:text-white text-xl font-bold">&times;</button>
            </div>

            <form method="POST" :action="'/plantillas/' + targetTemplateId + '/duplicar'" class="p-6 space-y-4">
                @csrf
                <p class="text-xs text-slate-600">
                    Se creará una nueva plantilla independiente a partir de: <strong x-text="targetTemplateName"></strong>
                </p>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Nombre de la Nueva Plantilla <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" x-model="newTemplateName" required
                           class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="duplicateModalOpen = false"
                            class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs">
                        Crear Duplicado
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
