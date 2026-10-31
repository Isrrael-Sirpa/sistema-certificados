import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import vm from 'node:vm';

const certificateTypes = [
    { id: 21, code: 'calificacion-especial', name: 'Certificado de Calificación Especial', has_score: true, min_passing_score: 95, score_scale: 'Sobre 100 puntos', validity_mode: 'years', default_validity_years: 5 },
    { id: 22, code: 'asistencia', name: 'Certificado de Asistencia', has_score: false, validity_mode: 'years', default_validity_years: 3 },
    { id: 23, code: 'reconocimiento', name: 'Certificado de Reconocimiento', has_score: false, validity_mode: 'permanent', default_validity_years: null },
];

test('los tipos registrados aportan nombre, nota mínima y vigencia a la muestra sin cambiar la plantilla', () => {
    const { studio } = createStudio({ certificateTypes, sampleIssueDate: '2026-10-29' });
    assert.equal(studio.selectedSampleType.id, 21);
    assert.equal(studio.sampleData.certificateType, 'Certificado de Calificación Especial');
    assert.equal(studio.sampleData.score, 95);
    assert.equal(studio.sampleIsPassing, true);
    assert.equal(studio.sampleExpirationDate, '2031-10-29');
    assert.equal(studio.isDirty, false);
    assert.equal(studio.undoStack.length, 1);
    assert.equal('sampleTypeId' in studio.getDocumentState(), false);
});

test('un tipo sin nota elimina puntajes de muestra y el permanente no inventa vencimiento', () => {
    const { studio } = createStudio({ certificateTypes });
    studio.sampleData.scoreLabel = 'Distinción';
    studio.sampleTypeId = '23';
    studio.selectSampleType();
    assert.equal(studio.sampleHasScore, false);
    assert.equal(studio.sampleData.score, '');
    assert.equal(studio.sampleData.expirationDate, '');
    assert.equal(studio.interpolateText('{{certificado.puntaje}}|{{certificado.mencion}}|{{certificado.fecha_vencimiento}}'), '||');
    assert.equal(studio.isDirty, false);
});

test('la nota cero y una mención se prueban sin alterar la aprobación mínima ni el documento', () => {
    const { studio, opened } = createStudio({ certificateTypes });
    studio.sampleData.score = '0';
    studio.sampleData.scoreLabel = 'Requiere refuerzo';
    assert.equal(studio.sampleHasScore, true);
    assert.equal(studio.sampleIsPassing, false);
    assert.equal(studio.interpolateText('{{certificado.puntaje}}'), '0');
    assert.equal(studio.previewPdf(), true);
    const parameters = new URL(opened[0][0], 'http://localhost').searchParams;
    assert.equal(parameters.get('score'), '0');
    assert.equal(parameters.get('score_label'), 'Requiere refuerzo');
    assert.equal(parameters.get('certificate_type_id'), '21');
    assert.equal(studio.isDirty, false);
});

test('la muestra valida puntaje y fecha personalizada antes de abrir un PDF', () => {
    const { studio, opened } = createStudio({ certificateTypes, sampleIssueDate: '2026-10-29' });
    studio.sampleData.score = '101';
    assert.equal(studio.previewPdf(), false);
    studio.sampleData.score = '';
    studio.sampleData.validityType = 'custom';
    studio.sampleExpirationDate = '2026-10-28';
    studio.updateSampleDates();
    assert.equal(studio.previewPdf(), false);
    studio.sampleExpirationDate = '2027-01-01';
    studio.updateSampleDates();
    assert.equal(studio.previewPdf(), true);
    assert.equal(new URL(opened[0][0], 'http://localhost').searchParams.get('expiration_date'), '2027-01-01');
    assert.equal(studio.validSampleDate('2026-02-30'), false);
    assert.equal(studio.validSampleDate('2028-02-29'), true);
});

test('una calificación cualitativa sin puntaje muestra la mención sin inventar unidades', () => {
    const { studio } = createStudio({ certificateTypes });
    studio.sampleData.score = '';
    studio.sampleData.scoreLabel = 'Excelente';
    studio.addCertificateField('qualification');
    assert.equal(studio.interpolateText(studio.selectedElement.content), 'Calificación: Excelente');
    assert.equal(studio.isElementVisible(studio.selectedElement), true);
});

test('las reglas de visibilidad distinguen nota, vigencia y ocultamiento manual', () => {
    const { studio } = createStudio({ certificateTypes });
    const scored = element({ visibility_rule: 'with_score' });
    const permanent = element({ visibility_rule: 'permanent' });
    const temporal = element({ visibility_rule: 'temporary' });
    assert.equal(studio.isElementVisible(scored), true);
    assert.equal(studio.isElementVisible(temporal), true);
    assert.equal(studio.isElementVisible(permanent), false);
    studio.sampleTypeId = '23';
    studio.selectSampleType();
    assert.equal(studio.isElementVisible(scored), false);
    assert.equal(studio.isElementVisible(temporal), false);
    assert.equal(studio.isElementVisible(permanent), true);
    studio.showConditionalElements = true;
    assert.equal(studio.isElementVisible(scored), true);
    assert.equal(studio.isElementVisible(element({ visibility_rule: 'with_score', is_visible: false })), false);
});

test('insertar calificación conserva el diseño previo, evita duplicados y permite deshacer', () => {
    const { studio } = createStudio({ certificateTypes });
    const original = JSON.stringify(studio.elements[0]);
    studio.addCertificateField('qualification');
    assert.equal(studio.elements.length, 2);
    const qualification = studio.elements[1];
    assert.equal(qualification.type, 'text');
    assert.equal(qualification.visibility_rule, 'with_score');
    assert.match(qualification.content, /certificado.puntaje/);
    assert.equal(JSON.stringify(studio.elements[0]), original);
    assertInsideSheet(qualification);
    studio.addCertificateField('qualification');
    assert.equal(studio.elements.length, 2);
    studio.undo();
    assert.equal(studio.elements.length, 1);
    assert.equal(studio.isDirty, false);
});

