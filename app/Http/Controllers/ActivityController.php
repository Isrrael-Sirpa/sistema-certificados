<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityCertificateConfig;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\InstitutionalAsset;
use App\Models\OrganizationalUnit;
use App\Services\BeneficiaryImportService;
use App\Services\CertificatePdfService;
use App\Services\CertificateService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use ZipArchive;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Activity::with(['organizationalUnit', 'allowedUnits'])->withCount('certificates');

        if ($user->isUnitScoped()) {
            $query->where('organizational_unit_id', $user->organizational_unit_id);
        } elseif ($request->filled('unit_id')) {
            $query->where('organizational_unit_id', $request->input('unit_id'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%")
                    ->orWhere('resolution_number', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('activity_type')) {
            $query->where('activity_type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $activities = $query->orderBy('start_date', 'desc')->paginate(12)->withQueryString();
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();

        return view('activities.index', compact('activities', 'units'));
    }

    public function create(Request $request)
    {
        if (! $request->user()->isUnitActive()) {
            abort(403, 'Su carrera o departamento se encuentra desactivado temporalmente. No es posible crear nuevas actividades.');
        }

        $types = CertificateType::where('is_active', true)->get();
        $templates = CertificateTemplate::select(['id', 'name', 'category', 'orientation', 'organizational_unit_id', 'current_version', 'is_default'])
            ->where('is_active', true)
            ->when($request->user()->isUnitScoped(), fn ($query) => $query->forUnit($request->user()->organizational_unit_id))
            ->with('organizationalUnit:id,name')->orderBy('id')->get();
        $designs = CertificateTemplate::whereKey($templates->modelKeys())->get(['id', 'configuration'])->keyBy('id');
        foreach ($templates as $template) {
            $template->setAttribute('configuration', $designs->get($template->id)?->configuration ?? []);
        }
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();

        return view('activities.create', compact('types', 'templates', 'units'));
    }

    public function store(Request $request)
    {
        if (! $request->user()->isUnitActive()) {
            abort(403, 'Su carrera o departamento se encuentra desactivado temporalmente. No es posible registrar nuevas actividades.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity_type' => ['required', 'string', 'max:80'],
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'academic_hours' => ['required', 'integer', 'min:0', 'max:1000'],
            'resolution_number' => ['nullable', 'string', 'max:100'],
            'target_audience_mode' => ['nullable', 'string', 'in:own_unit,all_faculty,specific_units'],
            'allowed_unit_ids' => ['nullable', 'array'],
            'allowed_unit_ids.*' => ['exists:organizational_units,id'],
            'organizational_unit_id' => ['nullable', 'exists:organizational_units,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'responsible_authority' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:activa,finalizada,cancelada'],

            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'validity_mode' => ['nullable', 'string', 'in:permanent,temporal,custom'],
            'validity_years' => ['nullable', 'integer', 'min:1', 'max:50'],
            'show_score_on_pdf' => ['nullable', 'boolean'],
            'show_score_on_portal' => ['nullable', 'boolean'],
        ]);

        $activityData = collect($validated)->only([
            'name', 'description', 'activity_type', 'certificate_type_id',
            'start_date', 'end_date', 'academic_hours', 'resolution_number',
            'location', 'organizer', 'responsible_authority', 'status',
        ])->toArray();

        if ($request->user()->isUnitScoped()) {
            $activityData['organizational_unit_id'] = $request->user()->organizational_unit_id;
            $activityData['target_audience_mode'] = $validated['target_audience_mode'] ?? Activity::AUDIENCE_OWN_UNIT;
        } else {
            $activityData['organizational_unit_id'] = $validated['organizational_unit_id'] ?? $request->user()->organizational_unit_id;
            $activityData['target_audience_mode'] = $validated['target_audience_mode'] ?? (
                ! empty($activityData['organizational_unit_id']) ? Activity::AUDIENCE_OWN_UNIT : Activity::AUDIENCE_ALL_FACULTY
            );
        }

        $activity = Activity::create($activityData);

        if ($activity->target_audience_mode === Activity::AUDIENCE_SPECIFIC_UNITS && ! empty($validated['allowed_unit_ids'])) {
            $activity->allowedUnits()->sync($validated['allowed_unit_ids']);
        }

        if ($activity->certificate_type_id || ! empty($validated['certificate_template_id']) || ! empty($validated['validity_mode'])) {
            $certType = $activity->certificateType;
            $validityMode = $validated['validity_mode'] ?? ($certType?->validity_mode ?? 'temporal');
            $validityYears = isset($validated['validity_years'])
                ? (int) $validated['validity_years']
                : ($certType?->default_validity_years ?? 3);

            ActivityCertificateConfig::create([
                'activity_id' => $activity->id,
                'certificate_template_id' => $validated['certificate_template_id'] ?? null,
                'validity_mode' => $validityMode,
                'validity_years' => $validityYears,
                'show_score_on_pdf' => $request->boolean('show_score_on_pdf', true),
                'show_score_on_portal' => $request->boolean('show_score_on_portal', true),
            ]);
        }

        AuditLog::log('activity_created', "Actividad '{$activity->name}' registrada", $activity);

        return redirect()->route('activities.show', $activity)->with('success', 'Actividad registrada exitosamente.');
    }

    public function show(Request $request, Activity $activity)
    {
        if ($request->user()->isUnitScoped() && $activity->organizational_unit_id !== $request->user()->organizational_unit_id) {
            abort(403, 'No tiene autorización para visualizar actividades de otra carrera o unidad.');
        }

        $activity->load([
            'organizationalUnit',
            'allowedUnits',
            'certificateConfig.template',
            'certificates' => function ($q) {
                $q->forListing()->with(['beneficiary', 'certificateType', 'certificateTemplate:id,name', 'organizationalUnit'])->latest('id');
            },
            'participants' => function ($q) {
                $q->with('primaryOrganizationalUnit')->orderBy('last_name')->orderBy('first_name');
            },
        ]);

        $participants = $activity->participants;
        $issuedCertificatesByBenId = $activity->certificates
            ->whereNull('revoked_at')
            ->keyBy('beneficiary_id');

        $beneficiaries = Beneficiary::with('primaryOrganizationalUnit')->orderBy('last_name')->orderBy('first_name')->get();
        $types = CertificateType::where('is_active', true)->get();
        $templates = CertificateTemplate::where('is_active', true)
            ->forUnit($activity->organizational_unit_id)
            ->get();
        $config = $activity->certificateConfig;
        $signatures = InstitutionalAsset::active()->ofType(InstitutionalAsset::TYPE_SIGNATURE)->get();
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();

        return view('activities.show', compact('activity', 'participants', 'issuedCertificatesByBenId', 'beneficiaries', 'types', 'templates', 'config', 'signatures', 'units'));
    }

    public function edit(Request $request, Activity $activity)
    {
        if ($request->user()->isUnitScoped() && $activity->organizational_unit_id !== $request->user()->organizational_unit_id) {
            abort(403, 'No tiene autorización para editar actividades de otra carrera o unidad.');
        }

        $types = CertificateType::where('is_active', true)->get();
        $templates = CertificateTemplate::where('is_active', true)
            ->forUnit($activity->organizational_unit_id)
            ->get();
        $config = $activity->certificateConfig;
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();

        return view('activities.edit', compact('activity', 'types', 'templates', 'config', 'units'));
    }

    public function update(Request $request, Activity $activity)
    {
        if ($request->user()->isUnitScoped() && $activity->organizational_unit_id !== $request->user()->organizational_unit_id) {
            abort(403, 'No tiene autorización para modificar actividades de otra carrera o unidad.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity_type' => ['required', 'string', 'max:80'],
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'academic_hours' => ['required', 'integer', 'min:0', 'max:1000'],
            'resolution_number' => ['nullable', 'string', 'max:100'],
            'target_audience_mode' => ['nullable', 'string', 'in:own_unit,all_faculty,specific_units'],
            'allowed_unit_ids' => ['nullable', 'array'],
            'allowed_unit_ids.*' => ['exists:organizational_units,id'],
            'organizational_unit_id' => ['nullable', 'exists:organizational_units,id'],
            'location' => ['nullable', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'responsible_authority' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:activa,finalizada,cancelada'],

            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'validity_mode' => ['nullable', 'string', 'in:permanent,temporal,custom'],
            'validity_years' => ['nullable', 'integer', 'min:1', 'max:50'],
            'show_score_on_pdf' => ['nullable', 'boolean'],
            'show_score_on_portal' => ['nullable', 'boolean'],
        ]);

        $old = $activity->toArray();
        $activityData = collect($validated)->only([
            'name', 'description', 'activity_type', 'certificate_type_id',
            'start_date', 'end_date', 'academic_hours', 'resolution_number',
            'target_audience_mode', 'location', 'organizer', 'responsible_authority', 'status',
        ])->toArray();

        if ($request->user()->isAdmin() && ! empty($validated['organizational_unit_id'])) {
            $activityData['organizational_unit_id'] = $validated['organizational_unit_id'];
        }

        $activity->update($activityData);

        if (array_key_exists('resolution_number', $validated) && $activity->certificateConfig) {
            $customFields = $activity->certificateConfig->custom_fields_schema ?? [];
            $customFields['resolucion'] = $validated['resolution_number'];
            $activity->certificateConfig->update(['custom_fields_schema' => $customFields]);
        }

        if ($activity->target_audience_mode === Activity::AUDIENCE_SPECIFIC_UNITS) {
            $activity->allowedUnits()->sync($validated['allowed_unit_ids'] ?? []);
        } else {
            $activity->allowedUnits()->detach();
        }

        if ($request->has('validity_mode') || $request->has('certificate_template_id')) {
            $certType = $activity->certificateType;
            ActivityCertificateConfig::updateOrCreate(
                ['activity_id' => $activity->id],
                [
                    'certificate_template_id' => $validated['certificate_template_id'] ?? null,
                    'validity_mode' => $validated['validity_mode'] ?? ($certType?->validity_mode ?? 'temporal'),
                    'validity_years' => isset($validated['validity_years']) ? (int) $validated['validity_years'] : ($certType?->default_validity_years ?? 3),
                    'show_score_on_pdf' => $request->boolean('show_score_on_pdf', true),
                    'show_score_on_portal' => $request->boolean('show_score_on_portal', true),
                ]
            );
        }

        $activity->refreshParticipantEligibility();

        AuditLog::log('activity_updated', "Actividad '{$activity->name}' modificada", $activity, $old, $activity->toArray());

        return redirect()->route('activities.show', $activity)->with('success', 'Actividad actualizada.');
    }

    public function destroy(Request $request, Activity $activity)
    {
        if ($request->user()->isUnitScoped() && $activity->organizational_unit_id !== $request->user()->organizational_unit_id) {
            abort(403, 'No tiene autorización para eliminar actividades de otra carrera o unidad.');
        }
        if ($activity->certificates()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una actividad que ya posee certificados emitidos.');
        }

        $activity->delete();
        AuditLog::log('activity_deleted', "Actividad '{$activity->name}' eliminada", $activity);

        return redirect()->route('activities.index')->with('success', 'Actividad eliminada correctamente.');
    }

    /**
     * Guarda la personalización de certificado exclusiva para esta actividad.
     */
    public function updateCertificateConfig(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'validity_mode' => ['nullable', 'string', 'in:permanent,temporal,custom'],
            'validity_years' => ['nullable', 'integer', 'min:1', 'max:50'],
            'show_score_on_pdf' => ['nullable', 'boolean'],
            'show_score_on_portal' => ['nullable', 'boolean'],
            'custom_title' => ['nullable', 'string', 'max:255'],
            'custom_body_text' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'resolution_number' => ['nullable', 'string', 'max:100'],
            'modality' => ['nullable', 'string', 'max:100'],
        ]);

        if (array_key_exists('certificate_type_id', $validated)) {
            $activity->update(['certificate_type_id' => $validated['certificate_type_id']]);
        }

        $customFields = $activity->certificateConfig?->custom_fields_schema ?? [];
        if (array_key_exists('resolution_number', $validated)) {
            $customFields['resolucion'] = $validated['resolution_number'];
            $activity->update(['resolution_number' => $validated['resolution_number']]);
        }
        if (! empty($validated['modality'])) {
            $customFields['modalidad'] = $validated['modality'];
        }

        $certType = $activity->fresh()->certificateType;
        $validityMode = $validated['validity_mode'] ?? ($activity->certificateConfig?->validity_mode ?? ($certType?->validity_mode ?? 'temporal'));
        $validityYears = isset($validated['validity_years'])
            ? (int) $validated['validity_years']
            : ($activity->certificateConfig?->validity_years ?? ($certType?->default_validity_years ?? 3));

        ActivityCertificateConfig::updateOrCreate(
            ['activity_id' => $activity->id],
            [
                'certificate_template_id' => $validated['certificate_template_id'] ?? null,
                'validity_mode' => $validityMode,
                'validity_years' => $validityYears,
                'show_score_on_pdf' => $request->boolean('show_score_on_pdf', true),
                'show_score_on_portal' => $request->boolean('show_score_on_portal', true),
                'custom_title' => $validated['custom_title'] ?? null,
                'custom_body_text' => $validated['custom_body_text'] ?? null,
                'primary_color' => $validated['primary_color'] ?? null,
                'secondary_color' => $validated['secondary_color'] ?? null,
                'custom_fields_schema' => $customFields,
            ]
        );

        AuditLog::log(
            'activity_config_updated',
            "Personalización de certificado configurada para la actividad '{$activity->name}'",
            $activity
        );

        return redirect()->route('activities.show', $activity)->with('success', "Configuración de certificado para '{$activity->name}' guardada exitosamente.");
    }

    /**
     * Emisión masiva de certificados para múltiples beneficiarios seleccionados
     */
    public function batchIssue(Request $request, Activity $activity, CertificateService $certificateService)
    {
        $validated = $request->validate([
            'beneficiary_ids' => ['required', 'array', 'min:1'],
            'beneficiary_ids.*' => ['exists:beneficiaries,id'],
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'issue_date' => ['required', 'date'],
        ]);

        $typeId = $validated['certificate_type_id']
            ?? $activity->certificate_type_id
            ?? CertificateType::first()?->id;

        if (! $typeId) {
            return back()->with('error', 'Debe seleccionar o configurar un tipo de certificado para emitir.');
        }

        $templateId = $validated['certificate_template_id']
            ?? $activity->certificateConfig?->certificate_template_id
            ?? null;

        $certType = CertificateType::find($typeId);
        $hasScoreRule = $certType && $certType->supportsScore();
        $minScore = (float) ($certType?->min_passing_score ?? 51.0);

        $participants = $activity->participants()
            ->whereIn('beneficiaries.id', $validated['beneficiary_ids'])
            ->get()
            ->keyBy('id');

        $count = 0;
        $skipped = 0;

        foreach ($validated['beneficiary_ids'] as $beneficiaryId) {
            $alreadyExists = $activity->certificates()
                ->where('beneficiary_id', $beneficiaryId)
                ->where('certificate_type_id', $typeId)
                ->whereNull('revoked_at')
                ->exists();

            if ($alreadyExists) {
                $skipped++;

                continue;
            }

            $participant = $participants->get($beneficiaryId);
            $pivotScore = ($participant && $participant->pivot && $participant->pivot->score !== null)
                ? (float) $participant->pivot->score
                : null;
            $pivotScoreLabel = $participant?->pivot?->score_label;

            // Omitir reprobados o sin nota cuando el tipo exige puntaje
            if ($hasScoreRule) {
                if ($pivotScore === null || $pivotScore < $minScore) {
                    $skipped++;

                    continue;
                }
            }

            // Restricción de carrera/resolución
            if ($participant && ! $activity->isCareerAllowed($participant->primary_organizational_unit_id)) {
                $skipped++;

                continue;
            }

            $certificateService->issueCertificate([
                'beneficiary_id' => $beneficiaryId,
                'activity_id' => $activity->id,
                'certificate_type_id' => $typeId,
                'certificate_template_id' => $templateId,
                'issue_date' => $validated['issue_date'],
                'score' => $pivotScore,
                'score_label' => $pivotScoreLabel,
            ], $request->user());

            $count++;
        }

        if ($count === 0) {
            $msg = 'No se emitió ningún certificado nuevo.';
            if ($skipped > 0) {
                $msg .= " ({$skipped} omitidos por ya poseer certificado activo, notas no aprobatorias o carrera no autorizada).";
            }

            return back()->with('warning', $msg);
        }

        $msg = "Se generaron exitosamente {$count} certificado(s).";
        if ($skipped > 0) {
            $msg .= " ({$skipped} omitidos por ya poseer certificado activo o no cumplir requisitos).";
        }

        return back()->with('success', $msg);
    }

    /**
     * Analiza y genera un diagnóstico previo de la nómina Excel/CSV para esta actividad académica.
     */
    public function previewRoster(Request $request, Activity $activity, BeneficiaryImportService $importService)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
        ], [
            'file.required' => 'Debe adjuntar una nómina en formato Excel (.xlsx) o CSV.',
            'file.max' => 'El tamaño máximo de archivo es 10 MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'csv', 'txt'])) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Formato no soportado. Suba un archivo .xlsx o .csv.'], 422);
            }

            return back()->with('error', 'Formato no soportado. Suba un archivo .xlsx o .csv.');
        }

        try {
            $typeId = $request->input('certificate_type_id') ? (int) $request->input('certificate_type_id') : null;
            $preview = $importService->parseAndPreviewForActivity($file, $activity, $typeId);

            session(["activity_roster_preview_{$activity->id}" => $preview]);

            if ($request->wantsJson()) {
                return response()->json($preview);
            }

            return back()->with('success', 'Nómina analizada exitosamente. Revise el diagnóstico.');
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', 'Error al procesar la nómina: '.$e->getMessage());
        }
    }

    /**
     * Confirma la emisión masiva de certificados para la nómina analizada.
     * Registra a los nuevos estudiantes en el padrón y reutiliza a los existentes.
     */
    public function confirmRosterIssuance(Request $request, Activity $activity, BeneficiaryImportService $importService, CertificateService $certificateService)
    {
        $validated = $request->validate([
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'issue_date' => ['required', 'date'],
        ]);

        $typeId = $validated['certificate_type_id']
            ?? $activity->certificate_type_id
            ?? CertificateType::first()?->id;

        if (! $typeId) {
            return back()->with('error', 'Debe seleccionar o configurar un tipo de certificado para emitir.');
        }

        $preview = session("activity_roster_preview_{$activity->id}");
        $rows = $request->input('rows') ?? ($preview['rows'] ?? []);

        if (empty($rows)) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'No hay datos de nómina cargados para procesar. Suba un archivo primero.'], 422);
            }

            return back()->with('error', 'No hay datos de nómina cargados para procesar. Suba un archivo primero.');
        }

        $templateId = $validated['certificate_template_id']
            ?? $activity->certificateConfig?->certificate_template_id
            ?? null;

        try {
            $result = $importService->executeActivityBulkIssuance(
                $rows,
                $activity,
                (int) $typeId,
                $templateId ? (int) $templateId : null,
                $validated['issue_date'],
                $request->user(),
                $certificateService
            );

            session()->forget("activity_roster_preview_{$activity->id}");

            $msg = $this->issuanceSummary($result, (int) $typeId);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'result' => $result,
                ]);
            }

            return redirect()->route('activities.show', $activity)->with($result['issued_count'] > 0 ? 'success' : 'warning', $msg);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 422);
            }

            return back()->with('error', 'Error al emitir los certificados: '.$e->getMessage());
        }
    }

    /**
     * Genera y descarga todos los certificados vigentes de esta actividad en un archivo ZIP.
     */
    public function downloadZip(Activity $activity, CertificatePdfService $pdfService)
    {
        $certificates = $activity->certificates()
            ->whereNull('revoked_at')
            ->with(['beneficiary', 'certificateType', 'certificateTemplate'])
            ->get();

        if ($certificates->isEmpty()) {
            return back()->with('error', 'No existen certificados vigentes emitidos para esta actividad.');
        }

        $tempDir = storage_path('app/temp');
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $slugName = Str::slug($activity->name);
        $zipFileName = "certificados_{$slugName}_".now()->format('Ymd_His').'.zip';
        $zipFilePath = $tempDir.DIRECTORY_SEPARATOR.$zipFileName;

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo comprimido ZIP.');
        }

        $count = 0;
        foreach ($certificates as $cert) {
            try {
                $pdfBinary = $pdfService->render($cert)->output();
                $cleanBenName = Str::slug($cert->beneficiary->full_name);
                $doc = $cert->beneficiary->document_number;
                $pdfFileName = "{$cert->certificate_code}_{$cleanBenName}_{$doc}.pdf";

                $zip->addFromString($pdfFileName, $pdfBinary);
                $count++;
            } catch (\Throwable $e) {
                continue;
            }
        }

        $zip->close();

        if (! file_exists($zipFilePath) || filesize($zipFilePath) === 0 || $count === 0) {
            return back()->with('error', 'No se pudieron empaquetar los certificados en el archivo ZIP.');
        }

        return response()->download($zipFilePath, $zipFileName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Carga y registra masivamente la nómina de participantes desde un archivo Excel o CSV
     * sin emitir certificados aún.
     */
    public function enrollRoster(Request $request, Activity $activity, BeneficiaryImportService $importService)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ], [
            'file.required' => 'Debe adjuntar una nómina en formato Excel (.xlsx) o CSV.',
            'file.max' => 'El tamaño máximo de archivo es 10 MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, ['xlsx', 'csv', 'txt'])) {
            return back()->with('error', 'Formato no soportado. Suba un archivo .xlsx o .csv.');
        }

        try {
            $preview = $importService->parseAndPreviewForActivity($file, $activity);

            if (empty($preview['rows'])) {
                return back()->with('error', 'El archivo no contiene registros válidos para procesar.');
            }

            $result = $importService->enrollParticipantsFromRows($preview['rows'], $activity, $request->user());

            $msg = "Nómina cargada exitosamente: {$result['enrolled_count']} participante(s) agregado(s) a este evento ({$result['new_students_count']} nuevo(s) registrado(s) en el padrón).";
            if ($result['already_enrolled_count'] > 0) {
                $msg .= " ({$result['already_enrolled_count']} ya estaban registrados en la lista).";
            }
            if (($result['locked_count'] ?? 0) > 0) {
                $msg .= " Se conservaron sin cambios {$result['locked_count']} calificación(es) con certificado emitido.";
            }

            return redirect()->route('activities.show', $activity)->with('success', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al procesar la nómina: '.$e->getMessage());
        }
    }

    /**
     * Agrega manualmente a un beneficiario existente del padrón a la nómina de la actividad.
     */
    public function addParticipantManual(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'beneficiary_id' => ['required', 'exists:beneficiaries,id'],
            'role' => ['nullable', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'score_label' => ['nullable', 'string', 'max:100'],
        ]);

        $beneficiaryId = (int) $validated['beneficiary_id'];
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);

        if ($activity->participants()->where('beneficiaries.id', $beneficiaryId)->exists()) {
            return redirect()->route('activities.show', $activity)->with('warning', 'El estudiante ya se encuentra registrado en la nómina de esta actividad.');
        }

        $hasCert = $activity->certificates()
            ->where('beneficiary_id', $beneficiaryId)
            ->exists();

        if ($hasCert) {
            return back()->with('error', 'Este participante ya tiene un certificado emitido. Su calificación está bloqueada.');
        }

        $certType = $activity->certificateType;
        $hasScoreRule = $certType && $certType->supportsScore();
        $minScore = (float) ($certType?->min_passing_score ?? 51.0);
        $score = isset($validated['score']) && $validated['score'] !== '' ? (float) $validated['score'] : null;

        $isAllowed = $activity->isCareerAllowed($beneficiary->primary_organizational_unit_id);
        $resText = $activity->resolution_number ? "según Resolución {$activity->resolution_number}" : 'por restricción de unidad';
        $ineligibilityReason = $isAllowed ? null : "Carrera no autorizada ({$resText})";

        $participantStatus = ! $isAllowed ? 'observado' : ($hasCert ? 'certificado' : (($hasScoreRule && $score !== null && $score < $minScore) ? 'reprobado' : 'inscrito'));

        $activity->participants()->attach($beneficiaryId, [
            'role' => $validated['role'] ?? 'Participante',
            'score' => isset($validated['score']) && $validated['score'] !== '' ? (float) $validated['score'] : null,
            'score' => $score,
            'score_label' => $validated['score_label'] ?? null,
            'status' => $hasCert ? 'certificado' : 'inscrito',
            'career_eligible' => $isAllowed,
            'ineligibility_reason' => $ineligibilityReason,
            'status' => $participantStatus,
        ]);

        $notice = $isAllowed
            ? 'Participante agregado exitosamente a la nómina del evento.'
            : "Participante registrado como observado: {$ineligibilityReason}.";

        return redirect()->route('activities.show', $activity)->with($isAllowed ? 'success' : 'warning', $notice);
    }

    /**
     * Actualiza la información académica de un participante en la nómina (nota, mención, rol).
     */
    public function updateParticipant(Request $request, Activity $activity, Beneficiary $beneficiary)
    {
        abort_unless($activity->participants()->where('beneficiaries.id', $beneficiary->id)->exists(), 404);
        if ($activity->certificates()->where('beneficiary_id', $beneficiary->id)->exists()) {
            return back()->with('error', 'La calificación está bloqueada porque ya se emitió un certificado para este participante.');
        }

        $validated = $request->validate([
            'role' => ['nullable', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'score_label' => ['nullable', 'string', 'max:100'],
        ]);

        $certType = $activity->certificateType;
        $hasScoreRule = $certType && $certType->supportsScore();
        $minScore = (float) ($certType?->min_passing_score ?? 51.0);
        $score = isset($validated['score']) && $validated['score'] !== '' ? (float) $validated['score'] : null;

        $hasCert = $activity->certificates()
            ->where('beneficiary_id', $beneficiary->id)
            ->whereNull('revoked_at')
            ->exists();

        $isAllowed = $activity->isCareerAllowed($beneficiary->primary_organizational_unit_id);
        $resText = $activity->resolution_number ? "según Resolución {$activity->resolution_number}" : 'por restricción de unidad';
        $ineligibilityReason = $isAllowed ? null : "Carrera no autorizada ({$resText})";

        $updateData = [
            'role' => $validated['role'] ?? 'Participante',
            'score' => $score,
            'score_label' => $validated['score_label'] ?? null,
            'career_eligible' => $isAllowed,
            'ineligibility_reason' => $ineligibilityReason,
        ];

        if (! $hasCert) {
            $updateData['status'] = ! $isAllowed ? 'observado' : (($hasScoreRule && $score !== null && $score < $minScore) ? 'reprobado' : 'inscrito');
        }

        $activity->participants()->updateExistingPivot($beneficiary->id, $updateData);

        return redirect()->route('activities.show', $activity)->with('success', "Datos de {$beneficiary->full_name} actualizados en la nómina.");
    }

    /**
     * Quita a un participante de la nómina de la actividad (siempre que no tenga certificado emitido).
     */
    public function removeParticipant(Activity $activity, Beneficiary $beneficiary)
    {
        $hasCert = $activity->certificates()
            ->where('beneficiary_id', $beneficiary->id)
            ->whereNull('revoked_at')
            ->exists();

        if ($hasCert) {
            return redirect()->route('activities.show', $activity)->with('error', 'No se puede remover de la lista a un participante que ya tiene un certificado emitido.');
        }

        $activity->participants()->detach($beneficiary->id);

        return redirect()->route('activities.show', $activity)->with('success', "Participante {$beneficiary->full_name} removido de la nómina de esta actividad.");
    }

    /**
     * Emite masivamente los certificados para todos los participantes registrados en la nómina de la actividad.
     * Emite masivamente los certificados para todos los participantes registrados en la nómina de la actividad,
     * omitiendo automáticamente a reprobados o sin calificación.
     */
    public function issueToRoster(Request $request, Activity $activity, BeneficiaryImportService $importService, CertificateService $certificateService)
    {
        $validated = $request->validate([
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'issue_date' => ['required', 'date'],
            'beneficiary_ids' => ['nullable', 'array'],
            'beneficiary_ids.*' => ['exists:beneficiaries,id'],
        ]);

        $typeId = $validated['certificate_type_id']
            ?? $activity->certificate_type_id
            ?? CertificateType::first()?->id;

        if (! $typeId) {
            return back()->with('error', 'Debe seleccionar o configurar un tipo de certificado para emitir.');
        }

        $templateId = $validated['certificate_template_id']
            ?? $activity->certificateConfig?->certificate_template_id
            ?? null;

        try {
            $result = $importService->issueCertificatesToActivityParticipants(
                $activity,
                (int) $typeId,
                $templateId ? (int) $templateId : null,
                $validated['issue_date'],
                $request->user(),
                $certificateService,
                $validated['beneficiary_ids'] ?? null
            );

            $msg = $this->issuanceSummary($result, (int) $typeId);

            return redirect()->route('activities.show', $activity)->with($result['issued_count'] > 0 ? 'success' : 'warning', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al emitir los certificados: '.$e->getMessage());
        }
    }

    /** @param array<string, int> $result */
    private function issuanceSummary(array $result, int $typeId): string
    {
        $message = $result['issued_count'] > 0
            ? "Emisión masiva completada: {$result['issued_count']} certificado(s) emitido(s)."
            : 'No se emitió ningún certificado nuevo.';
        $minimum = (float) (CertificateType::find($typeId)?->min_passing_score ?? 51);
        $labels = [
            'failing_count' => "Reprobados (nota mínima: {$minimum} pts)",
            'unscored_count' => 'Sin calificación',
            'ineligible_count' => 'Carrera no habilitada o pendiente de identificación',
            'duplicate_count' => 'Duplicados o ya certificados',
            'invalid_count' => 'Datos no válidos',
        ];
        $result['duplicate_count'] ??= $result['skipped_count'] ?? 0;
        foreach ($labels as $key => $label) {
            if (($result[$key] ?? 0) > 0) {
                $message .= " {$label}: {$result[$key]}.";
            }
        }

        return $message;
    }
}
