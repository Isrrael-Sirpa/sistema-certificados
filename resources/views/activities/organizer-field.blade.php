@php
    $user = auth()->user();
@endphp

<div>
    <h5 id="activity-organizer-label" class="ft-field-label">Unidad organizadora</h5>

    @if ($user->isAdmin() && $organizerOptions->isNotEmpty())
        @php
            $groupLabels = ['carrera' => 'Carreras', 'departamento' => 'Departamentos', 'instituto' => 'Institutos', 'administrativo' => 'Unidades administrativas'];
            $groups = $organizerOptions->groupBy('type')->sortBy(fn ($group, $type) => array_search($type, array_keys($groupLabels), true));
            $unitChoices = $organizerOptions->mapWithKeys(fn ($unit) => [$unit->id => [
                'name' => $unit->name,
                'type' => $unit->type_label.($unit->is_active ? '' : ' · Inactiva'),
                'search' => $unit->name.' '.$unit->code.' '.$unit->type_label,
            ]]);
        @endphp

        <details data-organizer-picker class="group rounded-2xl border border-border bg-surface-muted"
                 @if (!$organizerUnit || $errors->has('organizational_unit_id')) open @endif
                 x-on:invalid.capture="$el.open = true"
                 x-data="{
                     query: '',
                     choices: @js($unitChoices),
                     normalize(value) { return value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().replaceAll('_', ' ').trim(); },
                     matches(id) { return this.normalize(this.choices[id].search).includes(this.normalize(this.query)); },
                     get matchCount() { return Object.keys(this.choices).filter(id => this.matches(id)).length; }
                 }">
            <summary class="flex min-h-16 cursor-pointer list-none flex-wrap items-center justify-between gap-x-4 gap-y-2 rounded-2xl p-4 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand [&::-webkit-details-marker]:hidden">
                <span class="flex min-w-0 flex-1 basis-48 items-start gap-3">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-soft text-brand" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="size-5"><path d="M3 21h18M5 21V9l7-5 7 5v12M9 21v-5h6v5M9 11h.01M15 11h.01" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </span>
                    <span class="min-w-0 self-center">
                        <strong class="block break-words text-sm leading-snug text-brand" x-text="choices[organizerUnitId]?.name || 'Selecciona una unidad'">{{ $organizerUnit?->name ?? 'Selecciona una unidad' }}</strong>
                        <span class="mt-1 block text-xs text-muted" x-text="choices[organizerUnitId]?.type || 'Administración central'">{{ $organizerUnit ? $unitChoices[$organizerUnit->id]['type'] : 'Administración central' }}</span>
                    </span>
                </span>
                <span class="inline-flex items-center gap-2 rounded-lg border border-border bg-white px-3 py-2 text-xs font-semibold text-brand">
                    <span class="group-open:hidden" x-text="organizerUnitId ? 'Cambiar unidad' : 'Elegir unidad'">{{ $organizerUnit ? 'Cambiar unidad' : 'Elegir unidad' }}</span>
                    <span class="hidden group-open:inline">Cerrar selector</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-4 group-open:rotate-180" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </span>
            </summary>

            <fieldset class="min-w-0 space-y-3 border-t border-border p-4" aria-labelledby="activity-organizer-label" aria-describedby="activity-organizer-help @error('organizational_unit_id') activity-organizer-error @enderror">
                <div>
                    <label for="activity-organizer-search" class="sr-only">Buscar unidad por nombre o código</label>
                    <input id="activity-organizer-search" type="search" x-model="query" autocomplete="off" placeholder="Buscar por nombre o código…" class="ft-input text-sm" @keydown.enter.prevent>
                    <p class="mt-1.5 text-xs text-muted" role="status" x-text="matchCount === 1 ? '1 unidad disponible' : matchCount + ' unidades disponibles'">{{ $organizerOptions->count() }} unidades disponibles</p>
                </div>

                <div class="max-h-72 space-y-4 overflow-y-auto overscroll-contain p-1">
                    @foreach ($groups as $type => $options)
                        <div role="group" aria-labelledby="activity-organizer-group-{{ $loop->index }}" x-show="@js($options->pluck('id')->all()).some(id => matches(id))">
                            <p id="activity-organizer-group-{{ $loop->index }}" class="mb-1.5 text-xs font-semibold text-muted">{{ $groupLabels[$type] ?? 'Otras unidades' }}</p>
                            <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                                @foreach ($options as $unit)
                                    <label x-show="matches({{ $unit->id }})" class="flex min-h-11 min-w-0 cursor-pointer items-start gap-2.5 rounded-lg border border-border bg-white px-3 py-2.5 text-sm text-brand hover:border-brand/40 has-checked:border-brand has-checked:bg-brand-soft has-focus-visible:outline-2 has-focus-visible:outline-brand">
                                        <input type="radio" name="organizational_unit_id" value="{{ $unit->id }}" @checked($organizerUnit?->id === $unit->id) required
                                               class="mt-0.5 size-4 shrink-0 accent-brand" @change="organizerUnitId = $event.target.value; onOrganizerChange()">
                                        <span class="min-w-0 break-words leading-snug">
                                            {{ $unit->name }}
                                            @unless ($unit->is_active)
                                                <span class="mt-0.5 block text-xs text-muted">Inactiva · asignación actual</span>
                                            @endunless
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <p x-cloak x-show="matchCount === 0" class="rounded-lg border border-dashed border-border bg-white p-3 text-sm text-muted">No hay unidades que coincidan con la búsqueda.</p>
                </div>
            </fieldset>
        </details>
        <p id="activity-organizer-help" class="ft-field-help">Elige quién organiza la actividad. Define el alcance de estudiantes por separado.</p>
    @else
        @php
            $organizerRole = match (true) {
                $user->isAdmin() => 'Administración central',
                $user->isJefeCarrera() => 'Jefatura de unidad',
                $user->isSecretaria() => 'Secretaría de unidad',
                $user->isUnitScoped() => 'Operación de unidad',
                default => 'Operación general',
            };
        @endphp
        <div class="flex flex-wrap items-start justify-between gap-3 rounded-2xl border border-border bg-surface-muted p-4">
            <div class="flex min-w-0 flex-1 basis-48 items-start gap-3">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-brand-soft text-brand" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" class="size-5"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3m-4 4v3" stroke-linecap="round"/></svg>
                </span>
                <div class="min-w-0">
                    <p class="break-words text-sm font-semibold text-brand">{{ $organizerUnit?->name ?? 'Sin unidad organizadora' }}</p>
                    @if ($organizerUnit)
                        <p class="mt-1 text-xs text-muted">{{ $organizerUnit->type_label }} · Unidad fija</p>
                    @endif
                </div>
            </div>
            <span class="rounded-lg bg-brand-soft px-2.5 py-1 text-xs font-semibold text-brand">{{ $organizerRole }}</span>
        </div>
        <p class="ft-field-help">
            @if ($user->isAdmin())
                No hay unidades activas disponibles para asignar.
            @elseif ($user->isUnitScoped())
                Tu cuenta organiza desde esta unidad. Puedes definir qué estudiantes podrán participar en el apartado de alcance.
            @elseif ($organizerUnit)
                Esta actividad conserva la unidad registrada. Solo administración central puede cambiarla.
            @else
                Esta cuenta de operación general registra actividades sin unidad organizadora. Define el alcance de estudiantes a continuación.
            @endif
        </p>
    @endif

    @error('organizational_unit_id')
        <p id="activity-organizer-error" class="ft-field-error" role="alert">{{ $message }}</p>
    @enderror
</div>