test('la inserción reconoce variables heredadas y no mueve ni desbloquea bloques existentes', () => {
    const original = element({ content: 'Nota: {{ certificado.calificacion }}', is_locked: true });
    const { studio } = createStudio({ initialElements: [original] });
    assert.equal(studio.hasAcademicField('qualification'), true);
    studio.addCertificateField('qualification');
    assert.equal(studio.elements.length, 1);
    assert.equal(studio.selectedId, original.id);
    assert.equal(studio.elements[0].is_locked, true);
    assert.equal(studio.isDirty, false);
});

test('un lienzo ocupado muestra aviso y no superpone automáticamente un bloque académico', () => {
    const { studio } = createStudio({ initialElements: [element({ x: 0, y: 0, width: 100, height: 100 })] });
    studio.addCertificateField('qualification');
    assert.equal(studio.elements.length, 1);
    assert.match(studio.previewNotice, /espacio libre/);
    assert.equal(studio.isDirty, false);
});

test('calificación, mención y vigencia comparten el espacio libre inferior sin superponerse', () => {
    const { studio } = createStudio({ initialElements: [element({ id: 'body', x: 8, y: 0, width: 84, height: 82 }), element({ id: 'qr', type: 'qr', x: 73, y: 82, width: 20, height: 12 })] });
    for (const field of ['qualification', 'mention', 'validity']) { studio.addCertificateField(field); }
    const blocks = studio.elements.filter(item => item.data_field);
    assert.equal(blocks.length, 3);
    for (const block of blocks) {
        assertInsideSheet(block);
        for (const other of studio.elements.filter(item => item.id !== block.id)) {
            assert.equal(block.x < other.x + other.width && block.x + block.width > other.x && block.y < other.y + other.height && block.y + block.height > other.y, false);
        }
    }
});

test('el formulario guarda la regla de visibilidad y nunca guarda los ejemplos de notas', () => {
    const { studio, nodes } = createStudio({ certificateTypes });
    studio.addCertificateField('expiration');
    studio.sampleData.score = 88;
    studio.saveTemplate();
    const schema = JSON.parse(nodes.schemaJsonInput.value);
    assert.equal(schema.elements[1].visibility_rule, 'temporary');
    assert.equal(schema.elements[1].data_field, 'expiration');
    assert.match(schema.elements[1].content, /certificado.fecha_vencimiento/);
    assert.equal('sampleData' in schema, false);
    assert.equal('certificateTypes' in schema, false);
});

const controllerPath = new URL('../../resources/views/templates/studio-controller.blade.php', import.meta.url);

for (const orientation of ['landscape', 'portrait']) {
    test(`ampliar el QR a 40 mm en ${orientation} conserva su proporción y permite deshacer`, () => {
        const qr = element({ id: 'qr', type: 'qr', x: 75, y: 65, width: 12, height: 18 });
        const { studio } = createStudio({ orientation, initialElements: [qr, element()] });
        studio.selectElement('qr');
        const original = studio.serializeDocument();

        studio.resizeQr(40);

        assert.equal(studio.qrSizeMm(), 40);
        const pageWidth = orientation === 'portrait' ? 210 : 297;
        const pageHeight = orientation === 'portrait' ? 297 : 210;
        assert.ok(Math.abs(studio.selectedElement.width * pageWidth / 100 - 40) < .04);
        assert.ok(Math.abs(studio.selectedElement.height * pageHeight / 100 * .7 - 40) < .04);
        assertInsideSheet(studio.selectedElement);
        assert.equal(studio.elements[1].x, 10);
        assert.equal(studio.isDirty, true);
        studio.undo();
        assert.equal(studio.serializeDocument(), original);
        studio.redo();
        assert.equal(studio.qrSizeMm(), 40);
    });
}

test('el QR nuevo mide 32 mm y su tamaño personalizado viaja en el formulario de la plantilla', () => {
    const { studio, nodes } = createStudio();
    studio.addQrElement();
    assert.equal(studio.qrSizeMm(), 32);
    studio.resizeQr(40);

    studio.saveTemplate(false);

    const schema = JSON.parse(nodes.schemaJsonInput.value);
    const qr = schema.elements.find(item => item.type === 'qr');
    assert.equal(studio.qrSizeMm(qr), 40);
    assert.equal(qr.width, studio.selectedElement.width);
    assert.equal(qr.height, studio.selectedElement.height);
    assert.equal(nodes.createNewVersionInput.value, '0');
});

test('el control QR respeta bloqueos, ignora valores inválidos y limita el tamaño al interior de la hoja', () => {
    const { studio } = createStudio({ initialElements: [element({ id: 'qr', type: 'qr_code', x: 95, y: 95, width: 5, height: 5, is_locked: true })] });
    studio.selectElement('qr');
    const locked = studio.serializeDocument();
    studio.resizeQr(40);
    assert.equal(studio.serializeDocument(), locked);
    studio.elements[0].is_locked = false;
    const unlocked = studio.serializeDocument();
    studio.resizeQr('');
    studio.resizeQr('invalid');
    assert.equal(studio.serializeDocument(), unlocked);
    studio.resizeQr(200);
    assert.equal(studio.qrSizeMm(), 60);
    assertInsideSheet(studio.selectedElement);
    studio.resizeQr(1);
    assert.equal(studio.qrSizeMm(), 20);
    assertInsideSheet(studio.selectedElement);
});

