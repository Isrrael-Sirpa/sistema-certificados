<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityCertificateConfig;
use App\Models\ActivityParticipant;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\InstitutionalAsset;
use App\Models\OrganizationalUnit;
use App\Services\BeneficiaryImportService;
use App\Services\CertificatePdfService;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
        $user = $request->user();
        if (! $user->isUnitActive()) {
            abort(403, 'Su carrera o departamento se encuentra desactivado temporalmente. No es posible crear nuevas actividades.');
        }

        $types = CertificateType::where('is_active', true)->get();
        $templates = CertificateTemplate::select(['id', 'name', 'category', 'orientation', 'organizational_unit_id', 'current_version', 'is_default'])
            ->where('is_active', true)
            ->when($user->isUnitScoped(), fn ($query) => $query->forUnit($user->organizational_unit_id))
            ->with('organizationalUnit:id,name')->orderBy('id')->get();
        $designs = CertificateTemplate::whereKey($templates->modelKeys())->get(['id', 'configuration'])->keyBy('id');
        foreach ($templates as $template) {
            $template->setAttribute('configuration', $designs->get($template->id)?->configuration ?? []);
        }
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();
        $organizerOptions = $user->isAdmin() ? $units : collect();
        $assignedUnit = $units->find($user->organizational_unit_id);
        $organizerUnit = $user->isAdmin()
            ? $organizerOptions->find(filter_var(old('organizational_unit_id', ($assignedUnit ?? $units->first())?->id), FILTER_VALIDATE_INT))
            : $assignedUnit;

        return view('activities.create', compact('types', 'templates', 'units', 'organizerOptions', 'organizerUnit'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        if (! $user->isUnitActive()) {
            abort(403, 'Su carrera o departamento se encuentra desactivado temporalmente. No es posible registrar nuevas actividades.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity_type' => ['required', 'string', 'max:80'],
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
            'min_passing_score' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,999.99'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'academic_hours' => ['required', 'integer', 'min:0', 'max:1000'],
            'requires_payment' => ['required', 'boolean'],
            'cost_amount' => [Rule::excludeIf(fn (): bool => ! $request->boolean('requires_payment')), Rule::requiredIf(fn (): bool => $request->boolean('requires_payment')), 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'resolution_number' => ['nullable', 'string', 'max:100'],
            'target_audience_mode' => ['nullable', 'string', 'in:own_unit,all_faculty,specific_units'],
            'allowed_unit_ids' => ['nullable', 'array'],
            'allowed_unit_ids.*' => ['exists:organizational_units,id'],
            'organizational_unit_id' => [Rule::excludeIf(! $user->isAdmin()), 'nullable', 'integer', Rule::exists('organizational_units', 'id')->where('is_active', true)],
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

        $this->validateActivityPassingScore($validated);

        $activityData = collect($validated)->only([
            'name', 'description', 'activity_type', 'certificate_type_id', 'min_passing_score',
            'start_date', 'end_date', 'academic_hours', 'resolution_number',
            'location', 'organizer', 'responsible_authority', 'status',
        ])->toArray();

        $activityData['requires_payment'] = $request->boolean('requires_payment');
        $activityData['cost_amount'] = $activityData['requires_payment'] ? $validated['cost_amount'] : '0.00';

        $activityData['organizational_unit_id'] = $validated['organizational_unit_id']
            ?? ($user->organizationalUnit?->is_active ? $user->organizational_unit_id : null);
        $activityData['target_audience_mode'] = $validated['target_audience_mode']
            ?? ($activityData['organizational_unit_id'] ? Activity::AUDIENCE_OWN_UNIT : Activity::AUDIENCE_ALL_FACULTY);

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
            ->reject(fn (Certificate $certificate): bool => $certificate->isRevoked())
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
        $user = $request->user();
        if ($user->isUnitScoped() && $activity->organizational_unit_id !== $user->organizational_unit_id) {
            abort(403, 'No tiene autorización para editar actividades de otra carrera o unidad.');
        }

        $types = CertificateType::where('is_active', true)->get();
        $templates = CertificateTemplate::where('is_active', true)
            ->when(! $user->isAdmin(), fn ($query) => $query->forUnit($activity->organizational_unit_id))
            ->orderBy('name')->get(['id', 'name', 'orientation', 'organizational_unit_id']);
        $config = $activity->certificateConfig;
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();
        $organizerUnit = $activity->organizationalUnit;
        $organizerOptions = $user->isAdmin() ? $units : collect();
        if ($user->isAdmin()) {
            if ($organizerUnit && ! $organizerUnit->is_active) {
                $organizerOptions = $organizerOptions->concat([$organizerUnit]);
            }
            $organizerUnit = $organizerOptions->firstWhere('id', filter_var(old('organizational_unit_id', $organizerUnit?->id), FILTER_VALIDATE_INT));
        }

        return view('activities.edit', compact('activity', 'types', 'templates', 'config', 'units', 'organizerOptions', 'organizerUnit'));
    }

    public function update(Request $request, Activity $activity): RedirectResponse
    {
        return DB::transaction(fn (): RedirectResponse => $this->updateActivity(
            $request, Activity::query()->lockForUpdate()->findOrFail($activity->id)
        ), attempts: 3);
    }

    private function updateActivity(Request $request, Activity $activity): RedirectResponse
    {
        if ($request->user()->isUnitScoped() && $activity->organizational_unit_id !== $request->user()->organizational_unit_id) {
            abort(403, 'No tiene autorización para modificar actividades de otra carrera o unidad.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity_type' => ['required', 'string', 'max:80'],
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
            'min_passing_score' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,999.99'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'academic_hours' => ['required', 'integer', 'min:0', 'max:1000'],
            'resolution_number' => ['nullable', 'string', 'max:100'],
            'target_audience_mode' => ['nullable', 'string', 'in:own_unit,all_faculty,specific_units'],
            'allowed_unit_ids' => ['nullable', 'array'],
            'allowed_unit_ids.*' => ['exists:organizational_units,id'],
            'organizational_unit_id' => [Rule::excludeIf(! $request->user()->isAdmin()), 'nullable', 'integer', Rule::exists('organizational_units', 'id')
                ->where(fn ($query) => $query->where(fn ($units) => $units->where('is_active', true)->orWhere('id', $activity->organizational_unit_id)))],
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

        $this->validateActivityPassingScore($validated, $activity);
        $old = $activity->toArray();
        $pricing = $request->validate([
            'requires_payment' => ['sometimes', 'boolean'],
            'cost_amount' => ['sometimes', 'required', 'numeric', 'decimal:0,2', 'min:0', 'max:99999999.99'],
        ]);
        $activityData = collect($validated)->only([
            'name', 'description', 'activity_type', 'certificate_type_id', 'min_passing_score',
            'start_date', 'end_date', 'academic_hours', 'resolution_number',
            'target_audience_mode', 'location', 'organizer', 'responsible_authority', 'status',
        ])->toArray();

        if ($request->user()->isAdmin() && ! empty($validated['organizational_unit_id'])) {
            $activityData['organizational_unit_id'] = $validated['organizational_unit_id'];
        }

        $activity->update(array_merge($activityData, $pricing));

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
    public function updateCertificateConfig(Request $request, Activity $activity): RedirectResponse
    {
        return DB::transaction(fn (): RedirectResponse => $this->saveCertificateConfig(
            $request, Activity::query()->lockForUpdate()->findOrFail($activity->id)
        ), attempts: 3);
    }

    private function saveCertificateConfig(Request $request, Activity $activity): RedirectResponse
    {
        abort_unless($request->user()->canAccessOrganizationalUnit($activity->organizational_unit_id), 403);

        $validated = $request->validate([
            'certificate_type_id' => ['nullable', 'exists:certificate_types,id'],
            'min_passing_score' => ['nullable', 'numeric', 'decimal:0,2', 'between:0,999.99'],
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

        $this->validateActivityPassingScore($validated, $activity);
        $grading = collect($validated)->only(['certificate_type_id', 'min_passing_score'])->all();
        if ($grading !== []) {
            $activity->update($grading);
            if ($activity->wasChanged(['certificate_type_id', 'min_passing_score'])) {
                $activity->refreshParticipantEligibility();
            }
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

    private function validateActivityPassingScore(array $validated, ?Activity $activity = null): void
    {
        $typeId = array_key_exists('certificate_type_id', $validated) ? $validated['certificate_type_id'] : $activity?->certificate_type_id;
        $minimum = array_key_exists('min_passing_score', $validated) ? $validated['min_passing_score'] : $activity?->min_passing_score;

        if ($minimum === null && $typeId && CertificateType::whereKey($typeId)->where('has_score', true)->exists()) {
            throw ValidationException::withMessages(['min_passing_score' => 'Indica la nota mínima de aprobación para esta actividad.']);
        }
    }

    /**
     * Emisión masiva de certificados para múltiples beneficiarios seleccionados
     */
    public function batchIssue(Request $request, Activity $activity, CertificateService $certificateService, BeneficiaryImportService $importService): RedirectResponse
    {
        return DB::transaction(fn (): RedirectResponse => $this->issueSelectedParticipants(
            $request, Activity::query()->lockForUpdate()->findOrFail($activity->id), $certificateService, $importService
        ), attempts: 3);
    }

    private function issueSelectedParticipants(Request $request, Activity $activity, CertificateService $certificateService, BeneficiaryImportService $importService): RedirectResponse
    {
        abort_unless($request->user()->canAccessOrganizationalUnit($activity->organizational_unit_id), 403);

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

        if ($activity->requires_payment) {
            $result = $importService->issueCertificatesToActivityParticipants(
                $activity, (int) $typeId, $templateId ? (int) $templateId : null,
                $validated['issue_date'], $request->user(), $certificateService, $validated['beneficiary_ids']
            );

            return back()->with($result['issued_count'] > 0 ? 'success' : 'warning', $this->issuanceSummary($result));
        }

        $certType = CertificateType::find($typeId);
        $minScore = $activity->passingScoreForIssuance($certType);

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
                ->notRevoked()
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
            if ($minScore !== null) {
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

            $msg = $this->issuanceSummary($result);

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
            ->notRevoked()
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
    public function addParticipantManual(Request $request, Activity $activity): RedirectResponse
    {
        $validated = $request->validate([
            'beneficiary_id' => ['required', 'exists:beneficiaries,id'],
            'role' => ['nullable', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'score_label' => ['nullable', 'string', 'max:100'],
        ]);

        return DB::transaction(fn (): RedirectResponse => $this->enrollParticipantManually(
            $request, Activity::query()->lockForUpdate()->findOrFail($activity->id), $validated
        ), attempts: 3);
    }

    private function enrollParticipantManually(Request $request, Activity $activity, array $validated): RedirectResponse
    {
        abort_unless($request->user()->canAccessOrganizationalUnit($activity->organizational_unit_id), 403);
        $beneficiaryId = (int) $validated['beneficiary_id'];
        $beneficiary = Beneficiary::findOrFail($beneficiaryId);

        if ($activity->participants()->where('beneficiaries.id', $beneficiaryId)->exists()) {
            return redirect()->route('activities.show', $activity)->with('warning', 'El estudiante ya se encuentra registrado en la nómina de esta actividad.');
        }

        $hasCert = $activity->certificates()
            ->where('beneficiary_id', $beneficiaryId)
            ->notRevoked()
            ->exists();

        if ($hasCert) {
            return back()->with('error', 'Este participante ya tiene un certificado emitido. Su calificación está bloqueada.');
        }

        $certType = $activity->certificateType;
        $minScore = $certType?->supportsScore() ? $activity->min_passing_score : null;
        $score = isset($validated['score']) && $validated['score'] !== '' ? (float) $validated['score'] : null;

        $isAllowed = $activity->isCareerAllowed($beneficiary->primary_organizational_unit_id);
        $resText = $activity->resolution_number ? "según Resolución {$activity->resolution_number}" : 'por restricción de unidad';
        $ineligibilityReason = $isAllowed ? null : "Carrera no autorizada ({$resText})";

        $participantStatus = ! $isAllowed ? 'observado' : ($hasCert ? 'certificado' : (($minScore !== null && $score !== null && $score < $minScore) ? 'reprobado' : 'inscrito'));

        $activity->participants()->attach($beneficiaryId, [
            'role' => $validated['role'] ?? 'Participante',
            'score' => $score,
            'score_label' => $validated['score_label'] ?? null,
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
    public function updateParticipant(Request $request, Activity $activity, Beneficiary $beneficiary): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['nullable', 'string', 'max:50'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'score_label' => ['nullable', 'string', 'max:100'],
        ]);

        return DB::transaction(function () use ($request, $activity, $beneficiary, $validated): RedirectResponse {
            $activity = Activity::query()->lockForUpdate()->findOrFail($activity->id);
            abort_unless($request->user()->canAccessOrganizationalUnit($activity->organizational_unit_id), 403);
            abort_unless($activity->participants()->where('beneficiaries.id', $beneficiary->id)->exists(), 404);
            if ($activity->certificates()->notRevoked()->where('beneficiary_id', $beneficiary->id)->exists()) {
                return back()->with('error', 'La calificación está bloqueada mientras exista un certificado sin anular. Solicite la anulación al superadministrador antes de corregirla.');
            }

            $participant = ActivityParticipant::where('activity_id', $activity->id)
                ->where('beneficiary_id', $beneficiary->id)->firstOrFail();
            $oldValues = $participant->only(['role', 'score', 'score_label']);
            $certType = $activity->certificateType;
            $score = isset($validated['score']) ? (float) $validated['score'] : null;
            $minimum = $certType?->supportsScore() ? $activity->min_passing_score : null;
            $isFailing = $minimum !== null && $score !== null && $score < $minimum;
            $isAllowed = $activity->isCareerAllowed($beneficiary->primary_organizational_unit_id);
            $resText = $activity->resolution_number ? "según Resolución {$activity->resolution_number}" : 'por restricción de unidad';

            $activity->participants()->updateExistingPivot($beneficiary->id, [
                'role' => $validated['role'] ?? 'Participante',
                'score' => $score,
                'score_label' => $validated['score_label'] ?? null,
                'career_eligible' => $isAllowed,
                'ineligibility_reason' => $isAllowed ? null : "Carrera no autorizada ({$resText})",
                'status' => ! $isAllowed ? 'observado' : ($isFailing ? 'reprobado' : 'inscrito'),
            ]);

            AuditLog::log('participant_academic_record_updated', "Datos académicos de {$beneficiary->full_name} actualizados en '{$activity->name}'", $activity,
                ['beneficiary_id' => $beneficiary->id, ...$oldValues],
                ['beneficiary_id' => $beneficiary->id, 'score' => $score, 'score_label' => $validated['score_label'] ?? null, 'role' => $validated['role'] ?? 'Participante']);

            return redirect()->route('activities.show', $activity)->with('success', "Datos de {$beneficiary->full_name} actualizados en la nómina.");
        }, attempts: 3);
    }

    /**
     * Registra la confirmación manual del encargado antes de la emisión.
     */
    public function updatePaymentConfirmation(Request $request, Activity $activity, Beneficiary $beneficiary): RedirectResponse
    {
        if (! $activity->requires_payment) {
            throw ValidationException::withMessages(['payment_confirmed' => 'Esta actividad es sin costo y no requiere confirmación de pago.']);
        }
        $validated = $request->validate(['payment_confirmed' => ['required', 'boolean']]);

        return DB::transaction(function () use ($activity, $beneficiary, $validated): RedirectResponse {
            $activity = Activity::query()->lockForUpdate()->findOrFail($activity->id);
            $participant = ActivityParticipant::where('activity_id', $activity->id)
                ->where('beneficiary_id', $beneficiary->id)->lockForUpdate()->firstOrFail();
            if ($activity->certificates()->notRevoked()->where('beneficiary_id', $beneficiary->id)->lockForUpdate()->value('id')) {
                return back()->with('error', 'La confirmación está bloqueada porque este participante ya tiene un certificado emitido.');
            }
            $confirmed = (bool) $validated['payment_confirmed'];
            $oldValue = $participant->payment_confirmed;
            if ($oldValue !== $confirmed) {
                $participant->update(['payment_confirmed' => $confirmed]);
                AuditLog::log('participant_payment_confirmation_updated', "Confirmación manual de {$beneficiary->full_name}: ".($confirmed ? 'Completado' : 'Pendiente'), $activity,
                    ['beneficiary_id' => $beneficiary->id, 'payment_confirmed' => $oldValue],
                    ['beneficiary_id' => $beneficiary->id, 'payment_confirmed' => $confirmed]);
            }

            return redirect()->route('activities.show', $activity)->with('success', 'Confirmación del encargado actualizada: '.($confirmed ? 'Completado.' : 'Pendiente.'));
        });
    }

    public function removeParticipant(Activity $activity, Beneficiary $beneficiary): RedirectResponse
    {
        return DB::transaction(function () use ($activity, $beneficiary): RedirectResponse {
            $activity = Activity::query()->lockForUpdate()->findOrFail($activity->id);
            $participant = ActivityParticipant::where('activity_id', $activity->id)
                ->where('beneficiary_id', $beneficiary->id)->lockForUpdate()->firstOrFail();
            if ($activity->certificates()->notRevoked()->where('beneficiary_id', $beneficiary->id)->lockForUpdate()->value('id')) {
                return redirect()->route('activities.show', $activity)->with('error', 'No se puede remover de la lista a un participante que ya tiene un certificado emitido.');
            }

            $oldValues = $participant->only(['beneficiary_id', 'status', 'payment_confirmed']);
            $activity->participants()->detach($beneficiary->id);
            AuditLog::log('activity_participant_removed', "Participante {$beneficiary->full_name} retirado de la nómina", $activity, $oldValues);

            return redirect()->route('activities.show', $activity)->with('success', "Participante {$beneficiary->full_name} removido de la nómina de esta actividad.");
        });
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

            $msg = $this->issuanceSummary($result);

            return redirect()->route('activities.show', $activity)->with($result['issued_count'] > 0 ? 'success' : 'warning', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al emitir los certificados: '.$e->getMessage());
        }
    }

    /** @param array<string, int|float|null> $result */
    private function issuanceSummary(array $result): string
    {
        $message = $result['issued_count'] > 0
            ? "Emisión masiva completada: {$result['issued_count']} certificado(s) emitido(s)."
            : 'No se emitió ningún certificado nuevo.';
        $minimum = $result['min_passing_score'] ?? null;
        $labels = [
            'failing_count' => "Reprobados (nota mínima: {$minimum} pts)",
            'unscored_count' => 'Sin calificación',
            'ineligible_count' => 'Carrera no habilitada o pendiente de identificación',
            'payment_pending_count' => 'Confirmación de pago pendiente',
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
