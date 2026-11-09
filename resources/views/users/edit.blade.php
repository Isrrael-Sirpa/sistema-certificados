@extends('layouts.app')

@section('page-title', 'Editar usuario')
@section('page-subtitle', 'Actualización de datos, permisos y acceso institucional')

@section('content')
<div class="ft-form-page">
    <header class="ft-form-hero">
        <div class="min-w-0">
            <span class="ft-form-kicker">Administración de accesos</span>
            <h2 class="wrap-break-word">{{ $user->name }}</h2>
            <p>Actualiza la identidad, la unidad de trabajo y el acceso de esta cuenta.</p>
        </div>
        <a href="{{ route('users.index') }}" class="ft-form-back">← Volver a usuarios</a>
    </header>

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="ft-form-grid">
            <div>
                <section class="ft-form-section">
                    <div class="ft-section-heading">
                        <span class="ft-section-number">01</span>
                        <div><h3>Datos de la persona</h3><p>Nombre y correo que identifican la cuenta.</p></div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="name" class="ft-field-label">Nombre completo <span class="text-action">*</span></label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required autocomplete="name"
                                   class="ft-input" @error('name') aria-invalid="true" aria-describedby="edit-user-name-error" @enderror>
                            @error('name')<p id="edit-user-name-error" class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="ft-field-label">Correo institucional <span class="text-action">*</span></label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required autocomplete="email"
                                   class="ft-input" @error('email') aria-invalid="true" aria-describedby="edit-user-email-error" @enderror>
                            @error('email')<p id="edit-user-email-error" class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="ft-form-section">
                    <div class="ft-section-heading">
                        <span class="ft-section-number">02</span>
                        <div><h3>Rol y unidad de trabajo</h3><p>Define qué funciones y actividades puede gestionar la cuenta.</p></div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="role_id" class="ft-field-label">Rol del usuario <span class="text-action">*</span></label>
                            <select name="role_id" id="role_id" required class="ft-input"
                                    @error('role_id') aria-invalid="true" aria-describedby="edit-user-role-error" @enderror>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id) == $role->id)>{{ $role->display_name }}</option>
                                @endforeach
                            </select>
                            @error('role_id')<p id="edit-user-role-error" class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="organizational_unit_id" class="ft-field-label">Carrera o departamento</label>
                            <select name="organizational_unit_id" id="organizational_unit_id" class="ft-input"
                                    aria-describedby="edit-user-unit-help{{ $errors->has('organizational_unit_id') ? ' edit-user-unit-error' : '' }}"
                                    @error('organizational_unit_id') aria-invalid="true" @enderror>
                                <option value="">Sin unidad específica</option>
                                @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" @selected(old('organizational_unit_id', $user->organizational_unit_id) == $unit->id)>{{ $unit->name }} ({{ $unit->type_label }})</option>
                                @endforeach
                            </select>
                            <p id="edit-user-unit-help" class="ft-field-help">La asignación de una unidad es obligatoria para jefaturas de carrera y secretarías.</p>
                            @error('organizational_unit_id')<p id="edit-user-unit-error" class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </section>

                <section class="ft-form-section">
                    <div class="ft-section-heading">
                        <span class="ft-section-number">03</span>
                        <div><h3>Contraseña</h3><p>Completa ambos campos si deseas cambiarla.</p></div>
                    </div>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div>
                            <label for="password" class="ft-field-label">Nueva contraseña</label>
                            <input type="password" name="password" id="password" autocomplete="new-password" class="ft-input"
                                   @error('password') aria-invalid="true" aria-describedby="edit-user-password-error" @enderror>
                            @error('password')<p id="edit-user-password-error" class="ft-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="ft-field-label">Confirmar contraseña</label>
                            <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="ft-input">
                        </div>
                    </div>
                </section>
            </div>

            <aside class="ft-form-aside">
                <h3>Estado del acceso</h3>
                <p class="ft-field-help">La cuenta puede iniciar sesión mientras esté activa.</p>
                <label class="mt-4 flex min-h-12 cursor-pointer items-center gap-3 rounded-xl border border-border bg-brand-soft/50 px-3 py-2 text-sm font-semibold text-brand">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->is_active)) class="size-4 accent-brand">
                    Cuenta activa
                </label>
            </aside>
        </div>

        <div class="ft-form-actions">
            <a href="{{ route('users.index') }}" class="ft-form-secondary">Cancelar</a>
            <button type="submit" class="ft-form-primary">Guardar cambios</button>
        </div>
    </form>
</div>
@endsection
