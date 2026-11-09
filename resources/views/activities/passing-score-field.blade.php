<div class="rounded-2xl border border-brand/15 bg-brand-soft/45 p-4 sm:p-5">
    <label for="min_passing_score" class="ft-field-label">Nota mínima de aprobación <span class="text-action" aria-hidden="true">*</span></label>
    <div class="relative max-w-xs">
        <input id="min_passing_score" name="min_passing_score" type="number" inputmode="decimal"
               min="0" max="999.99" step="0.01" placeholder="Ej. 70" value="{{ old('min_passing_score', $minimum ?? '') }}"
               :required="Boolean(currentType?.has_score)" :disabled="!currentType?.has_score" class="ft-input pr-20"
               aria-describedby="passing-score-help{{ $errors->has('min_passing_score') ? ' passing-score-error' : '' }}"
               @error('min_passing_score') aria-invalid="true" @enderror>
        <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-xs font-semibold text-muted" aria-hidden="true">puntos</span>
    </div>
    <p id="passing-score-help" class="ft-field-help">Usa la escala de la actividad. Podrá recibir certificado quien alcance o supere esta nota y cumpla los demás requisitos.</p>
    @error('min_passing_score')
        <p id="passing-score-error" class="ft-field-error">{{ $message }}</p>
    @enderror
</div>
