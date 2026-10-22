@extends('layouts.app')

@section('page-title', 'Biblioteca de Recursos Institucionales')
@section('page-subtitle', 'Gestión de logos, firmas digitalizadas, sellos oficiales y marcas de agua para certificados')

@section('content')
<div class="space-y-6" x-data="{ uploadModalOpen: false, selectedType: '{{ $type ?? 'all' }}', currentType: 'logo' }">

    <!-- Header Stats & Actions -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs">
        <div>
            <h2 class="text-lg font-bold text-[#024966] flex items-center gap-2">
                <span>🏛️</span> Biblioteca de Identidad Universitaria
            </h2>
            <p class="text-xs text-slate-500 mt-1">Recursos oficiales disponibles para su incorporación en plantillas y actividades del Vicedecanato</p>
        </div>

        <div class="flex items-center gap-3">
            <button @click="uploadModalOpen = true" 
                    class="inline-flex items-center gap-2 px-4 py-2.5 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs transition-all transform hover:-translate-y-0.5 cursor-pointer">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Nuevo Recurso Oficial</span>
            </button>
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('assets.index') }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ empty($type) ? 'bg-[#024966] text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            Todos ({{ $counts['total'] }})
        </a>
        <a href="{{ route('assets.index', ['type' => 'logo']) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $type === 'logo' ? 'bg-[#024966] text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            🛡️ Logos y Escudos ({{ $counts['logo'] }})
        </a>
        <a href="{{ route('assets.index', ['type' => 'signature']) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $type === 'signature' ? 'bg-[#024966] text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            ✍️ Firmas Digitalizadas ({{ $counts['signature'] }})
        </a>
        <a href="{{ route('assets.index', ['type' => 'stamp']) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $type === 'stamp' ? 'bg-[#024966] text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            ⭕ Sellos Oficiales ({{ $counts['stamp'] }})
        </a>
        <a href="{{ route('assets.index', ['type' => 'watermark']) }}" 
           class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all {{ $type === 'watermark' ? 'bg-[#024966] text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
            💧 Marcas de Agua ({{ $counts['watermark'] }})
        </a>
    </div>

    <!-- Assets Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($assets as $asset)
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition-all overflow-hidden flex flex-col justify-between group">
                <!-- Preview Canvas -->
                <div class="h-44 bg-gradient-to-br from-slate-50 to-slate-100/70 p-4 flex items-center justify-center relative border-b border-slate-100">
                    @if($asset->is_mandatory)
                        <span class="absolute top-3 left-3 bg-amber-500/10 text-amber-800 border border-amber-500/30 text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1">
                            <span class="text-amber-600">★</span> Obligatorio
                        </span>
                    @endif

                    <span class="absolute top-3 right-3 text-[10px] font-bold px-2 py-0.5 rounded-full {{ $asset->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-500 border border-slate-200' }}">
                        {{ $asset->is_active ? 'Activo' : 'Inactivo' }}
                    </span>

                    <img src="{{ $asset->url }}" 
                         alt="{{ $asset->name }}" 
                         class="max-h-28 max-w-full object-contain filter drop-shadow-xs transition-transform group-hover:scale-105 duration-300">
                </div>

                <!-- Info Box -->
                <div class="p-4 space-y-3 flex-1 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            @if($asset->type === 'logo') 🛡️ Logo Institucional
                            @elseif($asset->type === 'signature') ✍️ Firma de Autoridad
                            @elseif($asset->type === 'stamp') ⭕ Sello Oficial
                            @elseif($asset->type === 'watermark') 💧 Marca de Agua
                            @else 🎨 Recurso Gráfico
                            @endif
                        </div>
                        <h4 class="text-sm font-bold text-slate-900 mt-1 line-clamp-1" title="{{ $asset->name }}">
                            {{ $asset->name }}
                        </h4>

                        @if($asset->type === 'signature' && !empty($asset->metadata['authority_name']))
                            <p class="text-xs text-slate-600 font-medium mt-1">{{ $asset->metadata['authority_name'] }}</p>
                            <p class="text-[11px] text-slate-400">{{ $asset->metadata['authority_title'] ?? 'Autoridad' }}</p>
                        @elseif($asset->type === 'logo' && !empty($asset->metadata['institution']))
                            <p class="text-xs text-slate-500 mt-1">{{ $asset->metadata['institution'] }}</p>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                        <form method="POST" action="{{ route('assets.toggle-status', $asset) }}">
                            @csrf
                            <button type="submit" class="text-xs font-semibold {{ $asset->is_active ? 'text-amber-600 hover:text-amber-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                {{ $asset->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                        </form>

                        <form method="POST" action="{{ route('assets.destroy', $asset) }}" onsubmit="return confirm('¿Seguro que desea eliminar este recurso institucional?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-medium">
                                Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 text-center border border-slate-200">
                <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto text-2xl mb-3">
                    📁
                </div>
                <h3 class="text-sm font-bold text-slate-800">No se encontraron recursos registrados</h3>
                <p class="text-xs text-slate-500 mt-1">Comience subiendo logos, firmas o sellos para sus certificados universitarios.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $assets->links() }}
    </div>

    <!-- Upload Modal -->
    <div x-show="uploadModalOpen" 
         class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/60 backdrop-blur-xs p-4" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         style="display: none;">
        
        <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-lg overflow-hidden"
             @click.away="uploadModalOpen = false">
            
            <div class="bg-[#024966] text-white p-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold">Subir Recurso Institucional</h3>
                    <p class="text-xs text-slate-200 mt-0.5">Compatible con formatos vectoriales SVG y rasterizados PNG de alta fidelidad</p>
                </div>
                <button @click="uploadModalOpen = false" class="text-slate-300 hover:text-white text-xl font-bold">&times;</button>
            </div>

            <form method="POST" action="{{ route('assets.store') }}" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Tipo de Recurso <span class="text-red-500">*</span>
                    </label>
                    <select name="type" x-model="currentType" required
                            class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                        <option value="logo">🛡️ Logo / Escudo Institucional</option>
                        <option value="signature">✍️ Firma Digitalizada de Autoridad</option>
                        <option value="stamp">⭕ Sello Oficial / Seco</option>
                        <option value="watermark">💧 Marca de Agua Transparente</option>
                        <option value="background">🖼️ Fondo / Patrón Institucional</option>
                        <option value="decorative">✨ Elemento Decorativo / Marco</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Nombre Descriptivo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="name" required placeholder="Ej: Logo Carrera de Informática"
                           class="w-full py-2.5 px-3 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#024966]">
                </div>

                <!-- Signature Specific Metadata -->
                <div x-show="currentType === 'signature'" class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3">
                    <p class="text-xs font-bold text-slate-800">Datos de la Autoridad Firmante:</p>
                    <div>
                        <label class="block text-[11px] font-semibold text-slate-600 mb-1">Nombre Completo y Grado</label>
                        <input type="text" name="authority_name" placeholder="Ej: Ing. Aniceto Velarde Forest"
                               class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg bg-white">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Cargo</label>
                            <input type="text" name="authority_title" placeholder="Ej: Vicedecano"
                                   class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg bg-white">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Unidad / Facultad</label>
                            <input type="text" name="institution" placeholder="Ej: Facultad de Tecnología - UMSA"
                                   class="w-full py-2 px-3 text-xs border border-slate-300 rounded-lg bg-white">
                        </div>
                    </div>
                </div>

                <!-- File Upload -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">
                        Archivo de Imagen / Vector <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="file" accept=".png,.jpg,.jpeg,.svg,.webp" required
                           class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-[#024966] file:text-white hover:file:bg-[#01354a] cursor-pointer">
                    <p class="text-[11px] text-slate-500 mt-1.5 leading-relaxed">
                        Formatos: <strong class="text-slate-700">PNG, JPG/JPEG, SVG, WebP</strong> (Máx. 4MB).<br>
                        <span class="text-slate-400">💡 <strong>Marcas de agua y logos:</strong> Se recomienda PNG con fondo transparente o SVG para nitidez perfecta. Las imágenes JPG de alta resolución también son procesadas automáticamente para el motor PDF.</span>
                    </p>
                </div>

                <!-- Mandatory Identity Toggle -->
                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_mandatory" value="1" id="is_mandatory"
                           class="w-4 h-4 text-[#024966] border-slate-300 rounded focus:ring-[#024966]">
                    <label for="is_mandatory" class="text-xs text-slate-700 font-medium">
                        Elemento de identidad institucional obligatoria (no se podrá omitir)
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <button type="button" @click="uploadModalOpen = false"
                            class="px-4 py-2 border border-slate-300 text-slate-600 text-xs font-semibold rounded-xl hover:bg-slate-50">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-5 py-2 bg-[#BB2026] hover:bg-[#9e1a1f] text-white text-xs font-bold rounded-xl shadow-xs">
                        Guardar Recurso
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
