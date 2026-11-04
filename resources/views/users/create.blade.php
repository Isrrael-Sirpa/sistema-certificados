@extends('layouts.app')

@section('page-title', 'Crear usuario')
@section('page-subtitle', 'Identidad, permisos y acceso del personal institucional')

@section('content')
<div class="ft-form-page" x-data="{
    roles: @js($roles->keyBy('id')->map->only(['name', 'display_name', 'description'])),
    units: @js($units->keyBy('id')->map->only(['name', 'code'])),
    roleId: @js((string) old('role_id', '')),
    unitId: @js((string) old('organizational_unit_id', '')),
    personName: @js(old('name', '')),
    personEmail: @js(old('email', '')),
    password: '', confirmation: '', showPassword: false, submitting: false,
    get selectedRole() { return this.roles[this.roleId] || null; },
    get requiresUnit() { return ['jefe_carrera', 'secretaria'].includes(this.selectedRole?.name); }
}">
    <header class="ft-form-hero">
        <div>
            <span class="ft-form-kicker">Administración de accesos</span>
            <h2>Una cuenta para cada responsabilidad.</h2>
            <p>Registra al personal, define su rol y vincula su cuenta con la carrera o departamento que gestionará.</p>
        </div>
        <a href="{{ route('users.index') }}" class="ft-form-back">← Volver a usuarios</a>
    </header>

    <form method="POST" action="{{ route('users.store') }}" @submit="submitting = true" x-effect="$refs.confirmation?.setCustomValidity(confirmation && confirmation !== password ? 'Las contraseñas deben coincidir.' : '')">
        @csrf
        <div class="ft-form-grid">
            <div>
                <section class="ft-form-section">
                    <div class="ft-section-heading"><span class="ft-section-number">01</span><div><h3>Datos de la persona</h3><p>La información que identificará al usuario en el sistema.</p></div></div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="name" class="ft-field-label">Nombre completo <span class="text-action">*</span></label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" x-model="personName" required maxlength="255" autocomplete="name" placeholder="Ej. Lic. Marcelo Pérez" class="ft-input" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" aria-describedby="name-error">
                            @error('name')<p id="name-error" class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="ft-field-label">Correo institucional <span class="text-action">*</span></label>
                            <input id="email" name="email" type="email" value="{{ old('email') }}" x-model="personEmail" required maxlength="255" autocomplete="email" placeholder="nombre@umsa.bo" class="ft-input" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" aria-describedby="email-error">
                            @error('email')<p id="email-error" class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="ft-form-section">
                    <div class="ft-section-heading"><span class="ft-section-number">02</span><div><h3>Rol y unidad de trabajo</h3><p>Los permisos se aplican según la función y la unidad asignada.</p></div></div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="role_id" class="ft-field-label">Rol del usuario <span class="text-action">*</span></label>
                            <select name="role_id" id="role_id" x-model="roleId" required class="ft-input" aria-invalid="{{ $errors->has('role_id') ? 'true' : 'false' }}">
                                <option value="">Selecciona una función</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->display_name }}</option>
                                @endforeach
                            </select>
                            @error('role_id')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="organizational_unit_id" class="ft-field-label">Carrera o departamento <span x-show="requiresUnit" class="text-action" aria-hidden="true">*</span></label>
                            <select name="organizational_unit_id" id="organizational_unit_id" x-model="unitId" :required="requiresUnit" class="ft-input" aria-invalid="{{ $errors->has('organizational_unit_id') ? 'true' : 'false' }}">
                                <option value="" x-text="requiresUnit ? 'Selecciona la unidad que gestionará' : 'Sin unidad específica'">Selecciona una unidad</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('organizational_unit_id') == $unit->id)>{{ $unit->name }}</option>
                                @endforeach
                            </select>
                            @error('organizational_unit_id')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div class="ft-form-note mt-5" aria-live="polite">
                        <strong class="text-brand" x-text="selectedRole?.display_name || 'Elige el nivel de acceso'"></strong>
                        <p x-text="selectedRole?.description || 'La descripción del rol aparecerá aquí antes de crear la cuenta.'"></p>
                        <p x-show="requiresUnit" class="mt-2">La unidad es obligatoria. Este usuario gestionará los eventos y certificados de su propia carrera o departamento.</p>
                    </div>
                </section>

                <section class="ft-form-section">
                    <div class="ft-section-heading"><span class="ft-section-number">03</span><div><h3>Seguridad de la cuenta</h3><p>Define una contraseña de al menos ocho caracteres.</p></div></div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="password" class="ft-field-label">Contraseña <span class="text-action">*</span></label>
                            <input id="password" name="password" type="password" :type="showPassword ? 'text' : 'password'" x-model="password" required minlength="8" autocomplete="new-password" class="ft-input" placeholder="Mínimo 8 caracteres" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                            @error('password')<p class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="ft-field-label">Confirmar contraseña <span class="text-action">*</span></label>
                            <input id="password_confirmation" x-ref="confirmation" name="password_confirmation" type="password" :type="showPassword ? 'text' : 'password'" x-model="confirmation" required minlength="8" autocomplete="new-password" class="ft-input" placeholder="Repite la contraseña">
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-3 mt-4">
                        <button type="button" @click="showPassword = !showPassword" :aria-pressed="showPassword" class="text-xs font-semibold text-brand underline underline-offset-4" x-text="showPassword ? 'Ocultar contraseñas' : 'Mostrar contraseñas'">Mostrar contraseñas</button>
                        <p x-show="confirmation.length > 0" x-cloak class="text-xs" :class="password === confirmation ? 'text-emerald-700' : 'text-action'" x-text="password === confirmation ? '✓ Las contraseñas coinciden' : 'Las contraseñas todavía no coinciden'" aria-live="polite"></p>
                    </div>
                    <label class="ft-form-note mt-6 flex items-start gap-3 cursor-pointer">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') == '1') class="mt-1 rounded border-border text-brand">
                        <span><strong class="block text-ink">Habilitar acceso al crear la cuenta</strong>Si lo desmarcas, la cuenta se guardará inactiva hasta que la habilites.</span>
                    </label>
                </section>
            </div>

            <aside class="ft-form-aside">
                <span class="ft-form-kicker" style="color:var(--color-action)">Revisión rápida</span>
                <h3 class="mt-2">Antes de crear la cuenta</h3>
                <dl class="mt-3">
                    <div class="ft-summary-row"><dt>Nombre</dt><dd x-text="personName || 'Por completar'"></dd></div>
                    <div class="ft-summary-row"><dt>Correo</dt><dd x-text="personEmail || 'Por completar'"></dd></div>
                    <div class="ft-summary-row"><dt>Función</dt><dd x-text="selectedRole?.display_name || 'Sin seleccionar'"></dd></div>
                    <div class="ft-summary-row"><dt>Unidad</dt><dd x-text="units[unitId]?.name || (requiresUnit ? 'Debes asignar una unidad' : 'Sin unidad específica')"></dd></div>
                </dl>
                <p class="ft-field-help mt-5">Solo la administración central puede registrar usuarios y asignar permisos.</p>
            </aside>
        </div>
        <footer class="ft-form-actions">
            <p class="text-xs text-muted">Los campos con <span class="text-action">*</span> son obligatorios.</p>
            <div class="flex flex-wrap items-center gap-4 w-full sm:w-auto">
                <a href="{{ route('users.index') }}" class="text-sm font-semibold text-muted">Cancelar</a>
                <button type="submit" :disabled="submitting" class="ft-form-primary"><span x-text="submitting ? 'Creando cuenta…' : 'Crear cuenta'">Crear cuenta</span><span aria-hidden="true">→</span></button>
            </div>
        </footer>
    </form>
</div>
@endsection
