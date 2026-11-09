<fieldset class="rounded-2xl border border-brand/15 bg-brand-soft/30 p-5 space-y-4">
    <legend class="px-2 text-sm font-bold text-brand">Condiciones de costo de la actividad</legend>
    @if($activity)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm font-semibold text-brand">{{ $activity->requires_payment ? 'Actividad con costo' : 'Actividad sin costo' }}</p>
            <span class="rounded-xl bg-white px-4 py-3 text-base font-bold text-brand">{{ $activity->formatted_cost }}</span>
        </div>
        <p class="ft-field-help">La modalidad y el monto se definieron al crear esta actividad y permanecen fijos. El alcance de carreras se gestiona en el apartado de la resolución.</p>
    @else
        <p class="text-sm text-muted">Define las condiciones establecidas para este evento. El cobro lo realiza el encargado personalmente; en la nómina confirmará quién completó el pago.</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <label class="ft-template-choice flex items-start gap-3" :class="requiresPayment === '0' ? 'is-selected' : ''">
                <input type="radio" name="requires_payment" value="0" x-model="requiresPayment" required class="mt-1 text-brand">
                <span><strong class="block text-sm text-brand">Sin costo</strong><span class="ft-field-help block">La emisión aplica los requisitos de carrera y calificación.</span></span>
            </label>
            <label class="ft-template-choice flex items-start gap-3" :class="requiresPayment === '1' ? 'is-selected' : ''">
                <input type="radio" name="requires_payment" value="1" x-model="requiresPayment" required class="mt-1 text-brand">
                <span><strong class="block text-sm text-brand">Con costo</strong><span class="ft-field-help block">La emisión también requiere confirmación manual del encargado.</span></span>
            </label>
        </div>
        @error('requires_payment')<p class="ft-field-error">{{ $message }}</p>@enderror
        <div x-show="requiresPayment === '1'" x-cloak class="max-w-sm">
            <label for="cost_amount" class="ft-field-label">Monto de la actividad en bolivianos (Bs) <span class="text-action">*</span></label>
            <input type="number" id="cost_amount" name="cost_amount" value="{{ old('cost_amount') }}" min="0.01" max="99999999.99" step="0.01" inputmode="decimal" :required="requiresPayment === '1'" :disabled="requiresPayment !== '1'" class="ft-input" placeholder="Ej. 50.00" aria-invalid="{{ $errors->has('cost_amount') ? 'true' : 'false' }}">
            @error('cost_amount')<p class="ft-field-error">{{ $message }}</p>@enderror
        </div>
        <p class="ft-field-help">Revisa esta elección antes de guardar: la modalidad y el importe quedarán fijos al crear la actividad.</p>
    @endif
</fieldset>