test('las franjas se guardan como formas editables y su inserción se deshace en un paso', () => {
    const { studio } = createStudio();
    const original = studio.serializeDocument();
    studio.addShape('bottom');
    const band = studio.selectedElement;
    assert.equal(band.type, 'shape');
    assert.equal(band.y + band.height, 100);
    assert.equal(band.width, 100);
    band.styles.fill = '#950d1f';
    band.rotation = 5;
    studio.markDirty();
    const saved = studio.buildSchemaForSubmission();
    assert.equal(saved.layout_version, 2);
    assert.equal(saved.elements.at(-1).styles.fill, '#950d1f');
    assert.equal(saved.elements.at(-1).rotation, 5);
    studio.undo();
    studio.undo();
    assert.equal(studio.serializeDocument(), original);
});

test('la elipse conserva una proporción circular en ambos formatos', () => {
    for (const orientation of ['landscape', 'portrait']) {
        const { studio } = createStudio({ initialOrientation: orientation });
        studio.orientation = orientation;
        studio.addShape('ellipse');
        const shape = studio.selectedElement;
        assertInsideSheet(shape);
        assert.equal(shape.shape, 'ellipse');
        assert.ok(Math.abs(shape.width * studio.canvasWidth - shape.height * studio.canvasHeight) < .01);
    }
});

test('el ajuste de un texto largo respeta el tamaño mínimo y no modifica la plantilla', () => {
    const text = element({ content: 'María Fernanda Rodríguez Quispe Mamani', width: 20, height: 3, styles: { font_size: 28, min_font_size: 14, fit_text: true } });
    const { studio } = createStudio({ initialElements: [text] });
    const original = studio.serializeDocument();
    assert.ok(studio.fittedFontSize(studio.elements[0]) < 28);
    assert.ok(studio.fittedFontSize(studio.elements[0]) >= 14);
    assert.equal(studio.serializeDocument(), original);
    studio.elements[0].styles.fit_text = false;
    assert.equal(studio.fittedFontSize(studio.elements[0]), 28);
});

test('autoajustar usa la composición A4 y conserva los bloques bloqueados', () => {
    const fixed = element({ id: 'fixed', is_locked: true });
    const { studio } = createStudio({ initialElements: [fixed, element({ id: 'el_beneficiary_name' })], layoutPresets: { portrait: { beneficiary: [10, 42, 80, 8.5, 26, 20] } } });
    const original = studio.serializeDocument();
    studio.autoAdaptLayout('portrait');
    assert.equal(studio.elements[0].x, fixed.x);
    assert.equal(studio.elements[0].y, fixed.y);
    assert.equal(studio.elements[1].y, 42);
    assert.equal(studio.elements[1].styles.font_size, 26);
    assert.equal(studio.elements[1].styles.fit_text, true);
    studio.undo();
    assert.equal(studio.serializeDocument(), original);
});

function createTimers() {
    let now = 0;
    let sequence = 0;
    const pending = new Map();

    return {
        setTimeout(callback, delay = 0) {
            const id = ++sequence;
            pending.set(id, { callback, due: now + Number(delay) });
            return id;
        },
        clearTimeout(id) { pending.delete(id); },
        advance(milliseconds) {
            const end = now + milliseconds;
            let entry;
            while ((entry = [...pending].filter(([, timer]) => timer.due <= end).sort((a, b) => a[1].due - b[1].due)[0])) {
                const [id, timer] = entry;
                pending.delete(id);
                now = timer.due;
                timer.callback();
            }
            now = end;
        },
        get size() { return pending.size; },
    };
}

function eventTarget() {
    const listeners = new Map();
    return {
        addEventListener(name, callback) {
            if (!listeners.has(name)) { listeners.set(name, new Set()); }
            listeners.get(name).add(callback);
        },
        removeEventListener(name, callback) { listeners.get(name)?.delete(callback); },
        dispatch(name, event) {
            for (const callback of listeners.get(name) || []) { callback(event); }
        },
        get listenerCount() { return [...listeners.values()].reduce((sum, set) => sum + set.size, 0); },
    };
}

function element(attributes = {}) {
    return {
        id: 'heading', type: 'text', name: 'Título', content: 'Contenido de prueba',
        x: 10, y: 10, width: 20, height: 10, z_index: 1,
        is_visible: true, is_locked: false, is_mandatory: false, styles: {},
        ...attributes,
    };
}

