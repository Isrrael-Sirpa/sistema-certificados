@extends('layouts.app')

@section('page-title', 'Crear plantilla')
@section('page-subtitle', 'Un punto de partida claro para tu próximo certificado')

@section('content')
@php
    $baseChoices = collect($initialTemplates)->keyBy('slug')->map(fn ($base) => ['name' => $base['name'], 'orientation' => $base['orientation']]);
@endphp
<div class="ft-form-page" x-data="{
    creationMode: @js(old('creation_mode', 'predefined')),
    selectedBase: @js(old('base_template_slug', 'institucional-clasica')),
    selectedOrientation: @js(old('orientation', 'landscape')),
    selectedSourceId: @js((string) old('source_template_id', $existingTemplates->first()?->id ?? '')),
    templateName: @js(old('name', '')),
    bases: @js($baseChoices),
    sources: @js($existingTemplates->keyBy('id')->map->only(['name', 'orientation'])),
    submitting: false
}">
    <header class="ft-form-hero">
        <div><span class="ft-form-kicker">Biblioteca de certificados</span><h2>El próximo diseño empieza aquí.</h2><p>Elige una composición institucional, reutiliza una plantilla de tu unidad o comienza con un lienzo en blanco.</p></div>
        <a href="{{ route('templates.index') }}" class="ft-form-back">← Volver al catálogo</a>
    </header>

    <form method="POST" action="{{ route('templates.store') }}" @submit="submitting = true">
        @csrf
        <input type="hidden" name="creation_mode" :value="creationMode" value="{{ old('creation_mode', 'predefined') }}">
        <div class="ft-form-grid">
            <div>
                <section class="ft-form-section">
                    <div class="ft-section-heading"><span class="ft-section-number">01</span><div><h3>Identifica tu plantilla</h3><p>Utiliza un nombre que ayude a encontrarla al crear una actividad.</p></div></div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><label for="template-name" class="ft-field-label">Nombre <span class="text-action">*</span></label><input id="template-name" name="name" type="text" x-model="templateName" value="{{ old('name') }}" required maxlength="150" placeholder="Ej. Talleres de Química · Certificado oficial" class="ft-input" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">@error('name')<p class="ft-field-error">{{ $message }}</p>@enderror</div>
                        <div><label for="template-category" class="ft-field-label">Categoría <span class="text-action">*</span></label><select id="template-category" name="category" class="ft-input" required>@foreach($categories as $category)<option value="{{ $category }}" @selected(old('category', 'institucional') === $category)>{{ ucfirst($category) }}</option>@endforeach</select>@error('category')<p class="ft-field-error">{{ $message }}</p>@enderror</div>
                        <div class="sm:col-span-2"><label for="template-description" class="ft-field-label">Descripción <span class="text-muted font-normal">Opcional</span></label><textarea id="template-description" name="description" rows="2" maxlength="255" class="ft-input" placeholder="Indica para qué actividades se utilizará…">{{ old('description') }}</textarea>@error('description')<p class="ft-field-error">{{ $message }}</p>@enderror</div>
                        @if(auth()->user()->isAdmin())
                            <div class="sm:col-span-2"><label for="template-unit" class="ft-field-label">Disponibilidad de la plantilla</label><select id="template-unit" name="organizational_unit_id" class="ft-input"><option value="">Institucional · Compartida con todas las unidades</option>@foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('organizational_unit_id') == $unit->id)>{{ $unit->name }}</option>@endforeach</select><p class="ft-field-help">Puedes crear un diseño compartido o reservarlo para una carrera o departamento.</p></div>
                        @else
                            <div class="sm:col-span-2 ft-form-note"><strong class="text-brand">Unidad propietaria</strong><p>{{ auth()->user()->organizationalUnit?->name }}. La nueva plantilla quedará disponible para tu unidad.</p></div>
                        @endif
                    </div>
                </section>

                <section class="ft-form-section">
                    <div class="ft-section-heading"><span class="ft-section-number">02</span><div><h3>Elige cómo comenzar</h3><p>Después podrás personalizar cada elemento en el diseñador.</p></div></div>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @foreach(['predefined' => ['▧', 'Diseño institucional', 'Composición lista para personalizar'], 'blank' => ['＋', 'Lienzo en blanco', 'Crea tu propia composición'], 'duplicate' => ['⧉', 'Duplicar plantilla', 'Reutiliza un diseño disponible']] as $mode => $choice)
                            <button type="button" @click="creationMode = '{{ $mode }}'" :aria-pressed="creationMode === '{{ $mode }}'" class="ft-template-choice text-left" :class="creationMode === '{{ $mode }}' ? 'is-selected' : ''">
                                <span class="ft-section-number mb-3" aria-hidden="true">{{ $choice[0] }}</span><strong class="block text-sm text-brand">{{ $choice[1] }}</strong><span class="block ft-field-help">{{ $choice[2] }}</span>
                            </button>
                        @endforeach
                    </div>
                    @error('creation_mode')<p class="ft-field-error">{{ $message }}</p>@enderror

                    <fieldset x-show="creationMode === 'predefined'" :disabled="creationMode !== 'predefined'" class="mt-6">
                        <legend class="ft-field-label">Diseños de referencia</legend>
                        <p class="ft-field-help mb-4">Las miniaturas son ejemplos. El diseño se adaptará al formato A4 que elijas.</p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[620px] overflow-y-auto p-1">
                            @foreach($initialTemplates as $base)
                                <label class="ft-template-choice" :class="selectedBase === @js($base['slug']) ? 'is-selected' : ''">
                                    <input type="radio" name="base_template_slug" value="{{ $base['slug'] }}" x-model="selectedBase" class="sr-only" :required="creationMode === 'predefined'">
                                    <div class="ft-choice-preview">@include('templates.thumbnail', ['tpl' => (object) ['configuration' => $base['configuration'], 'orientation' => $base['orientation']]])</div>
                                    <strong class="block text-sm text-brand">{{ $base['name'] }}</strong><span class="block ft-field-help">{{ $base['description'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('base_template_slug')<p class="ft-field-error">{{ $message }}</p>@enderror
                    </fieldset>

                    <div x-show="creationMode === 'blank'" x-cloak class="ft-form-note mt-6"><strong class="text-brand">Tu espacio para diseñar</strong><p>Empieza con una hoja A4 y agrega textos, logos, firmas, franjas y el código QR desde el diseñador.</p></div>

                    <fieldset x-show="creationMode === 'duplicate'" x-cloak :disabled="creationMode !== 'duplicate'" class="mt-6">
                        <legend class="ft-field-label">Plantilla de origen</legend>
                        @if($existingTemplates->isEmpty())
                            <p class="ft-form-note">Todavía no hay plantillas disponibles para duplicar. Puedes comenzar con un diseño institucional o un lienzo en blanco.</p>
                        @else
                            <div class="grid gap-3 sm:grid-cols-2">
                                @foreach($existingTemplates as $source)
                                    <label class="ft-template-choice" :class="String(selectedSourceId) === '{{ $source->id }}' ? 'is-selected' : ''">
                                        <input type="radio" name="source_template_id" value="{{ $source->id }}" x-model="selectedSourceId" class="sr-only" :required="creationMode === 'duplicate'">
                                        <strong class="block text-sm text-brand">{{ $source->name }}</strong><span class="block ft-field-help">A4 {{ $source->orientation === 'landscape' ? 'horizontal' : 'vertical' }} · Versión {{ $source->current_version }}.0</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif
                        @error('source_template_id')<p class="ft-field-error">{{ $message }}</p>@enderror
                    </fieldset>
                </section>

                <fieldset class="ft-form-section">
                    <div class="ft-section-heading"><span class="ft-section-number">03</span><div><h3>Formato del documento</h3><p>Elige la orientación de la nueva plantilla.</p></div></div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach(['landscape' => ['Horizontal', '297 × 210 mm', 'Ideal para certificados y reconocimientos.'], 'portrait' => ['Vertical', '210 × 297 mm', 'Una composición de diploma o constancia.']] as $orientation => $format)
                            <label class="ft-template-choice flex items-center gap-4" :class="selectedOrientation === '{{ $orientation }}' ? 'is-selected' : ''">
                                <input type="radio" name="orientation" value="{{ $orientation }}" x-model="selectedOrientation" required class="text-brand">
                                <span><strong class="block text-sm text-brand">{{ $format[0] }} · A4</strong><span class="block ft-field-help">{{ $format[1] }}<br>{{ $format[2] }}</span></span>
                            </label>
                        @endforeach
                    </div>
                    @error('orientation')<p class="ft-field-error">{{ $message }}</p>@enderror
                </fieldset>
            </div>

            <aside class="ft-form-aside">
                <span class="ft-form-kicker" style="color:var(--color-action)">Tu nuevo documento</span><h3 class="mt-2">Resumen del diseño</h3>
                <dl class="mt-3">
                    <div class="ft-summary-row"><dt>Nombre</dt><dd x-text="templateName || 'Por completar'"></dd></div>
                    <div class="ft-summary-row"><dt>Punto de partida</dt><dd x-text="creationMode === 'blank' ? 'Lienzo en blanco' : (creationMode === 'duplicate' ? sources[selectedSourceId]?.name || 'Selecciona una plantilla' : bases[selectedBase]?.name || 'Selecciona un diseño')"></dd></div>
                    <div class="ft-summary-row"><dt>Formato</dt><dd x-text="selectedOrientation === 'landscape' ? 'A4 horizontal · 297 × 210 mm' : 'A4 vertical · 210 × 297 mm'"></dd></div>
                </dl>
                <p class="ft-field-help mt-5">Crear una plantilla abre el diseñador. Allí podrás ajustar textos, firmas, colores y el tamaño del QR antes de utilizarla.</p>
            </aside>
        </div>

        <footer class="ft-form-actions">
            <p class="text-xs text-muted">Se creará una plantilla nueva; el diseño de origen se conserva.</p>
            <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto">
                <a href="{{ route('templates.index') }}" class="text-sm font-semibold text-muted">Cancelar</a>
                <button type="submit" :disabled="submitting || (creationMode === 'duplicate' && !selectedSourceId)" class="ft-form-primary"><span x-text="submitting ? 'Preparando diseñador…' : 'Crear y abrir diseñador'">Crear y abrir diseñador</span><span aria-hidden="true">→</span></button>
            </div>
        </footer>
    </form>
</div>
@endsection