<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\ActivityCertificateConfig;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\InstitutionalAsset;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::withCount('certificates');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('activity_type')) {
            $query->where('activity_type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $activities = $query->orderBy('start_date', 'desc')->paginate(12)->withQueryString();

        return view('activities.index', compact('activities'));
    }

    public function create()
    {
        return view('activities.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity_type' => ['required', 'string', 'max:80'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'academic_hours' => ['required', 'integer', 'min:0', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'responsible_authority' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:activa,finalizada,cancelada'],
        ]);

        $activity = Activity::create($validated);

        AuditLog::log('activity_created', "Actividad '{$activity->name}' registrada", $activity);

        return redirect()->route('activities.show', $activity)->with('success', 'Actividad registrada exitosamente.');
    }

    public function show(Activity $activity)
    {
        $activity->load([
            'certificateConfig.template',
            'certificates' => function ($q) {
                $q->with(['beneficiary', 'certificateType', 'certificateTemplate'])->latest('id');
            },
        ]);

        $beneficiaries = Beneficiary::orderBy('last_name')->orderBy('first_name')->get();
        $types = CertificateType::where('is_active', true)->get();
        $templates = CertificateTemplate::where('is_active', true)->get();
        $config = $activity->certificateConfig;
        $signatures = InstitutionalAsset::active()->ofType(InstitutionalAsset::TYPE_SIGNATURE)->get();

        return view('activities.show', compact('activity', 'beneficiaries', 'types', 'templates', 'config', 'signatures'));
    }

    public function edit(Activity $activity)
    {
        return view('activities.edit', compact('activity'));
    }

    public function update(Request $request, Activity $activity)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity_type' => ['required', 'string', 'max:80'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'academic_hours' => ['required', 'integer', 'min:0', 'max:1000'],
            'location' => ['nullable', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'responsible_authority' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:activa,finalizada,cancelada'],
        ]);

        $old = $activity->toArray();
        $activity->update($validated);

        AuditLog::log('activity_updated', "Actividad '{$activity->name}' modificada", $activity, $old, $activity->toArray());

        return redirect()->route('activities.show', $activity)->with('success', 'Actividad actualizada.');
    }

    public function destroy(Activity $activity)
    {
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
            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'custom_title' => ['nullable', 'string', 'max:255'],
            'custom_body_text' => ['nullable', 'string'],
            'primary_color' => ['nullable', 'string', 'max:20'],
            'secondary_color' => ['nullable', 'string', 'max:20'],
            'resolution_number' => ['nullable', 'string', 'max:100'],
            'modality' => ['nullable', 'string', 'max:100'],
        ]);

        $customFields = [];
        if (! empty($validated['resolution_number'])) {
            $customFields['resolucion'] = $validated['resolution_number'];
        }
        if (! empty($validated['modality'])) {
            $customFields['modalidad'] = $validated['modality'];
        }

        ActivityCertificateConfig::updateOrCreate(
            ['activity_id' => $activity->id],
            [
                'certificate_template_id' => $validated['certificate_template_id'] ?? null,
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

        return back()->with('success', "Configuración de certificado para '{$activity->name}' guardada exitosamente.");
    }

    /**
     * Emisión masiva de certificados para múltiples beneficiarios seleccionados
     */
    public function batchIssue(Request $request, Activity $activity, CertificateService $certificateService)
    {
        $validated = $request->validate([
            'beneficiary_ids' => ['required', 'array', 'min:1'],
            'beneficiary_ids.*' => ['exists:beneficiaries,id'],
            'certificate_type_id' => ['required', 'exists:certificate_types,id'],
            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'issue_date' => ['required', 'date'],
        ]);

        $templateId = $validated['certificate_template_id']
            ?? $activity->certificateConfig?->certificate_template_id
            ?? null;

        $count = 0;
        $skipped = 0;

        foreach ($validated['beneficiary_ids'] as $beneficiaryId) {
            $alreadyExists = $activity->certificates()
                ->where('beneficiary_id', $beneficiaryId)
                ->where('certificate_type_id', $validated['certificate_type_id'])
                ->whereNull('revoked_at')
                ->exists();

            if ($alreadyExists) {
                $skipped++;

                continue;
            }

            $certificateService->issueCertificate([
                'beneficiary_id' => $beneficiaryId,
                'activity_id' => $activity->id,
                'certificate_type_id' => $validated['certificate_type_id'],
                'certificate_template_id' => $templateId,
                'issue_date' => $validated['issue_date'],
            ], $request->user());

            $count++;
        }

        $msg = "Se generaron exitosamente {$count} certificado(s).";
        if ($skipped > 0) {
            $msg .= " ({$skipped} omitidos por ya poseer certificado activo).";
        }

        return back()->with('success', $msg);
    }
}