function createStudio(overrides = {}, options = {}) {
    const timers = createTimers();
    const browserEvents = eventTarget();
    const documentEvents = eventTarget();
    const opened = [];
    const observers = [];
    const viewport = {
        clientWidth: options.width ?? 1000,
        clientHeight: options.height ?? 700,
        scrollLeft: 0, scrollTop: 0,
        getBoundingClientRect() { return { x: 0, y: 0, width: this.clientWidth, height: this.clientHeight }; },
    };
    const style = () => ({ setProperty(name, value) { this[name] = value; } });
    const stage = { style: style() };
    const sheet = {
        style: style(),
        getBoundingClientRect: () => ({ width: 820, height: 580, x: 0, y: 0 }),
    };
    const nodes = Object.fromEntries(['schemaJsonInput', 'createNewVersionInput', 'changeSummaryInput'].map(id => [id, { value: '' }]));
    const form = {
        submissions: 0,
        checkValidity: () => true,
        reportValidity: () => true,
        submit() { this.submissions++; },
        requestSubmit() { this.submissions++; },
        querySelector(selector) {
            if (selector.includes('create_new_version')) { return nodes.createNewVersionInput; }
            if (selector.includes('change_summary')) { return nodes.changeSummaryInput; }
            if (selector.includes('schema_json')) { return nodes.schemaJsonInput; }
            return null;
        },
    };
    nodes.studioForm = form;
    nodes.certificateCanvasSheet = sheet;
    const document = {
        ...documentEvents,
        get listenerCount() { return documentEvents.listenerCount; },
        getElementById(id) { return nodes[id] ?? null; },
        querySelector(selector) { return selector.startsWith('#') ? nodes[selector.slice(1)] : null; },
        activeElement: null,
        documentElement: { style: style() },
        body: { style: style() },
    };
    const window = {
        ...browserEvents,
        get listenerCount() { return browserEvents.listenerCount; },
        innerWidth: options.width ?? 1400,
        innerHeight: options.height ?? 900,
        confirm: () => false,
        open(...args) { opened.push(args); },
        location: { href: '', assign(value) { this.href = value; } },
        setTimeout: timers.setTimeout,
        clearTimeout: timers.clearTimeout,
        requestAnimationFrame(callback) { return timers.setTimeout(callback, 0); },
        cancelAnimationFrame: timers.clearTimeout,
    };
    class ResizeObserver {
        constructor(callback) { this.callback = callback; this.disconnected = false; observers.push(this); }
        observe(target) { this.target = target; }
        disconnect() { this.disconnected = true; }
    }
    window.ResizeObserver = ResizeObserver;
    const context = vm.createContext({
        window, document, ResizeObserver, console,
        setTimeout: timers.setTimeout,
        clearTimeout: timers.clearTimeout,
        requestAnimationFrame: window.requestAnimationFrame,
        cancelAnimationFrame: window.cancelAnimationFrame,
        getComputedStyle: () => ({ paddingLeft: '0px', paddingRight: '0px', paddingTop: '0px', paddingBottom: '0px' }),
        HTMLFormElement: { prototype: { submit() { this.submissions++; } } },
    });
    const source = readFileSync(controllerPath, 'utf8').replace(/^\s*<script[^>]*>/i, '').replace(/<\/script>\s*$/i, '');
    vm.runInContext(source, context, { filename: controllerPath.pathname });
    const studio = context.certificateStudio({
        templateId: 7, name: 'Plantilla académica', category: 'academica', description: 'Descripción inicial',
        orientation: 'landscape', initialSchema: { primary_color: '#110b51', retained_option: 'preserve' },
        initialElements: [element()], assets: {},
        previewPdfUrl: '/plantillas/7/previsualizar-pdf', backLink: '/plantillas',
        ...overrides,
    });
    studio.$refs = { canvasViewport: viewport, canvasStage: stage };
    studio.$nextTick = callback => callback();
    studio.$watch = () => () => {};
    studio.init();
    timers.advance(0);

    return { studio, timers, viewport, stage, sheet, nodes, form, window, document, opened, observers };
}

function pointer(overrides = {}) {
    const capture = [];
    const released = [];
    const target = {
        setPointerCapture(id) { capture.push(id); },
        hasPointerCapture(id) { return capture.includes(id) && !released.includes(id); },
        releasePointerCapture(id) { released.push(id); },
        closest: () => null,
    };
    return {
        pointerId: 4, pointerType: 'mouse', button: 0, isPrimary: true,
        clientX: 100, clientY: 100, target, currentTarget: target,
        preventDefault() { this.prevented = true; },
        stopPropagation() {}, capture, released,
        ...overrides,
    };
}

function assertInsideSheet(item) {
    assert.ok(item.x >= 0 && item.y >= 0, `${item.id} debe quedar dentro de la hoja`);
    assert.ok(item.width > 0 && item.height > 0, `${item.id} debe conservar dimensiones positivas`);
    assert.ok(item.x + item.width <= 100.01 && item.y + item.height <= 100.01, `${item.id} desborda la hoja`);
}

function assertNoOverlaps(items) {
    for (let i = 0; i < items.length; i++) {
        for (let j = i + 1; j < items.length; j++) {
            const a = items[i];
            const b = items[j];
            const overlaps = a.x < b.x + b.width - 0.01 && a.x + a.width > b.x + 0.01
                && a.y < b.y + b.height - 0.01 && a.y + a.height > b.y + 0.01;
            assert.equal(overlaps, false, `${a.id} se superpone con ${b.id}`);
        }
    }
}

test('undo y redo restauran elementos, esquema, orientación y metadatos del documento', () => {
    const { studio } = createStudio();
    const saved = studio.serializeDocument();
    studio.elements[0].x = 35;
    studio.schema.primary_color = '#950d1f';
    studio.orientation = 'portrait';
    studio.name = 'Plantilla revisada';
    studio.category = 'reconocimiento';
    studio.description = 'Texto corregido';
    studio.markDirty();
    studio.flushHistory();
    const edited = studio.serializeDocument();

    studio.undo();

    assert.equal(studio.serializeDocument(), saved);
    assert.equal(studio.isDirty, false);
    studio.redo();
    assert.equal(studio.serializeDocument(), edited);
    assert.equal(studio.isDirty, true);
});

test('una edición continua del inspector crea un estado sin duplicados tras 400 ms', () => {
    const { studio, timers } = createStudio();
    const initialCount = studio.undoStack.length;

    studio.elements[0].content = 'Primera escritura';
    studio.markDirty();
    timers.advance(200);
    studio.elements[0].content = 'Escritura completa';
    studio.markDirty();
    timers.advance(399);

    assert.equal(studio.undoStack.length, initialCount);
    assert.equal(studio.isDirty, true);
    timers.advance(1);
    assert.equal(studio.undoStack.length, initialCount + 1);
    studio.markDirty();
    timers.advance(400);
    studio.pushHistoryState();
    assert.equal(studio.undoStack.length, initialCount + 1);
    studio.undo();
    assert.equal(studio.elements[0].content, 'Contenido de prueba');
});

test('una edición nueva después de deshacer descarta rehacer sin perder el punto guardado', () => {
    const { studio } = createStudio();
    studio.elements[0].x = 20;
    studio.markDirty();
    studio.flushHistory();
    studio.undo();

    studio.elements[0].x = 45;
    studio.markDirty();
    studio.flushHistory();
    studio.redo();

    assert.equal(studio.elements[0].x, 45);
    assert.equal(studio.redoStack.length, 0);
    studio.undo();
    assert.equal(studio.isDirty, false);
});

