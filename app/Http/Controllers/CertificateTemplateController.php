<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateTemplateVersion;
use App\Models\CertificateType;
use App\Models\InstitutionalAsset;
use App\Services\CertificateLayoutService;
use App\Services\CertificatePdfService;
use App\Services\QRCodeService;
use App\Services\TemplateEngineService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CertificateTemplateController extends Controller
{
    public function __construct(
        protected TemplateEngineService $templateEngine,
        protected CertificatePdfService $pdfService,
        protected CertificateLayoutService $layoutService
    ) {}

    public function index(Request $request)
    {
        $query = CertificateTemplate::with(['versions', 'createdBy'])->withCount('certificates');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        if ($orientation = $request->input('orientation')) {
            $query->where('orientation', $orientation);
        }

        $templates = $query->orderBy('is_default', 'desc')->orderBy('name')->paginate(9)->withQueryString();
        $categories = ['institucional', 'seminario', 'capacitacion', 'reconocimiento', 'disertante', 'aprobacion', 'academica'];

        return view('templates.index', compact('templates', 'categories'));
    }

    public function create()
    {
        $categories = ['institucional', 'seminario', 'capacitacion', 'reconocimiento', 'disertante', 'aprobacion', 'academica'];
        $initialTemplates = $this->templateEngine->getInitialTemplateDefinitions();
        $existingTemplates = CertificateTemplate::active()->select('id', 'name', 'category', 'orientation', 'current_version')->get();

        return view('templates.create', compact('categories', 'initialTemplates', 'existingTemplates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:60'],
            'orientation' => ['required', 'string', 'in:landscape,portrait'],
            'description' => ['nullable', 'string', 'max:255'],
            'creation_mode' => ['nullable', 'string', 'in:predefined,blank,duplicate'],
            'base_template_slug' => ['nullable', 'string'],
            'source_template_id' => ['nullable', 'exists:certificate_templates,id'],
        ]);

        $slug = Str::slug($validated['name']).'-'.Str::lower(Str::random(5));
        $mode = $validated['creation_mode'] ?? 'predefined';
        $configuration = [];
        $description = $validated['description'] ?? null;

        if ($mode === 'blank' || ($validated['base_template_slug'] ?? '') === 'blank') {
            $configuration = $this->templateEngine->createBlankSchema($validated['orientation']);
            $description = $description ?? 'Plantilla creada en lienzo en blanco';
        } elseif ($mode === 'duplicate' && ! empty($validated['source_template_id'])) {
            $source = CertificateTemplate::findOrFail($validated['source_template_id']);
            $configuration = $source->configuration ?? [];
            $description = $description ?? "Duplicada a partir de {$source->name}";
        } else {
            // Predefined base template
            $initialDefs = collect($this->templateEngine->getInitialTemplateDefinitions());
            $baseDef = $initialDefs->firstWhere('slug', $validated['base_template_slug'] ?? 'institucional-clasica')
                ?? $initialDefs->first();
            $configuration = $baseDef['configuration'] ?? [];
            $description = $description ?? ($baseDef['description'] ?? '');
        }

        // Always ensure normalized schema with elements
        $configuration = $this->layoutService->improve($this->templateEngine->normalizeSchema($configuration, $validated['orientation']), $validated['orientation']);

        $template = CertificateTemplate::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'category' => $validated['category'],
            'orientation' => $validated['orientation'],
            'description' => $description,
            'view_path' => 'certificates.templates.engine',
            'configuration' => $configuration,
            'current_version' => 1,
            'is_active' => true,
            'is_default' => false,
            'created_by_user_id' => $request->user()->id,
        ]);

        $template->versions()->create([
            'version_number' => 1,
            'name' => 'Versión 1.0 (Inicial)',
            'orientation' => $template->orientation,
            'design_schema' => $configuration,
            'change_summary' => 'Creación inicial de la plantilla',
            'created_by_user_id' => $request->user()->id,
        ]);

        AuditLog::log('template_created', "Plantilla '{$template->name}' creada con versión 1.0", $template);

        return redirect()->route('templates.designer', $template)->with('success', "Plantilla '{$template->name}' creada exitosamente. Ahora puede personalizar cada elemento visual.");
    }

    /**
     * Interfaz del Diseñador Visual de Certificados
     */
    public function designer(CertificateTemplate $template): View
    {
        $template->load(['versions.createdBy']);
        $logos = InstitutionalAsset::active()->ofType(InstitutionalAsset::TYPE_LOGO)->get();
        $signatures = InstitutionalAsset::active()->ofType(InstitutionalAsset::TYPE_SIGNATURE)->get();
        $stamps = InstitutionalAsset::active()->ofType(InstitutionalAsset::TYPE_STAMP)->get();
        $watermarks = InstitutionalAsset::active()->ofType(InstitutionalAsset::TYPE_WATERMARK)->get();
        $backgrounds = InstitutionalAsset::active()->ofType(InstitutionalAsset::TYPE_BACKGROUND)->get();
        $decoratives = InstitutionalAsset::active()->ofType(InstitutionalAsset::TYPE_DECORATIVE)->get();
        $allAssets = InstitutionalAsset::active()->get();

        $schema = $this->templateEngine->normalizeSchema($template->configuration ?? [], $template->orientation);
        $certificateTypes = CertificateType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'has_score', 'score_scale', 'min_passing_score', 'validity_mode', 'default_validity_years']);
        $layoutPresets = ['landscape' => $this->layoutService->presets('landscape'), 'portrait' => $this->layoutService->presets('portrait')];

        return view('templates.designer', compact('template', 'logos', 'signatures', 'stamps', 'watermarks', 'backgrounds', 'decoratives', 'allAssets', 'schema', 'certificateTypes', 'layoutPresets'));
    }

    /**
     * Guarda la configuración del diseñador y crea una nueva versión si se solicita
     */
    public function updateDesigner(Request $request, CertificateTemplate $template)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'category' => ['required', 'string', 'max:60'],
            'orientation' => ['required', 'string', 'in:landscape,portrait'],
            'description' => ['nullable', 'string', 'max:255'],
            'schema' => ['nullable', 'array'],
            'schema_json' => ['nullable', 'string'],
            'change_summary' => ['nullable', 'string', 'max:255'],
            'create_new_version' => ['nullable', 'boolean'],
        ]);

        $schema = [];
        if (! empty($validated['schema_json'])) {
            $schema = json_decode($validated['schema_json'], true) ?? [];
        } elseif (! empty($validated['schema'])) {
            $schema = $validated['schema'];
        }

        $schema = $this->templateEngine->normalizeSchema($schema, $validated['orientation']);
        $schema['layout_version'] = CertificateLayoutService::VERSION;

        $newVersionNumber = $template->current_version;
        if (! empty($validated['create_new_version'])) {
            $newVersionNumber = $template->current_version + 1;
        }

        $template->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'orientation' => $validated['orientation'],
            'description' => $validated['description'] ?? $template->description,
            'configuration' => $schema,
            'current_version' => $newVersionNumber,
        ]);

        // Registrar o actualizar versión
        CertificateTemplateVersion::updateOrCreate(
            [
                'certificate_template_id' => $template->id,
                'version_number' => $newVersionNumber,
            ],
            [
                'name' => "Versión {$newVersionNumber}.0",
                'orientation' => $template->orientation,
                'design_schema' => $schema,
                'change_summary' => $validated['change_summary'] ?? 'Actualización de diseño visual de la plantilla',
                'created_by_user_id' => $request->user()->id,
            ]
        );

        AuditLog::log(
            'template_updated',
            "Plantilla '{$template->name}' actualizada a versión {$newVersionNumber}.0",
            $template
        );

        return back()->with('success', "Diseño de la plantilla guardado exitosamente (Versión {$newVersionNumber}.0).");
    }

    /**
     * Duplicación de Plantilla
     */
    public function duplicate(Request $request, CertificateTemplate $template)
    {
        $newName = $request->input('name', "Copia de {$template->name}");
        $newTemplate = $template->duplicate($newName, $request->user());

        AuditLog::log(
            'template_duplicated',
            "Plantilla '{$template->name}' duplicada como '{$newTemplate->name}'",
            $newTemplate
        );

        return redirect()->route('templates.designer', $newTemplate)
            ->with('success', "Plantilla duplicada exitosamente como '{$newTemplate->name}'.");
    }

    /**
     * Alternar estado Activa/Inactiva
     */
    public function toggleStatus(CertificateTemplate $template)
    {
        $template->is_active = ! $template->is_active;
        $template->save();

        AuditLog::log(
            'template_updated',
            "Estado de plantilla '{$template->name}' cambiado a ".($template->is_active ? 'Activa' : 'Inactiva'),
            $template
        );

        return back()->with('success', 'Estado de la plantilla actualizado.');
    }

    /**
     * Establecer como predeterminada
     */
    public function setDefault(CertificateTemplate $template)
    {
        CertificateTemplate::where('id', '!=', $template->id)->update(['is_default' => false]);
        $template->is_default = true;
        $template->save();

        AuditLog::log(
            'template_updated',
            "Plantilla '{$template->name}' configurada como predeterminada institucional",
            $template
        );

        return back()->with('success', "La plantilla '{$template->name}' es ahora la predeterminada del sistema.");
    }

    /**
     * Eliminar plantilla (Solo si no tiene certificados emitidos)
     */
    public function destroy(CertificateTemplate $template)
    {
        if ($template->certificates()->count() > 0) {
            return back()->with('error', "No se puede eliminar la plantilla '{$template->name}' porque ya existen certificados institucionales emitidos con ella. Para no utilizarla en el futuro, puede desactivarla.");
        }

        $name = $template->name;
        $template->delete();

        AuditLog::log('template_deleted', "Plantilla '{$name}' eliminada del sistema", null);

        return redirect()->route('templates.index')->with('success', "Plantilla '{$name}' eliminada correctamente.");
    }

    /**
     * Redirige la visualización REST de plantilla al Diseñador Visual
     */
    public function show(CertificateTemplate $template)
    {
        return redirect()->route('templates.designer', $template);
    }

    /**
     * Redirige la edición REST de plantilla al Diseñador Visual
     */
    public function edit(CertificateTemplate $template)
    {
        return redirect()->route('templates.designer', $template);
    }

    /**
     * Genera la previsualización oficial en PDF de la plantilla con datos de prueba
     */
    public function previewPdf(Request $request, CertificateTemplate $template): Response
    {
        $sample = $request->validate([
            'certificate_type_id' => ['nullable', 'integer', 'exists:certificate_types,id'],
            'score' => ['nullable', 'numeric', 'between:0,100'],
            'score_label' => ['nullable', 'string', 'max:255'],
            'validity_type' => ['nullable', 'string', 'in:permanent,years,custom'],
            'issue_date' => ['nullable', 'date_format:Y-m-d'],
            'expiration_date' => ['required_if:validity_type,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:issue_date'],
        ]);
        $type = isset($sample['certificate_type_id'])
            ? CertificateType::where('is_active', true)->findOrFail($sample['certificate_type_id'])
            : (CertificateType::where('is_active', true)->orderBy('name')->first() ?? new CertificateType(['name' => 'Certificado', 'has_score' => false]));
        $issueDate = Carbon::parse($sample['issue_date'] ?? now()->toDateString());
        $validityType = $sample['validity_type'] ?? $type->validity_mode ?? CertificateType::VALIDITY_YEARS;
        if ($validityType === CertificateType::VALIDITY_CUSTOM && empty($sample['expiration_date'])) {
            throw ValidationException::withMessages(['expiration_date' => 'Indica la fecha de vencimiento de la muestra.']);
        }
        $expirationDate = match ($validityType) {
            CertificateType::VALIDITY_PERMANENT => null,
            CertificateType::VALIDITY_CUSTOM => $sample['expiration_date'] ?? null,
            default => $issueDate->copy()->addYears($type->default_validity_years ?? 3)->toDateString(),
        };
        $score = $type->has_score
            ? (array_key_exists('score', $sample) ? $sample['score'] : 92.5)
            : null;

        $beneficiary = new Beneficiary([
            'first_name' => 'Rodrigo',
            'last_name' => 'Quispe Mamani',
            'academic_title' => 'Univ.',
            'document_type' => 'CI',
            'document_number' => '8493021',
            'document_expedition' => 'LP',
            'email' => 'rodrigo.quispe@example.edu.bo',
        ]);
        $activity = new Activity([
            'name' => 'Seminario Internacional de Inteligencia Artificial y Ciencia de Datos',
            'activity_type' => 'Seminario',
            'start_date' => $issueDate->copy()->subDays(5),
            'end_date' => $issueDate,
            'academic_hours' => 40,
            'location' => 'Paraninfo Universitario',
            'responsible_authority' => 'Dr. Carlos Mendoza Fernández',
        ]);
        $dummyCertificate = new Certificate([
            'certificate_code' => 'DEMO-PREVIEW-2026',
            'verification_token' => 'sample-preview-token',
            'issue_date' => $issueDate->toDateString(),
            'expiration_date' => $expirationDate,
            'validity_type' => $validityType,
            'score' => $score,
            'score_label' => $type->has_score ? ($sample['score_label'] ?? null) : null,
            'certificate_template_id' => $template->id,
            'status' => Certificate::STATUS_VIGENTE,
        ]);
        $dummyCertificate->setRelation('beneficiary', $beneficiary);
        $dummyCertificate->setRelation('activity', $activity);
        $dummyCertificate->setRelation('certificateType', $type);
        $dummyCertificate->setRelation('certificateTemplate', $template);

        $pdf = $this->pdfService->render($dummyCertificate);

        return $pdf->stream("vista-previa-{$template->slug}.pdf");
    }

    /**
     * Previsualización instantánea en vivo de la plantilla con datos de muestra
     */
    public function livePreview(Request $request, CertificateTemplate $template)
    {
        $schema = $request->input('schema') ? json_decode($request->input('schema'), true) : ($template->configuration ?? []);
        $orientation = $request->input('orientation', $template->orientation ?? 'landscape');
        $schema = $this->templateEngine->normalizeSchema($schema, $orientation);

        $sampleCertificate = (object) $this->createSampleCertificateData($template, $schema);
        $qrService = app(QRCodeService::class);
        $qrBase64 = $qrService->generateBase64Svg(url('/verificar/DEMO-PREVIEW-2026'), 160);

        // Interpolar textos dinámicos en los elementos del esquema para previsualización fidedigna
        if (! empty($schema['elements']) && is_array($schema['elements'])) {
            $context = [
                'beneficiary' => [
                    'name' => $sampleCertificate->beneficiary_name,
                    'document' => $sampleCertificate->beneficiary_document,
                ],
                'activity' => [
                    'name' => $sampleCertificate->activity_name,
                    'hours' => $sampleCertificate->academic_hours,
                    'dates' => $sampleCertificate->date_range,
                    'type' => $sampleCertificate->activity_type,
                    'location' => $sampleCertificate->location,
                    'authority' => $sampleCertificate->authority_name,
                ],
                'certificate' => [
                    'code' => $sampleCertificate->certificate_code,
                    'issue_date' => $sampleCertificate->issue_date,
                    'expiration_date' => $sampleCertificate->expiration_date,
                    'type' => $sampleCertificate->certificate_type,
                ],
            ];

            foreach ($schema['elements'] as &$el) {
                if (($el['type'] ?? '') === 'text' && ! empty($el['content'])) {
                    $el['resolved_content'] = $this->templateEngine->interpolate($el['content'], $context);
                }
            }
            unset($el);
        }

        return view('certificates.templates.preview_canvas', [
            'template' => $template,
            'schema' => $schema,
            'certificate' => $sampleCertificate,
            'orientation' => $orientation,
            'qr_base64' => $qrBase64,
        ]);
    }

    /**
     * Genera datos simulados ricos para previsualización fidedigna
     */
    protected function createSampleCertificateData(CertificateTemplate $template, array $schema): array
    {
        return [
            'certificate_code' => 'CERT-2026-000042',
            'issue_date' => now()->translatedFormat('d \d\e F \d\e Y'),
            'expiration_date' => now()->addYears(3)->translatedFormat('d \d\e F \d\e Y'),
            'beneficiary_name' => 'Univ. Rodrigo Quispe Mamani',
            'beneficiary_document' => 'CI: 8493021 LP',
            'activity_name' => 'Seminario Internacional de Inteligencia Artificial y Ciencia de Datos',
            'activity_type' => 'Seminario',
            'academic_hours' => 40,
            'date_range' => 'Del 15 al 20 de Septiembre de 2026',
            'location' => 'Paraninfo Universitario - La Paz, Bolivia',
            'certificate_type' => 'ASISTENCIA Y APROBACIÓN',
            'authority_name' => 'Dr. Carlos Mendoza Fernández',
            'authority_position' => 'Vicedecano de Facultad',
        ];
    }
}
