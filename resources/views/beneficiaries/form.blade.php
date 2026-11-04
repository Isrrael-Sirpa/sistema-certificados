@php
    $isEditing = $beneficiary !== null;
    $isUnitScoped = auth()->user()->isUnitScoped();
    $assignedUnit = auth()->user()->organizationalUnit;
    $selectedUnitId = $isUnitScoped ? $assignedUnit?->id : old('primary_organizational_unit_id', $beneficiary?->primary_organizational_unit_id ?? '');
    $unitChoices = $units->keyBy('id')->map->only(['name', 'code']);
    if ($isUnitScoped && $assignedUnit) {
        $unitChoices->put($assignedUnit->id, $assignedUnit->only(['name', 'code']));
    }
    $backUrl = $isEditing ? route('beneficiaries.show', $beneficiary) : route('beneficiaries.index');
@endphp
<div class="ft-form-page" x-data="{
    firstName: @js(old('first_name', $beneficiary?->first_name ?? '')),
    lastName: @js(old('last_name', $beneficiary?->last_name ?? '')),
    documentType: @js(old('document_type', $beneficiary?->document_type ?? 'CI')),
    documentNumber: @js(old('document_number', $beneficiary?->document_number ?? '')),
    unitId: @js((string) $selectedUnitId),
    units: @js($unitChoices),
    detailsOpen: @js($isEditing || $errors->hasAny(['academic_title', 'institution_or_department', 'email', 'phone', 'notes'])),
    submitting: false,
    get fullName() { return [this.firstName, this.lastName].filter(Boolean).join(' ').trim(); },
    get unitName() { return this.units[this.unitId]?.name || 'Selecciona una unidad'; }
}">
    <header class="ft-form-hero">
        <div>
            <span class="ft-form-kicker">Padrón institucional · {{ $isEditing ? 'Actualizar ficha' : 'Nuevo beneficiario' }}</span>
            <h2>{{ $isEditing ? 'Actualiza su identidad académica.' : 'Registra su identidad académica.' }}</h2>
            <p>Verifica el CI, los nombres, los apellidos y la carrera de pertenencia antes de guardar la ficha.</p>
        </div>
        <a href="{{ $backUrl }}" class="ft-form-back">← {{ $isEditing ? 'Volver a la ficha' : 'Volver al padrón' }}</a>
    </header>

    <form method="POST" action="{{ $isEditing ? route('beneficiaries.update', $beneficiary) : route('beneficiaries.store') }}" @submit="submitting = true"
          @invalid.capture="if (!detailsOpen && $event.target.closest('#beneficiary-extra-fields')) { $event.preventDefault(); detailsOpen = true; $nextTick(() => $event.target.reportValidity()); }">
        @csrf
        @if($isEditing)
            @method('PUT')
        @endif

        <div class="ft-form-grid">
            <div class="min-w-0">
                <section class="ft-form-section">
                    <div class="ft-section-heading">
                        <span class="ft-section-number">01</span>
                        <div><h3>Identificación del beneficiario</h3><p>Estos datos se utilizarán en sus certificados y en la nómina de participantes.</p></div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-3">
                        <div>
                            <label for="document_type" class="ft-field-label">Tipo de documento <span class="text-action">*</span></label>
                            <select id="document_type" name="document_type" x-model="documentType" required class="ft-input" aria-invalid="{{ $errors->has('document_type') ? 'true' : 'false' }}">
                                @foreach(['CI' => 'Cédula de identidad (CI)', 'DNI' => 'DNI', 'PASAPORTE' => 'Pasaporte', 'EXTRANJERIA' => 'Cédula de extranjería'] as $code => $label)
                                    <option value="{{ $code }}" @selected(old('document_type', $beneficiary?->document_type ?? 'CI') === $code)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('document_type')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="document_number" class="ft-field-label">CI / Número de documento <span class="text-action">*</span></label>
                            <input type="text" id="document_number" name="document_number" x-model="documentNumber" value="{{ old('document_number', $beneficiary?->document_number) }}" required maxlength="50" class="ft-input" placeholder="Ej. 8493021 LP" aria-describedby="document-help" aria-invalid="{{ $errors->has('document_number') ? 'true' : 'false' }}">
                            <p id="document-help" class="ft-field-help">Copia el número y su complemento tal como figuran en el documento.</p>
                            @error('document_number')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2 mt-6">
                        <div>
                            <label for="first_name" class="ft-field-label">Nombres completos <span class="text-action">*</span></label>
                            <input type="text" id="first_name" name="first_name" x-model="firstName" value="{{ old('first_name', $beneficiary?->first_name) }}" required maxlength="100" autocomplete="given-name" class="ft-input" placeholder="Ej. Rodrigo Antonio" aria-invalid="{{ $errors->has('first_name') ? 'true' : 'false' }}">
                            @error('first_name')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="last_name" class="ft-field-label">Apellidos completos <span class="text-action">*</span></label>
                            <input type="text" id="last_name" name="last_name" x-model="lastName" value="{{ old('last_name', $beneficiary?->last_name) }}" required maxlength="100" autocomplete="family-name" class="ft-input" placeholder="Ej. Quispe Mamani" aria-invalid="{{ $errors->has('last_name') ? 'true' : 'false' }}">
                            @error('last_name')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="ft-form-section">
                    <div class="ft-section-heading">
                        <span class="ft-section-number">02</span>
                        <div><h3>Carrera o departamento de pertenencia</h3><p>La unidad oficial permite verificar si el estudiante está habilitado para una actividad.</p></div>
                    </div>

                    <label for="primary_organizational_unit_id" class="ft-field-label">Carrera oficial o departamento · Facultad de Tecnología <span class="text-action">*</span></label>
                    @if($isUnitScoped)
                        <input type="hidden" name="primary_organizational_unit_id" value="{{ $assignedUnit?->id }}">
                        <div class="rounded-2xl border border-brand/15 bg-brand-soft/40 p-4">
                            <div class="flex flex-wrap justify-between items-center gap-3 mb-3">
                                <span class="text-xs font-semibold text-brand">Tu unidad de trabajo</span>
                                <span class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-brand">Asignación automática</span>
                            </div>
                            <input type="text" id="primary_organizational_unit_id" value="{{ $assignedUnit?->name ?? 'Unidad sin asignar' }}" readonly aria-readonly="true" aria-describedby="unit-help" class="ft-input font-semibold text-brand">
                            <p id="unit-help" class="ft-field-help mt-3">El beneficiario se registrará en {{ $assignedUnit?->name ?? 'tu unidad' }}. La carrera o departamento se toma de tu cuenta y no se puede cambiar desde este formulario.</p>
                        </div>
                    @else
                        <select id="primary_organizational_unit_id" name="primary_organizational_unit_id" x-model="unitId" required class="ft-input" aria-describedby="unit-help" aria-invalid="{{ $errors->has('primary_organizational_unit_id') ? 'true' : 'false' }}">
                            <option value="">Selecciona la carrera o departamento</option>
                            @foreach($units->groupBy(fn ($unit) => $unit->type_label) as $group => $groupUnits)
                                <optgroup label="{{ $group }}">
                                    @foreach($groupUnits as $unit)
                                        <option value="{{ $unit->id }}" @selected((string) $selectedUnitId === (string) $unit->id)>[{{ $unit->code }}] {{ $unit->name }}{{ $unit->is_active ? '' : ' · Inactiva' }}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                        <p id="unit-help" class="ft-field-help">Selecciona la pertenencia oficial del beneficiario. La administración central puede registrar estudiantes de distintas carreras y departamentos.</p>
                    @endif
                    @error('primary_organizational_unit_id')<p class="ft-field-error">{{ $message }}</p>@enderror

                    <div class="ft-form-note mt-5">
                        <strong class="block text-brand mb-1">¿La actividad admite varias carreras?</strong>
                        <p>Carga la nómina desde la actividad correspondiente. Allí se verifican las carreras habilitadas y las condiciones registradas; quienes no cumplan quedan observados y no reciben un certificado.</p>
                        <a href="{{ route('activities.index') }}" class="inline-flex min-h-11 items-center mt-2 text-brand font-semibold underline underline-offset-4">Ir a actividades →</a>
                    </div>
                </section>

                <section class="ft-form-section">
                    <button type="button" @click="detailsOpen = !detailsOpen" :aria-expanded="detailsOpen" aria-controls="beneficiary-extra-fields" class="flex w-full items-center justify-between gap-4 text-left">
                        <span class="flex gap-3 items-start"><span class="ft-section-number">03</span><span><strong class="block text-base text-brand">Datos complementarios</strong><span class="block ft-field-help">Título académico, contacto y observaciones. Opcionales.</span></span></span>
                        <span class="text-brand text-xl" aria-hidden="true" x-text="detailsOpen ? '−' : '+'">+</span>
                    </button>
                    <div id="beneficiary-extra-fields" x-show="detailsOpen" x-cloak class="mt-6 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="academic_title" class="ft-field-label">Grado o título académico</label>
                            <input type="text" id="academic_title" name="academic_title" value="{{ old('academic_title', $beneficiary?->academic_title) }}" maxlength="50" class="ft-input" placeholder="Ej. Univ., Lic., Ing.">
                            @error('academic_title')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="institution_or_department" class="ft-field-label">Institución o referencia adicional</label>
                            <input type="text" id="institution_or_department" name="institution_or_department" value="{{ old('institution_or_department', $beneficiary?->institution_or_department) }}" maxlength="150" class="ft-input" placeholder="Referencia institucional opcional">
                            <p class="ft-field-help">Esta referencia no modifica la carrera o departamento oficial.</p>
                            @error('institution_or_department')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="ft-field-label">Correo electrónico</label>
                            <input type="email" id="email" name="email" value="{{ old('email', $beneficiary?->email) }}" maxlength="150" autocomplete="email" class="ft-input" placeholder="correo@ejemplo.com" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                            @error('email')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="ft-field-label">Teléfono o celular</label>
                            <input type="tel" id="phone" name="phone" value="{{ old('phone', $beneficiary?->phone) }}" maxlength="50" autocomplete="tel" class="ft-input" placeholder="+591 …">
                            @error('phone')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="sm:col-span-2">
                            <label for="notes" class="ft-field-label">Observaciones administrativas</label>
                            <textarea id="notes" name="notes" rows="3" class="ft-input" placeholder="Información adicional para la gestión del beneficiario…">{{ old('notes', $beneficiary?->notes) }}</textarea>
                            <p class="ft-field-help">Las calificaciones se registran en la nómina de cada actividad.</p>
                            @error('notes')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>
            </div>

            <aside class="ft-form-aside" aria-live="polite">
                <span class="ft-form-kicker" style="color:var(--color-action)">Revisión de identidad</span>
                <h3 class="mt-2">Resumen de la ficha</h3>
                <dl class="mt-3">
                    <div class="ft-summary-row"><dt>Nombre completo</dt><dd x-text="fullName || 'Por completar'"></dd></div>
                    <div class="ft-summary-row"><dt>Documento</dt><dd x-text="documentNumber ? documentType + ' · ' + documentNumber : 'Por completar'"></dd></div>
                    <div class="ft-summary-row"><dt>Carrera o departamento</dt><dd x-text="unitName"></dd></div>
                </dl>
                <p class="ft-field-help mt-5">Guardar la ficha no emite un certificado. La habilitación del estudiante se verifica en cada actividad.</p>
            </aside>
        </div>

        <footer class="ft-form-actions">
            <p class="text-xs text-muted">Los campos con <span class="text-action">*</span> son obligatorios.</p>
            <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto">
                <a href="{{ $backUrl }}" class="text-sm font-semibold text-muted">Cancelar</a>
                <button type="submit" :disabled="submitting" class="ft-form-primary"><span x-text="submitting ? 'Guardando…' : @js($isEditing ? 'Guardar cambios' : 'Registrar beneficiario')">{{ $isEditing ? 'Guardar cambios' : 'Registrar beneficiario' }}</span><span aria-hidden="true">→</span></button>
            </div>
        </footer>
    </form>
</div>