test('deshacer captura primero una escritura pendiente del inspector', () => {
    const { studio } = createStudio();
    studio.name = 'Nombre todavía sin confirmar';
    studio.markDirty();

    studio.undo();

    assert.equal(studio.name, 'Plantilla académica');
    studio.redo();
    assert.equal(studio.name, 'Nombre todavía sin confirmar');
});

test('las dimensiones editadas en el inspector permanecen dentro de la hoja', () => {
    const { studio } = createStudio();
    studio.selectElement('heading');
    Object.assign(studio.selectedElement, { x: 95, y: 90, width: 50, height: 25 });

    studio.markDirty();
    studio.flushHistory();

    assert.equal(studio.selectedElement.x, 50);
    assert.equal(studio.selectedElement.y, 75);
    assertInsideSheet(studio.selectedElement);
    studio.undo();
    assert.equal(studio.selectedElement.x, 10);
    assert.equal(studio.selectedElement.width, 20);
    assert.equal(studio.isDirty, false);
});

test('los comandos de edición respetan elementos bloqueados y obligatorios', () => {
    const { studio } = createStudio({ initialElements: [element({ is_locked: true })] });
    studio.selectElement('heading');
    const original = JSON.stringify(studio.elements);

    studio.alignElementCenter('x');
    studio.bringToFront('heading');
    studio.sendToBack('heading');
    studio.changeFontSize(2);
    studio.setTextAlign('right');
    studio.insertVariable('{{certificado.codigo}}');
    studio.deleteElement('heading');

    assert.equal(JSON.stringify(studio.elements), original);
    assert.equal(studio.isDirty, false);
    studio.elements[0].is_locked = false;
    studio.elements[0].is_mandatory = true;
    studio.deleteElement('heading');
    assert.equal(studio.elements.length, 1);
});

test('el arrastre usa captura de puntero, ignora otros punteros y respeta todos los bordes', () => {
    const { studio } = createStudio();
    const event = pointer();
    const initialCount = studio.undoStack.length;
    studio.startDrag(event, 'heading');

    studio.handlePointerMove(pointer({ pointerId: 99, clientX: 5000, clientY: 5000 }));
    assert.equal(studio.elements[0].x, 10);
    studio.handlePointerMove(pointer({ clientX: 5000, clientY: 5000 }));
    studio.handlePointerUp(event);

    assert.deepEqual(event.capture, [4]);
    assert.ok(event.released.includes(4));
    assert.equal(studio.elements[0].x, 80);
    assert.equal(studio.elements[0].y, 90);
    assert.equal(studio.isDragging, false);
    assert.equal(studio.undoStack.length, initialCount + 1);
    studio.undo();
    assert.equal(studio.elements[0].x, 10);
});

test('redimensionar limita el tamaño al espacio restante y se puede deshacer', () => {
    const { studio } = createStudio({ initialElements: [element({ x: 70, y: 80 })] });
    const event = pointer();
    studio.startResize(event, 'heading');

    studio.handlePointerMove(pointer({ clientX: 5000, clientY: 5000 }));
    studio.handlePointerUp(event);

    assert.equal(studio.elements[0].width, 30);
    assert.equal(studio.elements[0].height, 20);
    assertInsideSheet(studio.elements[0]);
    studio.undo();
    assert.equal(studio.elements[0].width, 20);
    assert.equal(studio.elements[0].height, 10);
});

test('cancelar un gesto restaura su geometría y conserva los cambios disponibles para rehacer', () => {
    const { studio } = createStudio();
    studio.elements[0].x = 20;
    studio.markDirty();
    studio.flushHistory();
    studio.undo();
    const historyLength = studio.undoStack.length;
    const event = pointer();
    studio.startDrag(event, 'heading');
    studio.handlePointerMove(pointer({ clientX: 5000, clientY: 5000 }));

    studio.handlePointerCancel(pointer({ pointerId: 99 }));
    assert.equal(studio.isDragging, true);
    studio.handlePointerCancel(event);

    assert.equal(studio.elements[0].x, 10);
    assert.equal(studio.elements[0].y, 10);
    assert.equal(studio.undoStack.length, historyLength);
    assert.equal(studio.isDirty, false);
    assert.equal(studio.isDragging, false);
    assert.ok(event.released.includes(4));
    studio.redo();
    assert.equal(studio.elements[0].x, 20);
});

test('ni arrastre ni redimensionado comienzan sobre un elemento bloqueado', () => {
    const { studio } = createStudio({ initialElements: [element({ is_locked: true })] });
    const event = pointer();

    studio.startDrag(event, 'heading');
    studio.startResize(event, 'heading');
    studio.handlePointerMove(pointer({ clientX: 5000, clientY: 5000 }));
    studio.handlePointerUp(event);

    assert.deepEqual(event.capture, []);
    assert.equal(studio.elements[0].x, 10);
    assert.equal(studio.elements[0].width, 20);
});

test('seleccionar en móvil conserva visible el lienzo', () => {
    const { studio } = createStudio({}, { width: 390 });
    studio.activePanel = 'canvas';

    studio.selectElement('heading');

    assert.equal(studio.selectedId, 'heading');
    assert.equal(studio.activePanel, 'canvas');
});

test('el cambio de ancho actualiza el modo móvil sin marcar el documento como modificado', () => {
    const { studio, window } = createStudio();
    assert.equal(studio.isDesktop, true);
    window.innerWidth = 390;

    window.dispatch('resize', {});

    assert.equal(studio.isDesktop, false);
    studio.activePanel = 'inspector';
    window.innerWidth = 1280;
    window.dispatch('resize', {});
    assert.equal(studio.isDesktop, true);
    assert.equal(studio.activePanel, 'canvas');
    assert.equal(studio.isDirty, false);
});

test('el estado inicial conserva la versión actual y el alias de regreso del catálogo', () => {
    const { studio } = createStudio({ currentVersion: 5, backUrl: '/catalogo-plantillas' });

    assert.equal(studio.currentVersion, 5);
    assert.equal(studio.backUrl, '/catalogo-plantillas');
    assert.equal(studio.backLink, '/catalogo-plantillas');
});

