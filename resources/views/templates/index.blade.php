@extends('layouts.app')

@section('page-title', 'Catálogo de Plantillas Institucionales')
@section('page-subtitle', 'Organiza los diseños institucionales y personaliza cada certificado en el editor visual')

@section('content')
<div class="space-y-6" x-data="{ duplicateModalOpen: false, targetTemplateId: null, targetTemplateName: '', newTemplateName: '' }" @keydown.escape.window="duplicateModalOpen = false">
    <section class="ft-panel bg-white p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center justify-between gap-5 border border-border">
        <div class="min-w-0">
            <p class="ft-eyebrow">Biblioteca de diseño</p>
            <h2 class="mt-2 text-xl font-bold text-brand">Un diseño para cada logro académico.</h2>
            <p class="mt-2 max-w-2xl text-sm text-muted">Abre una plantilla para trabajar sus textos, identidad y composición. Duplica un diseño cuando necesites una variante.</p>
        </div>
        <a href="{{ route('templates.create') }}" class="inline-flex shrink-0 items-center justify-center gap-2 min-h-11 px-5 py-3 bg-action hover:bg-action-hover text-white text-sm font-semibold rounded-xl transition-colors">
            <span aria-hidden="true">＋</span> Nueva plantilla
        </a>
    </section>

    <section class="ft-panel bg-white p-5 border border-border" aria-label="Buscar y filtrar plantillas">
        <form method="GET" action="{{ route('templates.index') }}" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 items-end gap-4">
            <div>
                <label for="template-search" class="block mb-2 text-xs font-semibold text-ink">Buscar plantilla</label>
                <input id="template-search" type="search" name="search" value="{{ request('search') }}" placeholder="Nombre o descripción" class="w-full min-w-0 px-3 py-2 text-sm border border-border rounded-xl focus:ring-2 focus:ring-brand/15">
            </div>
            <div>
                <label for="template-category" class="block mb-2 text-xs font-semibold text-ink">Categoría</label>
                <select id="template-category" name="category" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border border-border rounded-xl focus:ring-2 focus:ring-brand/15">
                    <option value="">Todas las categorías</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}" {{ request('category') === $cat ? 'selected' : '' }}>{{ ucfirst($cat) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="template-orientation" class="block mb-2 text-xs font-semibold text-ink">Orientación</label>
                <select id="template-orientation" name="orientation" onchange="this.form.submit()" class="w-full px-3 py-2 text-sm border border-border rounded-xl focus:ring-2 focus:ring-brand/15">
                    <option value="">Todos los formatos</option>
                    <option value="landscape" {{ request('orientation') === 'landscape' ? 'selected' : '' }}>A4 horizontal</option>
                    <option value="portrait" {{ request('orientation') === 'portrait' ? 'selected' : '' }}>A4 vertical</option>
                </select>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="min-h-11 px-4 py-2 bg-brand hover:bg-brand-deep text-white text-sm font-semibold rounded-xl">Aplicar filtros</button>
                @if(request()->hasAny(['search', 'category', 'orientation']))
                    <a href="{{ route('templates.index') }}" class="inline-flex min-h-11 items-center text-xs font-semibold text-muted hover:text-action">Limpiar</a>
                @endif
            </div>
        </form>
    </section>

    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm font-semibold text-brand">{{ $templates->total() }} {{ $templates->total() === 1 ? 'plantilla disponible' : 'plantillas disponibles' }}</p>
        <p class="text-xs text-muted">Vistas de diseño con datos de ejemplo</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
        @forelse($templates as $tpl)
            <article class="ft-panel bg-white border border-border overflow-hidden flex flex-col group">
                <a href="{{ route('templates.designer', $tpl) }}" class="ft-template-preview relative flex min-h-64 items-center justify-center p-6 bg-surface-muted focus-visible:outline-offset-[-4px]" data-orientation="{{ $tpl->orientation }}" aria-label="Abrir diseñador de {{ $tpl->name }}">
                    @include('templates.thumbnail', ['tpl' => $tpl])
                </a>
                <div class="p-5 flex flex-1 flex-col gap-4">
                    <div class="flex flex-wrap items-center gap-2 text-[10px] font-bold">
                        @if($tpl->is_default)
                            <span class="px-2.5 py-1 rounded-full bg-accent-soft text-[#946000] border border-accent/40">PREDETERMINADA</span>
                        @endif
                        <span class="px-2.5 py-1 rounded-full {{ $tpl->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">{{ $tpl->is_active ? 'ACTIVA' : 'INACTIVA' }}</span>
                        <span class="ml-auto text-muted font-mono">v{{ $tpl->current_version }}.0</span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-brand break-words"><a href="{{ route('templates.designer', $tpl) }}" class="hover:text-action">{{ $tpl->name }}</a></h3>
                        <p class="mt-2 text-xs leading-relaxed text-muted line-clamp-2">{{ $tpl->description ?: 'Diseño institucional listo para personalizar en el editor visual.' }}</p>
                        @if(str_contains($tpl->slug, 'industrial'))
                            <p class="mt-2 text-[11px] font-medium text-brand">Mecánica · Eléctrica · Civil</p>
                        @elseif(str_contains($tpl->slug, 'telecom'))
                            <p class="mt-2 text-[11px] font-medium text-brand">Telecomunicaciones · Electrónica</p>
                        @elseif(str_contains($tpl->slug, 'iiat'))
                            <p class="mt-2 text-[11px] font-medium text-brand">Instituto de Investigaciones (IIAT)</p>
                        @elseif(str_contains($tpl->slug, 'postgrado'))
                            <p class="mt-2 text-[11px] font-medium text-brand">Unidad de Postgrado</p>
                        @elseif(str_contains($tpl->slug, 'cpu'))
                            <p class="mt-2 text-[11px] font-medium text-brand">Admisión C.P.U. Facultativo</p>
                        @endif
                    </div>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-[11px] text-muted">
                        <span>{{ ucfirst($tpl->category) }}</span>
                        <span>A4 {{ $tpl->orientation === 'portrait' ? 'vertical' : 'horizontal' }}</span>
                        <span>{{ $tpl->certificates_count }} emitidos</span>
                    </div>
                    <div class="mt-auto grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <a href="{{ route('templates.designer', $tpl) }}" class="inline-flex min-h-11 items-center justify-center gap-2 px-3 py-2.5 rounded-xl bg-brand hover:bg-brand-deep text-white text-xs font-semibold transition-colors">Abrir diseñador <span aria-hidden="true">↗</span></a>
                        <button type="button" @click="targetTemplateId = {{ $tpl->id }}; targetTemplateName = @js($tpl->name); newTemplateName = 'Copia de ' + targetTemplateName; duplicateModalOpen = true; $nextTick(() => $refs.duplicateName.focus());" class="min-h-11 px-3 py-2.5 rounded-xl border border-border hover:bg-surface-muted text-brand text-xs font-semibold transition-colors">Duplicar plantilla</button>
                    </div>
                    <details class="border-t border-border pt-3">
                        <summary class="flex min-h-11 items-center justify-between gap-2 text-xs font-semibold text-muted cursor-pointer">Administrar plantilla <span aria-hidden="true">＋</span></summary>
                        <div class="flex flex-wrap items-center gap-2 pt-2">
                            <form method="POST" action="{{ route('templates.toggle-status', $tpl) }}">
                                @csrf
                                <button type="submit" class="min-h-11 px-3 py-2 text-xs font-medium text-ink rounded-lg hover:bg-surface-muted">{{ $tpl->is_active ? 'Desactivar' : 'Activar' }}</button>
                            </form>
                            @if(!$tpl->is_default)
                                <form method="POST" action="{{ route('templates.set-default', $tpl) }}">
                                    @csrf
                                    <button type="submit" class="min-h-11 px-3 py-2 text-xs font-semibold text-brand rounded-lg hover:bg-brand-soft">Hacer predeterminada</button>
                                </form>
                            @endif
                            @if($tpl->certificates_count == 0)
                                <form method="POST" action="{{ route('templates.destroy', $tpl) }}" onsubmit="return confirm('¿Seguro que desea eliminar esta plantilla?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="min-h-11 px-3 py-2 text-xs font-medium text-action rounded-lg hover:bg-action-soft">Eliminar</button>
                                </form>
                            @else
                                <span class="px-3 py-2 text-[11px] text-muted" title="No se puede eliminar porque tiene certificados emitidos">En uso · eliminación no disponible</span>
                            @endif
                        </div>
                    </details>
                </div>
            </article>
        @empty
            <div class="col-span-full ft-panel bg-white p-8 sm:p-12 text-center border border-border">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-brand-soft text-brand text-2xl" aria-hidden="true">▧</div>
                <h3 class="text-lg font-bold text-brand">No encontramos plantillas</h3>
                <p class="mt-2 text-sm text-muted">Prueba con otros filtros o crea un diseño para comenzar.</p>
                <a href="{{ route('templates.create') }}" class="mt-5 inline-flex min-h-11 items-center px-5 py-3 rounded-xl bg-action hover:bg-action-hover text-white text-sm font-semibold">Crear plantilla</a>
            </div>
        @endforelse
    </div>

    <div>{{ $templates->links() }}</div>

    <div x-show="duplicateModalOpen" x-cloak x-transition.opacity class="fixed inset-0 z-50 p-4 flex items-center justify-center bg-brand-deep/70 backdrop-blur-xs" role="dialog" aria-modal="true" aria-labelledby="duplicate-title" style="display: none;" @click.self="duplicateModalOpen = false">
        <div class="ft-panel w-full max-w-md max-h-[calc(100dvh-2rem)] overflow-y-auto bg-white border border-border p-6" @click.away="duplicateModalOpen = false">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="ft-eyebrow">Nueva variante</p>
                    <h3 id="duplicate-title" class="mt-2 text-xl font-bold text-brand">Duplicar plantilla</h3>
                </div>
                <button type="button" @click="duplicateModalOpen = false" class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-xl text-muted hover:bg-surface-muted" aria-label="Cerrar ventana">✕</button>
            </div>
            <form method="POST" :action="'/plantillas/' + targetTemplateId + '/duplicar'" class="mt-5 space-y-5">
                @csrf
                <p class="text-sm text-muted">Crearás una copia independiente de <strong class="text-ink" x-text="targetTemplateName"></strong>.</p>
                <div>
                    <label for="duplicate-name" class="block mb-2 text-xs font-semibold text-ink">Nombre de la nueva plantilla</label>
                    <input id="duplicate-name" x-ref="duplicateName" type="text" name="name" x-model="newTemplateName" required class="w-full px-3 py-2.5 border border-border rounded-xl focus:ring-2 focus:ring-brand/15">
                </div>
                <div class="flex flex-wrap justify-end gap-3 border-t border-border pt-4">
                    <button type="button" @click="duplicateModalOpen = false" class="min-h-11 px-4 py-2.5 text-sm font-semibold text-muted rounded-xl hover:bg-surface-muted">Cancelar</button>
                    <button type="submit" class="min-h-11 px-5 py-2.5 rounded-xl bg-action hover:bg-action-hover text-white text-sm font-semibold">Crear copia</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
