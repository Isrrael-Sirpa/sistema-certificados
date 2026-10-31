<script>
function certificateStudio(config) {
    const clone = value => JSON.parse(JSON.stringify(value));
    const stable = value => JSON.stringify(value, function (key, item) {
        return item && !Array.isArray(item) && typeof item === 'object'
            ? Object.keys(item).sort().reduce((result, property) => { result[property] = item[property]; return result; }, {})
            : item;
    });
    const clamp = (value, min, max) => Math.max(min, Math.min(max, Number(value) || 0));
    const round = value => Math.round(value * 100) / 100;
    const listeners = [];
    const handledKeys = new WeakSet();
    let historyTimer = null;
    let resizeObserver = null;
    let savedState = '';
    let initialized = false;
    let destroyed = false;
    let allowNavigation = false;
    let pointerTarget = null;
    let pointerId = null;
    let gestureState = null;
    let gestureRedo = [];
    let idSequence = 0;

    return {
        templateId: config.templateId,
        currentVersion: Number(config.currentVersion) || 1,
        name: config.name || '',
        category: config.category || 'institucional',
        orientation: config.orientation === 'portrait' ? 'portrait' : 'landscape',
        description: config.description || '',
        schema: clone(config.initialSchema || {}),
        elements: clone(config.initialElements || config.initialSchema?.elements || []),
        assets: config.assets || {},
        layoutPresets: config.layoutPresets || {},
        certificateTypes: clone(config.certificateTypes || []),
        sampleTypeId: '',
        sampleIssueDate: config.sampleIssueDate || '2026-09-20',
        sampleExpirationDate: '',
        showConditionalElements: false,
        backLink: config.backUrl || config.backLink || '',
        backUrl: config.backUrl || config.backLink || '',
        previewPdfUrl: config.previewPdfUrl || '',
        leftTab: 'tools',
        libFilter: 'all',
        selectedId: null,
        zoom: 100,
        autoFit: true,
        showGrid: false,
        showSnapX: false,
        showSnapY: false,
        isDirty: false,
        isSaving: false,
        activePanel: 'canvas',
        leftPanelOpen: true,
        rightPanelOpen: true,
        viewportWidth: window.innerWidth,
        showVersionDialog: false,
        versionChangeSummary: '',
        previewNotice: '',
        isDragging: false,
        isResizing: false,
        activeElementId: null,
        dragStartX: 0,
        dragStartY: 0,
        initialElementX: 0,
        initialElementY: 0,
        initialElementW: 0,
        initialElementH: 0,
        undoStack: [],
        redoStack: [],
        sampleProfile: 'participant',
        sampleData: {
            beneficiaryName: 'Univ. Rodrigo Quispe Mamani',
            beneficiaryDoc: 'CI: 8493021 LP',
            beneficiaryEmail: 'rodrigo.quispe@example.edu.bo',
            activityName: 'Seminario Internacional de Inteligencia Artificial y Ciencia de Datos',
            activityHours: '40',
            activityDates: 'del 15 al 20 de septiembre de 2026',
            activityType: 'Seminario',
            location: 'Paraninfo Universitario',
            organizer: 'Vicedecanato',
            authorityName: 'Ing. Aniceto Velarde Forest',
            authorityPosition: 'Vicedecano de Facultad',
            certificateType: 'Certificado de Aprobación',
            certificateCode: 'CERT-2026-000042',
            issuePlaceDate: 'Dado en la ciudad de La Paz, a los 20 días del mes de septiembre de 2026.',
            issueDateLong: 'a los 20 días del mes de septiembre del año 2026',
            issueDate: '20 de septiembre de 2026',
            expirationDate: '20 de septiembre de 2029',
            validityType: 'years',
            score: '92.5',
            scoreLabel: '92.5/100 · Aprobado con distinción',
            resolution: '045/2026',
            institutionName: 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
            facultyName: 'FACULTAD DE TECNOLOGÍA',
            departmentName: 'VICEDECANATO',
            city: 'La Paz - Bolivia'
        },

        init() {
            if (initialized) { return; }
            initialized = true;
            destroyed = false;
            delete this.schema.elements;
            Object.entries({ primary_color: '#024966', secondary_color: '#d4af37', border_style: 'ornamental-double', font_family: 'Helvetica', text_color: '#1e293b' }).forEach(([key, value]) => {
                if (!this.schema[key]) { this.schema[key] = value; }
            });
            this.elements.forEach((element, index) => {
                element.id = element.id || `element_initial_${index}`;
                element.styles = element.styles || {};
                element.type = element.type || 'text';
                if (element.opacity === undefined || element.opacity === null) { element.opacity = 1; }
                for (const [key, value] of Object.entries({ x: 10, y: 10, width: 80, height: 8 })) {
                    if (element[key] === undefined || element[key] === null) { element[key] = value; }
                }
                for (const property of ['font_size', 'font_weight', 'font_style', 'text_color', 'text_align', 'font_family', 'line_height', 'letter_spacing', 'text_transform']) {
                    if (element[property] !== undefined && element.styles[property] === undefined) { element.styles[property] = element[property]; }
                }
            });
            if (this.certificateTypes.length) {
                this.sampleTypeId = String((this.certificateTypes.find(type => type.has_score) || this.certificateTypes[0]).id);
                this.selectSampleType();
            }
            savedState = this.serializeDocument();
            this.undoStack = [savedState];
            this.redoStack = [];
            this.isDirty = false;
            if (typeof this.$watch === 'function') {
                for (const property of ['elements', 'schema', 'name', 'category', 'description', 'orientation']) {
                    this.$watch(property, () => {
                        if (destroyed) { return; }
                        this.markDirty();
                        if (property === 'orientation' && this.autoFit) { this.nextRender(() => this.fitCanvas()); }
                    });
                }
            }
            const listen = (type, callback) => {
                window.addEventListener(type, callback);
                listeners.push(() => window.removeEventListener(type, callback));
            };
            listen('pointermove', event => this.handlePointerMove(event));
            listen('pointerup', event => this.handlePointerUp(event));
            listen('pointercancel', event => this.handlePointerCancel(event));
            listen('keydown', event => this.handleKeyDown(event));
            listen('beforeunload', event => {
                this.refreshDirtyState();
                if (!this.isDirty || this.isSaving || allowNavigation) { return; }
                event.preventDefault();
                event.returnValue = '';
            });
            listen('resize', () => {
                this.viewportWidth = window.innerWidth;
                if (window.innerWidth >= 1024) { this.activePanel = 'canvas'; }
                if (this.autoFit) { this.fitCanvas(); }
            });
            this.nextRender(() => {
                const viewport = this.$refs?.canvasViewport;
                if (viewport && typeof ResizeObserver !== 'undefined') {
                    resizeObserver = new ResizeObserver(() => { if (!destroyed && this.autoFit) { this.fitCanvas(); } });
                    resizeObserver.observe(viewport);
                }
                if (this.autoFit) { this.fitCanvas(); }
            });
        },

        destroy() {
            destroyed = true;
            clearTimeout(historyTimer);
            historyTimer = null;
            listeners.splice(0).forEach(remove => remove());
            resizeObserver?.disconnect();
            resizeObserver = null;
            this.releasePointer();
            this.isDragging = false;
            this.isResizing = false;
        },

        nextRender(callback) {
            if (typeof this.$nextTick === 'function') { this.$nextTick(() => { if (!destroyed) { callback(); } }); }
            else if (!destroyed) { callback(); }
        },

        get selectedElement() { return this.elements.find(element => element.id === this.selectedId) || null; },
        get isDesktop() { return this.viewportWidth >= 1024; },
        get sortedLayers() { return [...this.elements].sort((a, b) => (b.z_index || 0) - (a.z_index || 0)); },
        get canvasWidth() { return this.orientation === 'landscape' ? 820 : 580; },
        get canvasHeight() { return this.orientation === 'landscape' ? 580 : 820; },
        get canvasWidthStyle() { return `${this.canvasWidth}px`; },
        get canvasHeightStyle() { return `${this.canvasHeight}px`; },
        get stageWidthStyle() { return `${round(this.canvasWidth * this.zoom / 100)}px`; },
        get stageHeightStyle() { return `${round(this.canvasHeight * this.zoom / 100)}px`; },
        get canvasTransformStyle() { return `transform:scale(${this.zoom / 100});transform-origin:top left;`; },
        get canvasStageStyle() { return `width:${this.stageWidthStyle};height:${this.stageHeightStyle};flex-shrink:0;`; },
        get canUndo() { return this.undoStack.length > 1 || this.serializeDocument() !== this.undoStack.at(-1); },
        get canRedo() { return this.redoStack.length > 0; },
        get selectedSampleType() { return this.certificateTypes.find(type => String(type.id) === String(this.sampleTypeId)) || null; },
        get sampleSupportsScore() { return this.selectedSampleType ? Boolean(this.selectedSampleType.has_score) : true; },
        get sampleHasScore() {
            return this.sampleSupportsScore && ((this.sampleData.score !== '' && this.sampleData.score !== null && this.sampleData.score !== undefined) || Boolean(this.sampleData.scoreLabel?.trim()));
        },
        get sampleIsPassing() { return this.sampleHasScore && this.sampleData.score !== '' && this.sampleData.score !== null && Number(this.sampleData.score) >= Number(this.selectedSampleType?.min_passing_score ?? 51); },
        get sampleError() {
            if (!this.validSampleDate(this.sampleIssueDate)) { return 'Indica una fecha de emisión válida para la muestra.'; }
            if (this.sampleSupportsScore && this.sampleData.score !== '' && this.sampleData.score !== null && (!Number.isFinite(Number(this.sampleData.score)) || Number(this.sampleData.score) < 0 || Number(this.sampleData.score) > 100)) { return 'El puntaje de muestra debe estar entre 0 y 100.'; }
            if (this.sampleData.validityType === 'custom' && (!this.validSampleDate(this.sampleExpirationDate) || this.sampleExpirationDate < this.sampleIssueDate)) { return 'Indica un vencimiento igual o posterior a la fecha de emisión.'; }
            return '';
        },
        validSampleDate(value) { return /^\d{4}-\d{2}-\d{2}$/.test(value || '') && !Number.isNaN(Date.parse(`${value}T12:00:00Z`)) && new Date(`${value}T12:00:00Z`).toISOString().slice(0, 10) === value; },
        formatSampleDate(value) { return this.validSampleDate(value) ? new Date(`${value}T12:00:00Z`).toLocaleDateString('es-BO', { day: 'numeric', month: 'long', year: 'numeric', timeZone: 'UTC' }) : ''; },
        selectSampleType() {
            const type = this.selectedSampleType;
            if (!type) { return; }
            this.sampleData.certificateType = type.name;
            this.sampleData.validityType = type.validity_mode || 'years';
            this.sampleData.score = type.has_score ? Math.min(100, Math.max(92.5, Number(type.min_passing_score) || 0)) : '';
            this.sampleData.scoreLabel = '';
            this.sampleExpirationDate = '';
            this.updateSampleDates();
        },
        updateSampleDates() {
            if (!this.validSampleDate(this.sampleIssueDate)) { return; }
            const issue = new Date(`${this.sampleIssueDate}T12:00:00Z`);
            this.sampleData.issueDate = this.formatSampleDate(this.sampleIssueDate);
            this.sampleData.issueDateLong = `a los ${issue.getUTCDate()} días del mes de ${issue.toLocaleDateString('es-BO', { month: 'long', timeZone: 'UTC' })} del año ${issue.getUTCFullYear()}`;
            this.sampleData.issuePlaceDate = `Dado en la ciudad de ${this.sampleData.city}, ${this.sampleData.issueDateLong}.`;
            if (this.sampleData.validityType === 'permanent') { this.sampleData.expirationDate = ''; return; }
            if (this.sampleData.validityType === 'years') {
                const expiration = new Date(issue);
                expiration.setUTCFullYear(issue.getUTCFullYear() + Number(this.selectedSampleType?.default_validity_years ?? 3));
                this.sampleExpirationDate = expiration.toISOString().slice(0, 10);
            }
            this.sampleData.expirationDate = this.formatSampleDate(this.sampleExpirationDate);
        },
        matchesVisibilityRule(element) {
            switch (element.visibility_rule || 'always') {
                case 'with_score': return this.sampleHasScore;
                case 'permanent': return this.sampleData.validityType === 'permanent';
                case 'temporary': return this.sampleData.validityType !== 'permanent';
                default: return true;
            }
        },
        isElementVisible(element) { return element.is_visible !== false && (this.showConditionalElements || this.matchesVisibilityRule(element)); },
        get allAssets() {
            const groups = Array.isArray(this.assets) ? [this.assets] : Object.values(this.assets);
            const found = new Map();
            groups.filter(Array.isArray).flat().forEach(asset => {
                const key = asset.id ?? asset.url;
                if (!found.has(key)) { found.set(key, asset); }
            });
            return [...found.values()];
        },
        get filteredAssets() {
            if (this.libFilter === 'all') { return this.allAssets; }
            return this.allAssets.filter(asset => (asset.category || asset.type || '').includes(this.libFilter));
        },

        getDocumentState() {
            const schema = clone(this.schema);
            delete schema.elements;
            return { name: this.name, category: this.category, description: this.description, orientation: this.orientation, schema, elements: clone(this.elements) };
        },
        serializeDocument() { return stable(this.getDocumentState()); },
        refreshDirtyState() { this.isDirty = this.serializeDocument() !== savedState; return this.isDirty; },
        markDirty() {
            const previous = this.undoStack.length ? JSON.parse(this.undoStack.at(-1)).elements.find(element => element.id === this.selectedId) : null;
            const selected = this.selectedElement;
            if (selected && !selected.is_locked && previous && ['x', 'y', 'width', 'height'].some(key => selected[key] !== previous[key])) {
                this.boundElement(selected);
            }
            this.refreshDirtyState();
            const changed = this.serializeDocument() !== this.undoStack.at(-1);
            if (!changed || destroyed) { return; }
            this.redoStack = [];
            allowNavigation = false;
            this.previewNotice = '';
            if (this.isDragging || this.isResizing) { return; }
            clearTimeout(historyTimer);
            historyTimer = setTimeout(() => this.pushHistoryState(), 400);
        },
        flushHistory() {
            clearTimeout(historyTimer);
            historyTimer = null;
            this.pushHistoryState();
        },
        pushHistoryState() {
            clearTimeout(historyTimer);
            historyTimer = null;
            const snapshot = this.serializeDocument();
            if (snapshot !== this.undoStack.at(-1)) {
                this.undoStack.push(snapshot);
                if (this.undoStack.length > 60) { this.undoStack.shift(); }
                this.redoStack = [];
            }
            this.refreshDirtyState();
        },
        restoreState(snapshot) {
            const state = JSON.parse(snapshot);
            for (const property of ['name', 'category', 'description', 'orientation', 'schema', 'elements']) { this[property] = state[property]; }
            if (!this.selectedElement) { this.selectedId = null; }
            this.refreshDirtyState();
            if (this.autoFit) { this.nextRender(() => this.fitCanvas()); }
        },
        undo() {
            this.flushHistory();
            if (this.undoStack.length <= 1) { return; }
            this.redoStack.push(this.undoStack.pop());
            this.restoreState(this.undoStack.at(-1));
        },
        redo() {
            this.flushHistory();
            if (!this.redoStack.length) { return; }
            const next = this.redoStack.pop();
            this.undoStack.push(next);
            this.restoreState(next);
        },
        commitChange(change) {
            this.flushHistory();
            change();
            this.pushHistoryState();
        },

        isTextType(type) { return ['header_title', 'header_subtitle', 'certificate_type', 'beneficiary_name', 'beneficiary_document', 'body_text', 'activity_dates', 'metadata_code', 'text'].includes(type); },
        isImageType(type) { return ['logo', 'watermark', 'stamp', 'image', 'background'].includes(type); },
        isQrType(type) { return ['qr', 'qr_code'].includes(type); },
        qrSizeMm(element = this.selectedElement) {
            if (!element || !this.isQrType(element.type)) { return 0; }
            const pageWidth = this.orientation === 'portrait' ? 210 : 297;
            const pageHeight = this.orientation === 'portrait' ? 297 : 210;
            return Math.round(Math.min(Number(element.width) * pageWidth / 100, Number(element.height) * pageHeight / 100 * .7) * 10) / 10;
        },
        qrDimensions(sizeMm) {
            const pageWidth = this.orientation === 'portrait' ? 210 : 297;
            const pageHeight = this.orientation === 'portrait' ? 297 : 210;
            return { width: sizeMm / pageWidth * 100, height: sizeMm / .7 / pageHeight * 100 };
        },
        resizeQr(sizeMm) {
            const element = this.selectedElement;
            if (!element || !this.isQrType(element.type) || element.is_locked || this.isSaving || sizeMm === '' || !Number.isFinite(Number(sizeMm))) { return; }
            const dimensions = this.qrDimensions(clamp(Number(sizeMm), 20, 60));
            const centerX = Number(element.x) + Number(element.width) / 2;
            const centerY = Number(element.y) + Number(element.height) / 2;
            Object.assign(element, dimensions, { x: centerX - dimensions.width / 2, y: centerY - dimensions.height / 2 });
            this.boundElement(element);
            this.markDirty();
        },
        isSignatureType(type) { return ['signature', 'signature_block'].includes(type); },
        isLineType(type) { return ['divider', 'decorative_line', 'line'].includes(type); },
        isShapeType(type) { return ['shape', 'band', 'rectangle', 'ellipse'].includes(type); },
        fittedFontSize(element) {
            const styles = element.styles || {};
            let size = Number(styles.font_size || element.font_size || 12);
            if (styles.fit_text !== true) { return size; }
            const minimum = Math.min(size, Number(styles.min_font_size || Math.max(8, size * .72)));
            const width = this.canvasWidth * Number(element.width || 80) / 100;
            const height = this.canvasHeight * Number(element.height || 8) / 100;
            const resolved = this.interpolateText(element.content || '');
            const text = styles.text_transform === 'uppercase' ? resolved.toLocaleUpperCase('es') : resolved;
            const context = typeof document.createElement === 'function' ? document.createElement('canvas').getContext('2d') : null;
            const measure = value => {
                if (!context) { return value.length * size * .55; }
                context.font = `${styles.font_style || 'normal'} ${styles.font_weight || 'normal'} ${size}px "${styles.font_family || this.schema.font_family || 'serif'}"`;
                return context.measureText(value).width + Math.max(0, value.length - 1) * Number(styles.letter_spacing || 0);
            };
            do {
                let lines = 0;
                for (const paragraph of text.split(/\r?\n/)) {
                    let line = '';
                    for (const word of paragraph.trim().split(/\s+/)) {
                        const candidate = line ? line + ' ' + word : word;
                        if (line && measure(candidate) > width) { lines++; line = word; } else { line = candidate; }
                    }
                    lines += Math.max(1, Math.ceil(measure(line) / Math.max(1, width)));
                }
                if (lines * size * 1.17 * Number(styles.line_height || 1.3) <= height || size <= minimum) { return Math.round(size * 100) / 100; }
                size = Math.max(minimum, size - .5);
            } while (true);
        },
        getElementIcon(type) {
            return ({ header_title: '🏛️', header_subtitle: '🏢', certificate_type: '📜', beneficiary_name: '👤', beneficiary_document: '🪪', body_text: '📝', activity_dates: '📅', metadata_code: '🔢', text: '🔤', logo: '🛡️', signature: '✍️', signature_block: '✍️', stamp: '⭕', watermark: '💧', qr: '🏁', qr_code: '🏁', divider: '➖', decorative_line: '➖' })[type] || '📦';
        },
        resolveAssetUrl(element) {
            const asset = element.asset_id ? this.allAssets.find(item => String(item.id) === String(element.asset_id)) : null;
            if (asset?.url) { return asset.url; }
            if (element.asset_url) { return element.asset_url; }
            if (element.asset_base64) { return element.asset_base64; }
            if (element.asset_path) {
                return /^(https?:|data:)/i.test(element.asset_path) ? element.asset_path : '/' + element.asset_path.replace(/^\/+/, '');
            }
            return '';
        },

        interpolateText(text) {
            if (!text) { return ''; }
            const sample = this.sampleData;
            const modality = sample.certificateType.replace(/^Certificado de\s+/i, '');
            const participantRole = ({ asistencia: 'Asistente', 'aprobación': 'Aprobante', aprobacion: 'Aprobante', 'disertante / ponente': 'Disertante / Ponente', organizador: 'Organizador' })[modality.toLowerCase()] || modality;
            const permanent = sample.validityType === 'permanent';
            const values = {
                'beneficiario.nombre': sample.beneficiaryName, 'beneficiario.documento': sample.beneficiaryDoc, 'beneficiario.email': sample.beneficiaryEmail,
                'actividad.nombre': sample.activityName, 'actividad.horas': sample.activityHours, 'actividad.fechas': sample.activityDates,
                'actividad.tipo': sample.activityType, 'actividad.lugar': sample.location, 'actividad.organizador': sample.organizer,
                'actividad.autoridad': sample.authorityName, 'actividad.resolucion': sample.resolution,
                'certificado.tipo': sample.certificateType, 'certificado.modalidad': modality, 'participante.rol': participantRole,
                'certificado.codigo': sample.certificateCode, 'certificado.lugar_fecha_emision': sample.issuePlaceDate,
                'certificado.fecha_emision_larga': sample.issueDateLong, 'certificado.fecha_emision': sample.issueDate,
                'certificado.fecha_vencimiento': permanent ? '' : sample.expirationDate,
                'certificado.puntaje': this.sampleSupportsScore ? (sample.score !== '' && sample.score !== null && sample.score !== undefined ? sample.score : (sample.scoreLabel || '')) : '',
                'certificado.calificacion': this.sampleSupportsScore ? (sample.scoreLabel || (sample.score !== '' && sample.score !== null ? `${sample.score} pts` : '')) : '',
                'certificado.mencion': this.sampleSupportsScore ? (sample.scoreLabel || '') : '',
                'certificado.vigencia': permanent ? 'Vigencia Permanente / No expira' : (sample.expirationDate ? `Válido hasta el ${sample.expirationDate}` : 'Vigencia temporal'),
                'certificado.vigencia_corta': permanent ? 'PERMANENTE' : (sample.expirationDate ? `Hasta ${sample.expirationDate}` : 'Temporal'),
                'acreditacion.resolucion': `Acreditado bajo ${sample.resolution}`,
                'institucion.nombre': sample.institutionName, 'facultad.nombre': sample.facultyName, 'vicedecanato.nombre': sample.departmentName,
                'autoridad.nombre': sample.authorityName, 'autoridad.cargo': sample.authorityPosition, 'institucion.ciudad': sample.city
            };
            return String(text).replace(/@?\{\{\s*([^{}]+?)\s*\}\}/g, (match, key) => Object.hasOwn(values, key) ? String(values[key] ?? '') : match);
        },
        updateSampleProfile() {
            const profiles = {
                participant: ['Univ. Rodrigo Quispe Mamani', 'CI: 8493021 LP', 'Certificado de Aprobación', 'years', '92.5', '92.5/100 · Aprobado con distinción'],
                speaker: ['Dr. Alejandro Morales Ramos', 'CI: 4729103 SC', 'Certificado de Disertante / Ponente', 'permanent', '', ''],
                organizer: ['Lic. Carmen Vargas Luna', 'CI: 3892019 LP', 'Certificado de Organizador', 'years', '', ''],
                honor: ['Univ. Valeria Torrico Siles', 'CI: 9201834 CB', 'Certificado de Reconocimiento', 'permanent', '100', '100/100 · Excelencia académica']
            };
            const profile = profiles[this.sampleProfile] || profiles.participant;
            [this.sampleData.beneficiaryName, this.sampleData.beneficiaryDoc, this.sampleData.certificateType, this.sampleData.validityType, this.sampleData.score, this.sampleData.scoreLabel] = profile;
            if (this.certificateTypes.length) {
                const code = { participant: 'aprobacion', speaker: 'disertante', organizer: 'organizador', honor: 'reconocimiento' }[this.sampleProfile];
                const type = this.certificateTypes.find(item => item.code === code);
                if (type) { this.sampleTypeId = String(type.id); }
                this.selectSampleType();
            }
        },

        selectElement(id) { this.selectedId = id; },
        toggleLeftPanel() {
            if (window.innerWidth < 1024) { this.activePanel = this.activePanel === 'tools' ? 'canvas' : 'tools'; }
            else { this.leftPanelOpen = !this.leftPanelOpen; }
            this.nextRender(() => { if (this.autoFit) { this.fitCanvas(); } });
        },
        toggleRightPanel() {
            if (window.innerWidth < 1024) { this.activePanel = this.activePanel === 'inspector' ? 'canvas' : 'inspector'; }
            else { this.rightPanelOpen = !this.rightPanelOpen; }
            this.nextRender(() => { if (this.autoFit) { this.fitCanvas(); } });
        },
        closePanels() { this.activePanel = 'canvas'; },
        openInspector() { if (window.innerWidth < 1024) { this.activePanel = 'inspector'; } else { this.rightPanelOpen = true; } },
        fitCanvas() {
            const viewport = this.$refs?.canvasViewport;
            if (!viewport) { return; }
            const rect = viewport.getBoundingClientRect();
            const style = typeof window.getComputedStyle === 'function' ? window.getComputedStyle(viewport) : {};
            const horizontalPadding = (parseFloat(style.paddingLeft) || 0) + (parseFloat(style.paddingRight) || 0);
            const verticalPadding = (parseFloat(style.paddingTop) || 0) + (parseFloat(style.paddingBottom) || 0);
            const width = (viewport.clientWidth || rect.width) - horizontalPadding;
            const height = (viewport.clientHeight || rect.height) - verticalPadding;
            if (width <= 0 || height <= 0) { return; }
            this.autoFit = true;
            this.zoom = clamp(Math.floor(Math.min(width / this.canvasWidth, height / this.canvasHeight) * 100), 20, 200);
        },
        setZoom(value) { this.autoFit = false; this.zoom = clamp(value, 20, 200); },

        boundElement(element) {
            element.width = round(clamp(element.width, 1, 100));
            element.height = round(clamp(element.height, 1, 100));
            element.x = round(clamp(element.x, 0, 100 - element.width));
            element.y = round(clamp(element.y, 0, 100 - element.height));
        },
        startDrag(event, id) { this.beginPointer(event, id, false); },
        startResize(event, id) { this.beginPointer(event, id, true); },
        beginPointer(event, id, resize) {
            const element = this.elements.find(item => item.id === id);
            if (!element || element.is_locked || this.isSaving || event.isPrimary === false || (event.button !== undefined && event.button !== 0) || this.isDragging || this.isResizing) { return; }
            this.flushHistory();
            this.selectedId = id;
            this.activeElementId = id;
            gestureState = this.serializeDocument();
            gestureRedo = [...this.redoStack];
            this.dragStartX = event.clientX;
            this.dragStartY = event.clientY;
            this.initialElementX = Number(element.x) || 0;
            this.initialElementY = Number(element.y) || 0;
            this.initialElementW = Number(element.width) || 1;
            this.initialElementH = Number(element.height) || 1;
            this.isDragging = !resize;
            this.isResizing = resize;
            pointerId = event.pointerId ?? null;
            pointerTarget = event.currentTarget || event.target;
            if (pointerId !== null && typeof pointerTarget?.setPointerCapture === 'function') {
                try { pointerTarget.setPointerCapture(pointerId); } catch (error) { pointerTarget = null; }
            }
            if (resize) { event.stopPropagation?.(); }
            event.preventDefault?.();
        },
        handlePointerMove(event) {
            if ((!this.isDragging && !this.isResizing) || (pointerId !== null && event.pointerId !== pointerId)) { return; }
            const sheet = document.getElementById('certificateCanvasSheet');
            const element = this.elements.find(item => item.id === this.activeElementId);
            if (!sheet || !element || element.is_locked) { return; }
            const rect = sheet.getBoundingClientRect();
            if (!rect.width || !rect.height) { return; }
            const dx = (event.clientX - this.dragStartX) / rect.width * 100;
            const dy = (event.clientY - this.dragStartY) / rect.height * 100;
            if (this.isDragging) {
                element.x = clamp(this.initialElementX + dx, 0, 100 - element.width);
                element.y = clamp(this.initialElementY + dy, 0, 100 - element.height);
                this.showSnapX = Math.abs(element.x + element.width / 2 - 50) < 1.2;
                this.showSnapY = Math.abs(element.y + element.height / 2 - 50) < 1.2;
                if (this.showSnapX) { element.x = 50 - element.width / 2; }
                if (this.showSnapY) { element.y = 50 - element.height / 2; }
            } else {
                element.width = clamp(this.initialElementW + dx, 1, 100 - element.x);
                element.height = clamp(this.initialElementH + dy, 1, 100 - element.y);
            }
            this.boundElement(element);
            this.markDirty();
            event.preventDefault?.();
        },
        releasePointer() {
            if (pointerId !== null && typeof pointerTarget?.releasePointerCapture === 'function') {
                try { pointerTarget.releasePointerCapture(pointerId); } catch (error) { /* The browser may already have released capture. */ }
            }
            pointerTarget = null;
            pointerId = null;
        },
        handlePointerUp(event = {}) {
            if ((!this.isDragging && !this.isResizing) || (pointerId !== null && event.pointerId !== undefined && event.pointerId !== pointerId)) { return; }
            this.releasePointer();
            this.isDragging = false;
            this.isResizing = false;
            this.activeElementId = null;
            this.showSnapX = false;
            this.showSnapY = false;
            gestureState = null;
            gestureRedo = [];
            this.pushHistoryState();
        },
        handlePointerCancel(event = {}) {
            if ((!this.isDragging && !this.isResizing) || (pointerId !== null && event.pointerId !== undefined && event.pointerId !== pointerId)) { return; }
            const previous = gestureState;
            const previousRedo = gestureRedo;
            this.releasePointer();
            this.isDragging = false;
            this.isResizing = false;
            this.activeElementId = null;
            this.showSnapX = false;
            this.showSnapY = false;
            gestureState = null;
            gestureRedo = [];
            if (previous) { this.restoreState(previous); }
            this.redoStack = previousRedo;
        },
        handleMouseMove(event) { this.handlePointerMove(event); },
        handleMouseUp(event = {}) { this.handlePointerUp(event); },

        handleKeyDown(event) {
            if (handledKeys.has(event)) { return; }
            handledKeys.add(event);
            const key = event.key?.toLowerCase();
            if (key === 'escape') {
                if (this.isDragging || this.isResizing) { this.handlePointerCancel(); }
                this.closeVersionDialog();
                this.closePanels();
                return;
            }
            const modifier = event.ctrlKey || event.metaKey;
            if (modifier && key === 's') { event.preventDefault(); this.saveTemplate(false); return; }
            const target = event.target;
            const editing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(target?.tagName) || target?.isContentEditable || target?.closest?.('[contenteditable="true"]');
            if (editing || this.showVersionDialog || this.isSaving) { return; }
            if (modifier && key === 'z') { event.preventDefault(); event.shiftKey ? this.redo() : this.undo(); return; }
            if (modifier && key === 'y') { event.preventDefault(); this.redo(); return; }
            const element = this.selectedElement;
            if (!element || element.is_locked || event.altKey || modifier) { return; }
            const step = event.shiftKey ? 2 : 0.5;
            if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
                event.preventDefault();
                if (event.key === 'ArrowLeft') { element.x = Number(element.x) - step; }
                if (event.key === 'ArrowRight') { element.x = Number(element.x) + step; }
                if (event.key === 'ArrowUp') { element.y = Number(element.y) - step; }
                if (event.key === 'ArrowDown') { element.y = Number(element.y) + step; }
                this.boundElement(element);
                this.markDirty();
            } else if (key === 'delete' || key === 'backspace') {
                event.preventDefault();
                this.deleteElement(element.id);
            }
        },

        createElement(type, properties = {}) {
            return { id: `el_${Date.now()}_${++idSequence}`, type, name: type, x: 10, y: 40, width: 80, height: 8, z_index: 20, opacity: 1, rotation: 0, is_visible: true, is_locked: false, is_mandatory: false, styles: {}, ...properties };
        },
        insertElement(element) {
            this.commitChange(() => { this.elements.push(element); this.selectedId = element.id; });
            this.activePanel = 'canvas';
        },
        addTextElement(type, defaultContent, fontSize = 12, isBold = false, fontStyle = 'normal') {
            this.insertElement(this.createElement(type, { name: defaultContent.slice(0, 20).toUpperCase(), content: defaultContent, styles: { font_size: fontSize, font_weight: isBold ? 'bold' : 'normal', font_style: fontStyle, text_align: 'center', text_color: this.schema.text_color || '#1e293b' } }));
        },
        get academicFields() {
            const variable = name => '{' + '{' + name + '}' + '}';
            return {
                qualification: { name: 'Calificación', content: 'Calificación: ' + variable('certificado.puntaje'), visibility_rule: 'with_score', height: 4, font_size: 14, tokens: ['certificado.puntaje', 'certificado.calificacion'] },
                mention: { name: 'Mención académica', content: variable('certificado.mencion'), visibility_rule: 'with_score', height: 3, font_size: 11, tokens: ['certificado.mencion'] },
                validity: { name: 'Vigencia del certificado', content: variable('certificado.vigencia'), visibility_rule: 'always', height: 3, font_size: 10, tokens: ['certificado.vigencia', 'certificado.vigencia_corta'] },
                expiration: { name: 'Fecha de vencimiento', content: 'Válido hasta el ' + variable('certificado.fecha_vencimiento'), visibility_rule: 'temporary', height: 3, font_size: 10, tokens: ['certificado.fecha_vencimiento'] },
                modality: { name: 'Modalidad de certificación', content: variable('certificado.modalidad'), visibility_rule: 'always', height: 4, font_size: 14, tokens: ['certificado.modalidad'] },
                role: { name: 'Rol del participante', content: variable('participante.rol'), visibility_rule: 'always', height: 3, font_size: 11, tokens: ['participante.rol'] }
            };
        },
        hasAcademicField(field) {
            const definition = this.academicFields[field];
            return Boolean(definition && this.elements.some(element => element.data_field === field || definition.tokens.some(token => new RegExp('\\{\\{\\s*' + token.replaceAll('.', '\\.') + '\\s*\\}\\}').test(element.content || ''))));
        },
        academicFieldPosition(height) {
            const obstacles = this.elements.filter(element => element.is_visible !== false && !['watermark', 'background'].includes(element.type));
            for (const [x, width] of [[12, 76], [8, 40], [52, 40]]) {
                for (const [start, end] of [[54, 70], [36, 92]]) {
                    for (let y = start; y <= end; y += 1) {
                        if (!obstacles.some(element => x < Number(element.x) + Number(element.width) && x + width > Number(element.x) && y < Number(element.y) + Number(element.height || 3) + 0.4 && y + height + 0.4 > Number(element.y))) { return { x, y, width, height }; }
                    }
                }
            }
            return null;
        },
        addCertificateField(field) {
            const definition = this.academicFields[field];
            if (!definition) { return; }
            const existing = this.elements.find(element => element.data_field === field || definition.tokens.some(token => new RegExp('\\{\\{\\s*' + token.replaceAll('.', '\\.') + '\\s*\\}\\}').test(element.content || '')));
            if (existing) { this.selectElement(existing.id); this.openInspector(); return; }
            const position = this.academicFieldPosition(definition.height);
            if (!position) { this.previewNotice = 'No hay espacio libre para este bloque. Mueve o reduce un elemento antes de insertarlo.'; return; }
            this.insertElement(this.createElement('text', { ...position, data_field: field, name: definition.name, content: definition.content, visibility_rule: definition.visibility_rule, styles: { font_size: definition.font_size, font_weight: field === 'qualification' ? 'bold' : 'normal', text_align: 'center', text_color: this.schema.primary_color || this.schema.text_color } }));
        },
        addQrElement() {
            const element = this.createElement('qr', { name: 'CÓDIGO QR OFICIAL', x: 82, y: 72, ...this.qrDimensions(32), z_index: 25 });
            this.boundElement(element);
            this.insertElement(element);
            this.openInspector();
        },
        addSignatureBlock() {
            const asset = this.allAssets.find(item => (item.category || item.type) === 'signature');
            this.insertElement(this.createElement('signature', { name: 'BLOQUE DE FIRMA OFICIAL', x: 35, y: 74, width: 30, height: 16, z_index: 22, asset_id: asset?.id || null, asset_url: asset?.url || '', content: 'Ing. Aniceto Velarde Forest', subtitle: 'Vicedecano de Facultad' }));
        },
        addDecorativeLine() { this.insertElement(this.createElement('divider', { name: 'LÍNEA ORNAMENTAL', x: 25, y: 35, width: 50, height: 2, z_index: 15, opacity: 0.8, styles: { height: 2 } })); },
        addShape(preset) {
            const settings = {
                top: ['Franja superior', 0, 0, 100, 3, 1], bottom: ['Franja inferior', 0, 97, 100, 3, 1],
                side: ['Franja lateral', 0, 0, 4, 100, 1], rectangle: ['Rectángulo', 15, 38, 70, 20, .12],
                ellipse: ['Elipse', 40, 40, 18, 18 * this.canvasWidth / this.canvasHeight, .12]
            }[preset];
            if (!settings) { return; }
            const [name, x, y, width, height, opacity] = settings;
            const element = this.createElement('shape', { name, shape: preset === 'ellipse' ? 'ellipse' : 'rectangle', x, y, width, height, opacity, z_index: 2, styles: { fill: this.schema.primary_color || '#110b51', border_color: this.schema.secondary_color || '#efb43c', border_width: 0, border_radius: 0 } });
            this.commitChange(() => { this.schema.layout_version = 2; this.elements.push(element); this.selectedId = element.id; });
            this.activePanel = 'canvas';
        },
        insertAssetToCanvas(asset) {
            const category = asset.category || asset.type;
            const type = ({ logo: 'logo', watermark: 'watermark', stamp: 'stamp', background: 'background', signature: 'signature' })[category] || 'image';
            const watermark = type === 'watermark';
            const background = type === 'background';
            this.insertElement(this.createElement(type, { name: asset.name, x: background ? 0 : (watermark ? 25 : 8), y: background ? 0 : (watermark ? 25 : 8), width: background ? 100 : (watermark ? 50 : 12), height: background ? 100 : (watermark ? 50 : 12), z_index: background ? 0 : (watermark ? 2 : 25), opacity: watermark ? 0.08 : 1, asset_id: asset.id, asset_url: asset.url, styles: { object_fit: 'contain' } }));
        },
        deleteElement(id) {
            const element = this.elements.find(item => item.id === id);
            if (!element || element.is_locked || element.is_mandatory) { return; }
            this.commitChange(() => { this.elements = this.elements.filter(item => item.id !== id); if (this.selectedId === id) { this.selectedId = null; } });
        },
        duplicateElement(id) {
            const original = this.elements.find(item => item.id === id);
            if (!original || original.is_locked) { return; }
            const copy = { ...clone(original), id: `el_${Date.now()}_${++idSequence}`, name: `${original.name || original.type} (Copia)`, x: Number(original.x) + 2, y: Number(original.y) + 2, is_mandatory: false, is_locked: false };
            this.boundElement(copy);
            this.insertElement(copy);
        },
        toggleVisibility(id) {
            const element = this.elements.find(item => item.id === id);
            if (!element || element.is_locked) { return; }
            this.commitChange(() => { element.is_visible = element.is_visible === false; });
        },
        toggleLock(id) {
            const element = this.elements.find(item => item.id === id);
            if (element) { this.commitChange(() => { element.is_locked = !element.is_locked; }); }
        },
        alignElementCenter(axis) {
            const element = this.selectedElement;
            if (!element || element.is_locked) { return; }
            this.commitChange(() => { if (axis === 'x') { element.x = (100 - element.width) / 2; } else { element.y = (100 - element.height) / 2; } this.boundElement(element); });
        },
        bringToFront(id) {
            const element = this.elements.find(item => item.id === id);
            if (element && !element.is_locked) { this.commitChange(() => { element.z_index = Math.max(10, ...this.elements.map(item => Number(item.z_index) || 0)) + 1; }); }
        },
        sendToBack(id) {
            const element = this.elements.find(item => item.id === id);
            if (element && !element.is_locked) { this.commitChange(() => { element.z_index = Math.min(0, ...this.elements.map(item => Number(item.z_index) || 0)) - 1; }); }
        },
        changeFontSize(delta) {
            const element = this.selectedElement;
            if (!element || element.is_locked) { return; }
            element.styles.font_size = clamp(Number(element.styles.font_size || element.font_size || 12) + delta, 6, 72);
            element.font_size = element.styles.font_size;
            this.markDirty();
        },
        setTextAlign(align) {
            const element = this.selectedElement;
            if (!element || element.is_locked) { return; }
            element.styles.text_align = align;
            element.text_align = align;
            this.markDirty();
        },
        insertVariable(variable) {
            const element = this.selectedElement;
            if (!element || element.is_locked) { return; }
            element.content = [element.content?.trim(), variable].filter(Boolean).join(' ');
            this.markDirty();
        },
        toggleOrientation() {
            this.commitChange(() => { this.orientation = this.orientation === 'landscape' ? 'portrait' : 'landscape'; });
            if (this.autoFit) { this.nextRender(() => this.fitCanvas()); }
        },

        autoAdaptLayout(forceOrientation = null) {
            this.commitChange(() => {
                this.orientation = forceOrientation === 'portrait' || forceOrientation === 'landscape' ? forceOrientation : this.orientation;
                const portrait = this.orientation === 'portrait';
                const presets = this.layoutPresets[this.orientation];
                if (presets) {
                    this.schema.layout_version = 2;
                    const ids = { el_header_title: 'header', el_header_subtitle: 'faculty', el_header_department: 'department', el_header_preamble: 'preamble', el_preamble: 'preamble', el_resolution: 'resolution', el_award_title: 'award', el_award_intro: 'intro', el_beneficiary_intro: 'recipient_intro', el_beneficiary_name: 'beneficiary', el_beneficiary_doc: 'document', el_body_text: 'body', el_date_place: 'date', el_divider: 'divider', el_divider_signatures: 'signature_divider' };
                    this.elements.forEach(element => {
                        if (element.is_locked) { return; }
                        const role = element.layout_role || element.data_field || ids[element.id];
                        const layout = presets[role];
                        if (layout) {
                            [element.x, element.y, element.width, element.height] = layout;
                            Object.assign(element.styles, { font_size: layout[4], min_font_size: layout[5], fit_text: true, line_height: role === 'body' ? 1.45 : 1.2 });
                            if (role === 'beneficiary') { element.border_bottom = false; element.styles.border_bottom = false; }
                        } else if (this.isQrType(element.type)) {
                            Object.assign(element, portrait ? { x: 80, y: 82, width: 13, height: 12 } : { x: 83, y: 76, width: 11, height: 18 });
                        } else if (element.type === 'stamp') {
                            Object.assign(element, portrait ? { x: 64, y: 84, width: 12, height: 9 } : { x: 67, y: 80, width: 11, height: 15.6 });
                        }
                        this.boundElement(element);
                    });
                    const logos = this.elements.filter(element => element.type === 'logo' && !element.is_locked);
                    logos.forEach((element, index) => Object.assign(element, { x: 6 + index * (88 - (portrait ? 14 : 10)) / Math.max(1, logos.length - 1), y: 5, width: portrait ? 14 : 10, height: portrait ? 11 : 16 }));
                    const signatures = this.elements.filter(element => this.isSignatureType(element.type) && !element.is_locked);
                    const columns = Math.min(3, signatures.length);
                    const rows = Math.ceil(signatures.length / Math.max(1, columns));
                    signatures.forEach((element, index) => {
                        const width = (56 - 4 * (columns - 1)) / Math.max(1, columns);
                        const height = Math.min(portrait ? 10.5 : 13, 18 / rows);
                        Object.assign(element, { x: 8 + index % columns * (width + 4), y: (portrait ? 82.5 : 81) - (rows - 1) * height + Math.floor(index / columns) * height, width, height });
                    });
                    return;
                }
                const layouts = {
                    header_title: [15, 20, 70, 4, 13], header_subtitle: [15, 25, 70, 3.5, 11],
                    certificate_type: [10, 30, 80, 6, 23], beneficiary_name: [10, 37.5, 80, 6, 22],
                    beneficiary_document: [20, 44.5, 60, 3.5, 11], body_text: [12, 49, 76, 13, 11.5],
                    activity_dates: [15, 64, 70, 3.5, 10], metadata_code: [20, 96, 60, 2, 8]
                };
                this.elements.forEach(element => {
                    if (element.is_locked) { return; }
                    const layout = layouts[element.type];
                    if (layout) {
                        [element.x, element.y, element.width, element.height] = layout;
                        element.styles.font_size = layout[4];
                    } else if (this.isQrType(element.type)) {
                        Object.assign(element, { x: 80, y: 87, width: portrait ? 10 : 7, height: portrait ? 7 : 10 });
                    } else if (element.type === 'stamp') {
                        Object.assign(element, { x: 12, y: 87, width: portrait ? 12 : 8, height: portrait ? 8 : 11 });
                    } else if (element.type === 'watermark') {
                        Object.assign(element, { x: 25, y: 25, width: 50, height: 50, opacity: 0.07 });
                    } else if (this.isLineType(element.type)) {
                        Object.assign(element, { x: 20, y: 28, width: 60, height: 0.5 });
                    }
                    this.boundElement(element);
                });
                this.arrangeAssetGroup(this.elements.filter(element => element.type === 'logo' && !element.is_locked), { x: 8, y: 4, width: 84, height: 14 }, false);
                this.arrangeAssetGroup(this.elements.filter(element => this.isSignatureType(element.type) && !element.is_locked), { x: 8, y: 70, width: 84, height: 15 }, true);
            });
            if (this.autoFit) { this.nextRender(() => this.fitCanvas()); }
        },
        arrangeAssetGroup(group, region, signatures) {
            if (!group.length) { return; }
            const groupIds = new Set(group.map(element => element.id));
            const obstacles = this.elements.filter(element => !groupIds.has(element.id) && element.is_visible !== false && !['watermark', 'background'].includes(element.type) && !this.isLineType(element.type));
            const overlaps = (a, b) => a.x < Number(b.x) + Number(b.width) - 0.01 && a.x + a.width > Number(b.x) + 0.01 && a.y < Number(b.y) + Number(b.height) - 0.01 && a.y + a.height > Number(b.y) + 0.01;
            const ratio = signatures ? 2.2 * this.canvasHeight / this.canvasWidth : this.canvasHeight / this.canvasWidth;
            for (const area of [region, { x: 4, y: 4, width: 92, height: 90 }]) {
                for (let capacity = group.length; capacity <= group.length + 240; capacity += Math.max(1, Math.ceil(group.length / 2))) {
                    const columns = Math.min(capacity, Math.max(1, Math.ceil(Math.sqrt(capacity * area.width / (area.height * ratio)))));
                    const rows = Math.ceil(capacity / columns);
                    const gap = Math.min(2, area.width / columns / 4, area.height / rows / 4);
                    const cellWidth = (area.width - gap * (columns - 1)) / columns;
                    const cellHeight = (area.height - gap * (rows - 1)) / rows;
                    const width = Math.min(signatures ? 32 : 14, cellWidth);
                    const height = Math.min(signatures ? 15 : 13, cellHeight);
                    const slots = [];
                    for (let row = 0; row < rows; row++) {
                        for (let column = 0; column < columns; column++) {
                            const slot = { x: round(area.x + column * (cellWidth + gap) + (cellWidth - width) / 2), y: round(area.y + row * (cellHeight + gap) + (cellHeight - height) / 2), width: round(width), height: round(height) };
                            if (!obstacles.some(obstacle => overlaps(slot, obstacle))) { slots.push(slot); }
                        }
                    }
                    if (slots.length >= group.length) {
                        group.forEach((element, index) => Object.assign(element, slots[index]));
                        return;
                    }
                }
            }
            this.previewNotice = 'No hay espacio libre suficiente para adaptar todos los recursos sin mover elementos bloqueados.';
        },

        openVersionDialog() {
            if (this.isSaving) { return; }
            this.showVersionDialog = true;
            this.nextRender(() => (this.$refs?.versionSummary || this.$refs?.versionDialog)?.focus());
        },
        trapVersionFocus(event) {
            const dialog = this.$refs?.versionDialog;
            if (!this.showVersionDialog || !dialog || event.key !== 'Tab') { return; }
            const controls = [...dialog.querySelectorAll('a[href], button, input, textarea, select, [tabindex]')]
                .filter(element => !element.disabled && element.tabIndex >= 0 && element.getClientRects().length);
            const first = controls[0];
            const last = controls.at(-1);
            if (!first) { event.preventDefault(); dialog.focus(); return; }
            if (event.shiftKey && (document.activeElement === first || !dialog.contains(document.activeElement))) {
                event.preventDefault(); last.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !dialog.contains(document.activeElement))) {
                event.preventDefault(); first.focus();
            }
        },
        closeVersionDialog() {
            if (!this.showVersionDialog) { return; }
            this.showVersionDialog = false;
            this.nextRender(() => (document.getElementById('studio-version-trigger') || this.$refs?.versionButton)?.focus());
        },
        confirmLeave(event) {
            if (event?.ctrlKey || event?.metaKey || event?.shiftKey || (event?.button !== undefined && event.button !== 0)) { return true; }
            this.refreshDirtyState();
            if (this.isDirty && !this.isSaving && !window.confirm('Tiene cambios sin guardar. ¿Desea salir del diseñador y descartarlos?')) {
                event?.preventDefault();
                return false;
            }
            allowNavigation = true;
            return true;
        },
        previewPdf() {
            this.refreshDirtyState();
            if (this.isDirty) {
                this.previewNotice = 'Guarde los cambios antes de abrir el PDF. La vista PDF corresponde al último diseño guardado.';
                return false;
            }
            if (this.sampleError) { this.previewNotice = this.sampleError; return false; }
            if (!this.previewPdfUrl) { this.previewNotice = 'La vista previa PDF no está disponible para esta plantilla.'; return false; }
            this.previewNotice = '';
            let url = this.previewPdfUrl;
            if (this.selectedSampleType) {
                const parameters = { certificate_type_id: this.sampleTypeId, score: this.sampleSupportsScore ? (this.sampleData.score ?? '') : '', score_label: this.sampleSupportsScore ? this.sampleData.scoreLabel : '', validity_type: this.sampleData.validityType, issue_date: this.sampleIssueDate, expiration_date: this.sampleData.validityType === 'permanent' ? '' : this.sampleExpirationDate };
                url += (url.includes('?') ? '&' : '?') + Object.entries(parameters).map(([key, value]) => encodeURIComponent(key) + '=' + encodeURIComponent(value ?? '')).join('&');
            }
            window.open(url, '_blank', 'noopener,noreferrer');
            return true;
        },
        buildSchemaForSubmission() {
            const schema = clone(this.schema);
            schema.elements = clone(this.elements);
            for (const element of schema.elements) {
                for (const property of ['font_size', 'font_weight', 'font_style', 'text_align', 'text_color', 'font_family', 'line_height', 'letter_spacing', 'text_transform', 'object_fit']) {
                    if (element.styles?.[property] !== undefined) { delete element[property]; }
                }
            }
            schema.page = {
                ...(schema.page || {}),
                orientation: this.orientation,
                width_mm: this.orientation === 'portrait' ? 210 : 297,
                height_mm: this.orientation === 'portrait' ? 297 : 210
            };
            return schema;
        },
        saveTemplate(createNewVersion = false) {
            if (this.isSaving) { return false; }
            this.flushHistory();
            if (!String(this.name).trim() || this.name.length > 150 || !String(this.category).trim() || this.category.length > 60 || this.description.length > 255 || (createNewVersion && this.versionChangeSummary.length > 255)) {
                this.previewNotice = 'Revise el nombre, la categoría y la descripción. El resumen de versión admite hasta 255 caracteres.';
                return false;
            }
            const form = document.getElementById('studioForm');
            const schemaInput = document.getElementById('schemaJsonInput');
            const versionInput = document.getElementById('createNewVersionInput');
            const summaryInput = document.getElementById('changeSummaryInput');
            if (!form || !schemaInput || !versionInput || !summaryInput) {
                this.previewNotice = 'No se pudo preparar el formulario. Sus cambios siguen disponibles en el diseñador.';
                return false;
            }
            if (typeof form.reportValidity === 'function' && !form.reportValidity()) { return false; }
            schemaInput.value = JSON.stringify(this.buildSchemaForSubmission());
            versionInput.value = createNewVersion ? '1' : '0';
            summaryInput.value = createNewVersion ? this.versionChangeSummary.trim() : '';
            for (const property of ['name', 'category', 'orientation', 'description']) {
                const input = form.querySelector?.(`[name="${property}"]`);
                if (input) { input.value = this[property]; }
            }
            this.isSaving = true;
            this.previewNotice = '';
            try { form.submit(); }
            catch (error) { this.isSaving = false; this.previewNotice = 'No fue posible enviar el diseño. Intente guardarlo de nuevo.'; return false; }
            return true;
        }
    };
}
</script>