test('el zoom respeta 20–200 y ajustar calcula una escala que cabe en el viewport', () => {
    const { studio, viewport } = createStudio({}, { width: 300, height: 400 });

    studio.setZoom(5);
    assert.equal(studio.zoom, 20);
    studio.setZoom(999);
    assert.equal(studio.zoom, 200);
    studio.fitCanvas();

    assert.ok(studio.zoom >= 20 && studio.zoom <= 200);
    assert.ok(820 * studio.zoom / 100 <= viewport.clientWidth);
    assert.ok(580 * studio.zoom / 100 <= viewport.clientHeight);
    studio.orientation = 'portrait';
    studio.fitCanvas();
    assert.ok(580 * studio.zoom / 100 <= viewport.clientWidth);
    assert.ok(820 * studio.zoom / 100 <= viewport.clientHeight);
});

test('la resolución de recursos conserva imágenes al cambiar el filtro de biblioteca', () => {
    const { studio } = createStudio({ assets: { logos: [{ id: 17, category: 'logo', url: '/storage/escudo.svg' }], signatures: [] } });
    studio.libFilter = 'signature';

    const url = studio.resolveAssetUrl({ asset_id: '17' });

    assert.equal(url, '/storage/escudo.svg');
});

test('la muestra resuelve puntaje, mención y vencimiento sin alterar el documento', () => {
    const { studio } = createStudio();
    studio.sampleData.score = '85';
    studio.sampleData.scoreLabel = 'Excelente';
    studio.sampleData.validityType = 'years';
    studio.sampleData.expirationDate = '20 de septiembre de 2029';

    const text = studio.interpolateText('{{certificado.puntaje}} | {{certificado.calificacion}} | {{certificado.mencion}} | {{certificado.vigencia_corta}}');

    assert.equal(text, '85 | Excelente | Excelente | Hasta 20 de septiembre de 2029');
    assert.equal(studio.isDirty, false);
});

test('la muestra permanente no inventa vencimiento ni nota y conserva variables desconocidas', () => {
    const { studio } = createStudio();
    studio.sampleProfile = 'speaker';
    studio.updateSampleProfile();

    const text = studio.interpolateText('{{certificado.fecha_vencimiento}}|{{certificado.puntaje}}|{{certificado.vigencia_corta}}|{{campos.personalizado}}');

    assert.equal(text, '||PERMANENTE|{{campos.personalizado}}');
    assert.equal(studio.isDirty, false);
});

test('la muestra conserva una calificación numérica igual a cero', () => {
    const { studio } = createStudio();
    studio.sampleData.score = 0;
    studio.sampleData.scoreLabel = '';

    const text = studio.interpolateText('{{certificado.puntaje}}|{{certificado.calificacion}}');

    assert.equal(text, '0|0 pts');
});

test('la opacidad inicial coincide con el inspector y seleccionar no crea cambios pendientes', () => {
    const { studio, timers } = createStudio({
        initialElements: [element(), element({ id: 'transparent', opacity: 0 }), element({ id: 'null-opacity', opacity: null })],
    });
    assert.equal(studio.elements[0].opacity, 1);
    assert.equal(studio.elements[1].opacity, 0);
    assert.equal(studio.elements[2].opacity, 1);
    assert.equal(studio.createElement('text').opacity, 1);

    studio.selectElement('heading');
    studio.markDirty();
    timers.advance(400);

    assert.equal(studio.selectedElement.opacity, 1);
    assert.equal(studio.isDirty, false);
    assert.equal(studio.undoStack.length, 1);
});

test('el inspector recibe los estilos tipográficos heredados sin sobrescribir los estilos canónicos', () => {
    const { studio } = createStudio({
        initialElements: [element({
            font_family: 'Times-Roman', line_height: 1.8, letter_spacing: 0.7, text_transform: 'uppercase',
            styles: { line_height: 1.3 },
        })],
    });

    assert.equal(studio.elements[0].styles.font_family, 'Times-Roman');
    assert.equal(studio.elements[0].styles.line_height, 1.3);
    assert.equal(studio.elements[0].styles.letter_spacing, 0.7);
    assert.equal(studio.elements[0].styles.text_transform, 'uppercase');
    assert.equal(studio.isDirty, false);
});

test('guardar serializa el esquema completo una sola vez sin crear una versión nueva', () => {
    const { studio, form, nodes } = createStudio();
    studio.elements[0].content = 'Texto definitivo';
    studio.markDirty();
    studio.versionChangeSummary = 'No debe enviarse en un guardado normal';

    studio.saveTemplate(false);
    studio.saveTemplate(false);

    assert.equal(form.submissions, 1);
    assert.equal(studio.isSaving, true);
    assert.equal(nodes.createNewVersionInput.value, '0');
    assert.equal(nodes.changeSummaryInput.value, '');
    const schema = JSON.parse(nodes.schemaJsonInput.value);
    assert.equal(schema.retained_option, 'preserve');
    assert.equal(schema.elements[0].content, 'Texto definitivo');
});

