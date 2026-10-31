<section x-show="leftTab === 'data'" x-cloak class="space-y-4" aria-label="Datos de muestra del certificado">
    <div>
        <h3 class="text-xs font-bold text-brand">Probar el certificado</h3>
        <p class="mt-1 text-[10px] leading-relaxed text-slate-500">Estos datos son de ejemplo. Cambiarlos no modifica participantes, notas ni políticas del sistema.</p>
    </div>
    <div class="space-y-2">
        <label for="sample-certificate-type" class="block text-[11px] font-semibold text-brand">Tipo registrado</label>
        <select id="sample-certificate-type" x-model="sampleTypeId" @change="selectSampleType()" :disabled="!certificateTypes.length" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs">
            <template x-for="type in certificateTypes" :key="type.id"><option :value="String(type.id)" x-text="type.name"></option></template>
        </select>
        <p x-show="!certificateTypes.length" class="text-[10px] text-amber-800">Activa un tipo de certificado en Configuración para probar sus políticas.</p>
        <p class="text-[10px] text-slate-500" x-show="selectedSampleType" x-text="sampleSupportsScore ? (selectedSampleType?.score_scale || 'Con calificación') + ' · mínimo ' + (selectedSampleType?.min_passing_score ?? 51) : 'Acreditación sin calificación'"></p>
    </div>
    <div class="space-y-2">
        <label for="sample-beneficiary" class="block text-[11px] font-semibold text-brand">Nombre de ejemplo</label>
        <input id="sample-beneficiary" type="text" x-model="sampleData.beneficiaryName" maxlength="180" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs">
    </div>
    <div x-show="sampleSupportsScore" class="rounded-xl border border-brand/15 bg-brand-soft p-3 space-y-3">
        <h4 class="text-[11px] font-bold text-brand">Calificación de muestra</h4>
        <div><label for="sample-score" class="block text-[10px] text-slate-600 mb-1">Puntaje (0 a 100)</label><input id="sample-score" type="number" min="0" max="100" step="0.01" x-model="sampleData.score" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs" placeholder="Sin nota"></div>
        <div><label for="sample-score-label" class="block text-[10px] text-slate-600 mb-1">Mención o texto de calificación</label><input id="sample-score-label" type="text" x-model="sampleData.scoreLabel" maxlength="255" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs" placeholder="Ej.: Aprobado con distinción"></div>
        <p class="text-[10px] font-semibold" :class="sampleIsPassing ? 'text-emerald-700' : 'text-amber-800'" x-text="sampleData.score === '' || sampleData.score === null ? 'Muestra sin puntaje numérico' : (sampleIsPassing ? 'Muestra por encima del mínimo' : 'Muestra por debajo del mínimo')"></p>
        <p class="text-[10px] text-slate-600">El mínimo real se valida al emitir desde la actividad.</p>
    </div>
    <div class="space-y-3 rounded-xl border border-slate-200 p-3">
        <h4 class="text-[11px] font-bold text-brand">Vigencia de muestra</h4>
        <div><label for="sample-issue-date" class="block text-[10px] text-slate-600 mb-1">Fecha de emisión</label><input id="sample-issue-date" type="date" x-model="sampleIssueDate" @change="updateSampleDates()" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs"></div>
        <div><label for="sample-validity" class="block text-[10px] text-slate-600 mb-1">Vigencia</label><select id="sample-validity" x-model="sampleData.validityType" @change="updateSampleDates()" class="w-full rounded-lg border border-slate-300 bg-white px-2 py-2 text-xs"><option value="years">Temporal según el tipo</option><option value="permanent">Permanente</option><option value="custom">Vencimiento específico</option></select></div>
        <div x-show="sampleData.validityType === 'custom'"><label for="sample-expiration" class="block text-[10px] text-slate-600 mb-1">Fecha de vencimiento</label><input id="sample-expiration" type="date" :min="sampleIssueDate" x-model="sampleExpirationDate" @change="updateSampleDates()" class="w-full rounded-lg border border-slate-300 px-2 py-2 text-xs"></div>
        <p class="text-[10px] text-slate-500" x-text="sampleData.validityType === 'permanent' ? 'Permanente · sin fecha de vencimiento' : (sampleData.expirationDate ? 'Vence el ' + sampleData.expirationDate : 'Selecciona el vencimiento para esta muestra')"></p>
    </div>
    <p x-show="sampleError" x-text="sampleError" class="rounded-lg bg-accent-soft p-2 text-[10px] text-amber-800" role="status"></p>
    <label class="flex items-start gap-2 rounded-xl bg-slate-50 p-3 text-[10px] text-slate-600"><input type="checkbox" x-model="showConditionalElements" class="mt-0.5 rounded text-brand">Ver todos los bloques para editar, incluidos los que no corresponden a esta muestra.</label>
    <button type="button" @click="previewPdf()" class="w-full min-h-10 rounded-xl border border-brand/20 bg-white px-3 py-2 text-xs font-semibold text-brand">Ver PDF con esta muestra ↗</button>
    <p class="text-[10px] leading-relaxed text-slate-500">El PDF utiliza el último diseño guardado y estos datos de ejemplo. Los bloques se guardan con la plantilla; los ejemplos no.</p>
</section>