test('guardar conserva estilos canónicos y actualiza la hoja sin modificar el modelo del editor', () => {
    const { studio, nodes } = createStudio({
        orientation: 'portrait',
        initialSchema: {
            page: { orientation: 'landscape', width_mm: 297, height_mm: 210, bleed_mm: 3, print_note: 'Conservar' },
        },
        initialElements: [
            element({
                font_size: 44, font_weight: 'normal', font_style: 'italic', text_align: 'left',
                text_color: '#aabbcc', font_family: 'Times-Roman', line_height: 2, letter_spacing: 3, text_transform: 'uppercase',
                styles: {
                    font_size: 11.5, font_weight: 'bold', font_style: 'normal', text_align: 'center',
                    text_color: '#110b51', font_family: 'Helvetica', line_height: 1.4, letter_spacing: 1.2, text_transform: 'none',
                },
            }),
            element({ id: 'photo', type: 'image', object_fit: 'contain', styles: { object_fit: 'cover' } }),
        ],
    });
    const before = studio.serializeDocument();

    studio.saveTemplate(false);

    const schema = JSON.parse(nodes.schemaJsonInput.value);
    const text = schema.elements[0];
    assert.equal(text.styles.font_size, 11.5);
    assert.equal(text.styles.line_height, 1.4);
    assert.equal(text.styles.letter_spacing, 1.2);
    assert.equal(text.styles.text_transform, 'none');
    for (const alias of ['font_size', 'font_weight', 'font_style', 'text_align', 'text_color', 'font_family', 'line_height', 'letter_spacing', 'text_transform']) {
        assert.equal(Object.hasOwn(text, alias), false, `el alias ${alias} no debe sobrescribir styles`);
    }
    assert.equal(schema.elements[1].styles.object_fit, 'cover');
    assert.equal(Object.hasOwn(schema.elements[1], 'object_fit'), false);
    assert.deepEqual(schema.page, { orientation: 'portrait', width_mm: 210, height_mm: 297, bleed_mm: 3, print_note: 'Conservar' });
    assert.equal(studio.serializeDocument(), before);
    assert.equal(studio.elements[0].font_size, 44);
    assert.equal(studio.elements[1].object_fit, 'contain');
});

test('guardar conserva los aliases heredados cuando no existe su equivalente en styles', () => {
    const { studio, nodes } = createStudio({
        initialElements: [
            element({ font_family: 'Times-Roman', font_size: 18 }),
            element({ id: 'photo', type: 'image', object_fit: 'contain' }),
        ],
    });
    delete studio.elements[0].styles.font_size;
    delete studio.elements[0].styles.font_family;

    studio.saveTemplate(false);

    const schema = JSON.parse(nodes.schemaJsonInput.value);
    assert.equal(schema.elements[0].font_family, 'Times-Roman');
    assert.equal(schema.elements[0].font_size, 18);
    assert.equal(Object.hasOwn(schema.elements[0].styles, 'font_size'), false);
    assert.equal(schema.elements[1].object_fit, 'contain');
    assert.equal(Object.hasOwn(schema.elements[1].styles, 'object_fit'), false);
});

test('confirmar una nueva versión envía el resumen por el formulario existente', () => {
    const { studio, form, nodes } = createStudio();
    studio.showVersionDialog = true;
    studio.versionChangeSummary = 'Distribución de firmas revisada';

    studio.saveTemplate(true);

    assert.equal(form.submissions, 1);
    assert.equal(nodes.createNewVersionInput.value, '1');
    assert.equal(nodes.changeSummaryInput.value, 'Distribución de firmas revisada');
});

test('el modal de versión mantiene Tab y Shift Tab entre controles visibles y habilitados', () => {
    const { studio, document } = createStudio();
    const control = attributes => ({
        disabled: false, tabIndex: 0, getClientRects: () => [{}],
        focus() { document.activeElement = this; }, ...attributes,
    });
    const first = control({});
    const last = control({});
    const controls = [first, control({ disabled: true }), control({ getClientRects: () => [] }), last];
    const dialog = {
        querySelectorAll: () => controls,
        contains: value => controls.includes(value),
        focus() { document.activeElement = this; },
    };
    studio.$refs.versionDialog = dialog;
    studio.showVersionDialog = true;
    const tab = shiftKey => ({ key: 'Tab', shiftKey, preventDefault() { this.prevented = true; } });
    document.activeElement = last;
    const forward = tab(false);

    studio.trapVersionFocus(forward);

    assert.equal(forward.prevented, true);
    assert.equal(document.activeElement, first);
    const backward = tab(true);
    studio.trapVersionFocus(backward);
    assert.equal(backward.prevented, true);
    assert.equal(document.activeElement, last);
    document.activeElement = null;
    studio.trapVersionFocus(tab(false));
    assert.equal(document.activeElement, first);
});

test('un modal sin controles disponibles mantiene el foco en el propio diálogo', () => {
    const { studio, document } = createStudio();
    const dialog = { querySelectorAll: () => [], focus() { document.activeElement = this; } };
    studio.$refs.versionDialog = dialog;
    studio.showVersionDialog = true;
    const event = { key: 'Tab', preventDefault() { this.prevented = true; } };

    studio.trapVersionFocus(event);

    assert.equal(event.prevented, true);
    assert.equal(document.activeElement, dialog);
});

test('un formulario inválido no se envía ni deja el guardado bloqueado', () => {
    const { studio, form } = createStudio();
    studio.name = '';
    studio.markDirty();

    const result = studio.saveTemplate(false);

    assert.equal(result, false);
    assert.equal(form.submissions, 0);
    assert.equal(studio.isSaving, false);
    assert.equal(studio.isDirty, true);
    assert.ok(studio.previewNotice);
});

test('el PDF solo se abre para el documento guardado y avisa si hay cambios', () => {
    const { studio, opened } = createStudio();
    studio.name = 'Nombre modificado';
    studio.markDirty();

    studio.previewPdf();

    assert.equal(opened.length, 0);
    assert.ok(studio.previewNotice);
    studio.undo();
    studio.previewPdf();
    assert.equal(opened.length, 1);
    assert.equal(opened[0][0], '/plantillas/7/previsualizar-pdf');
});

test('Ctrl o Cmd S guarda incluso al editar un campo del inspector', () => {
    for (const modifier of ['ctrlKey', 'metaKey']) {
        const { studio, form } = createStudio();
        const event = {
            key: 's', [modifier]: true, target: { tagName: 'INPUT', isContentEditable: false },
            preventDefault() { this.prevented = true; },
        };

        studio.handleKeyDown(event);

        assert.equal(event.prevented, true);
        assert.equal(form.submissions, 1);
    }
});

test('los atajos de flechas y deshacer/rehacer mantienen el historial del elemento', () => {
    const { studio } = createStudio();
    studio.selectElement('heading');
    const key = (value, modifiers = {}) => ({
        key: value, target: { tagName: 'DIV', isContentEditable: false },
        preventDefault() {}, ...modifiers,
    });
    studio.handleKeyDown(key('ArrowRight', { shiftKey: true }));
    studio.flushHistory();

    studio.handleKeyDown(key('z', { metaKey: true }));

    assert.equal(studio.elements[0].x, 10);
    studio.handleKeyDown(key('z', { metaKey: true, shiftKey: true }));
    assert.equal(studio.elements[0].x, 12);
});

for (const orientation of ['landscape', 'portrait']) {
    for (const count of [1, 2, 3, 8]) {
        test(`adaptar ${orientation} distribuye ${count} firmas y logos sin superponer ni desbordar`, () => {
            const items = [];
            for (let i = 0; i < count; i++) {
                items.push(element({ id: `sig-${i}`, type: 'signature' }));
                items.push(element({ id: `logo-${i}`, type: 'logo' }));
            }
            const { studio } = createStudio({ initialElements: items });

            studio.autoAdaptLayout(orientation);

            assert.equal(studio.orientation, orientation);
            for (const item of studio.elements) { assertInsideSheet(item); }
            assertNoOverlaps(studio.elements.filter(item => item.type === 'signature'));
            assertNoOverlaps(studio.elements.filter(item => item.type === 'logo'));
        });
    }
}

test('adaptar conserva la geometría de elementos bloqueados', () => {
    const locked = element({ id: 'locked', type: 'signature', is_locked: true, x: 40, y: 70, width: 20, height: 15 });
    const { studio } = createStudio({ initialElements: [locked, element({ id: 'free', type: 'signature' })] });
    const original = JSON.stringify(studio.elements[0]);

    studio.autoAdaptLayout('portrait');

    assert.equal(JSON.stringify(studio.elements[0]), original);
    assertInsideSheet(studio.elements[1]);
    assertNoOverlaps(studio.elements);
});

test('la observación de tamaño ajusta automáticamente y respeta un zoom manual posterior', () => {
    const { studio, observers, viewport } = createStudio();
    assert.equal(observers.length, 1);
    viewport.clientWidth = 300;
    viewport.clientHeight = 400;

    observers[0].callback();

    assert.ok(820 * studio.zoom / 100 <= 300);
    studio.setZoom(150);
    viewport.clientWidth = 250;
    observers[0].callback();
    assert.equal(studio.zoom, 150);
});

test('una salida limpia o un envío en curso no presentan alerta de cambios pendientes', () => {
    const { studio, window } = createStudio();
    const event = () => ({ preventDefault() { this.prevented = true; }, returnValue: undefined });
    const cleanEvent = event();

    window.dispatch('beforeunload', cleanEvent);

    assert.equal(cleanEvent.prevented, undefined);
    studio.name = 'Cambio pendiente';
    studio.markDirty();
    studio.isSaving = true;
    const savingEvent = event();
    window.dispatch('beforeunload', savingEvent);
    assert.equal(savingEvent.prevented, undefined);
});

test('cancelar la salida conserva la protección y aceptarla evita una segunda alerta al salir', () => {
    const { studio, window } = createStudio();
    studio.name = 'Cambio pendiente';
    studio.markDirty();
    let confirmations = 0;
    let accept = false;
    window.confirm = () => { confirmations++; return accept; };
    const event = () => ({ button: 0, preventDefault() { this.prevented = true; }, returnValue: undefined });
    const cancelled = event();

    assert.equal(studio.confirmLeave(cancelled), false);

    assert.equal(cancelled.prevented, true);
    assert.equal(studio.isDirty, true);
    const afterCancellation = event();
    window.dispatch('beforeunload', afterCancellation);
    assert.equal(afterCancellation.prevented, true);
    accept = true;
    const accepted = event();
    assert.equal(studio.confirmLeave(accepted), true);
    assert.equal(accepted.prevented, undefined);
    const afterAcceptance = event();
    window.dispatch('beforeunload', afterAcceptance);
    assert.equal(afterAcceptance.prevented, undefined);
    assert.equal(confirmations, 2);
    studio.description = 'Una edición posterior debe volver a estar protegida';
    studio.markDirty();
    const afterNewEdit = event();
    window.dispatch('beforeunload', afterNewEdit);
    assert.equal(afterNewEdit.prevented, true);
});

test('abrir el enlace de regreso en otra pestaña no desactiva la protección de la pestaña actual', () => {
    const { studio, window } = createStudio();
    studio.name = 'Edición abierta';
    studio.markDirty();
    let confirmations = 0;
    window.confirm = () => { confirmations++; return true; };

    const result = studio.confirmLeave({ metaKey: true, button: 0, preventDefault() { this.prevented = true; } });

    assert.equal(result, true);
    assert.equal(confirmations, 0);
    const event = { preventDefault() { this.prevented = true; }, returnValue: undefined };
    window.dispatch('beforeunload', event);
    assert.equal(event.prevented, true);
});

test('beforeunload protege un documento sucio y destroy retira listeners y observer', () => {
    const { studio, window, observers } = createStudio();
    studio.name = 'Cambio no guardado';
    studio.markDirty();
    const event = { preventDefault() { this.prevented = true; }, returnValue: undefined };

    window.dispatch('beforeunload', event);

    assert.equal(event.prevented, true);
    assert.notEqual(event.returnValue, undefined);
    assert.ok(window.listenerCount > 0);
    assert.ok(observers.length > 0);
    studio.destroy();
    assert.equal(window.listenerCount, 0);
    assert.ok(observers.every(observer => observer.disconnected));
});
