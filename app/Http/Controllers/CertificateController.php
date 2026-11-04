<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\OrganizationalUnit;
use App\Services\CertificatePdfService;
use App\Services\CertificateService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateService $certificateService,
        protected CertificatePdfService $pdfService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Certificate::forListing()->with(['beneficiary', 'activity', 'certificateType', 'certificateTemplate:id,name', 'generatedBy', 'revokedBy', 'organizationalUnit']);

        if ($user->isUnitScoped()) {
            $query->where('organizational_unit_id', $user->organizational_unit_id);
        } elseif ($request->filled('unit_id')) {
            $query->where('organizational_unit_id', $request->input('unit_id'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('certificate_code', 'like', "%{$search}%")
                    ->orWhereHas('beneficiary', function ($b) use ($search) {
                        $b->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%")
                            ->orWhere('document_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('activity', function ($a) use ($search) {
                        $a->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $now = Carbon::now()->startOfDay();
            if ($status === 'VIGENTE') {
                $query->whereNull('revoked_at')
                    ->where('status', '!=', Certificate::STATUS_REVOCADO)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('expiration_date')
                            ->orWhere('expiration_date', '>=', $now);
                    });
            } elseif ($status === 'EXPIRADO') {
                $query->whereNull('revoked_at')
                    ->where('status', '!=', Certificate::STATUS_REVOCADO)
                    ->whereNotNull('expiration_date')
                    ->where('expiration_date', '<', $now);
            } elseif ($status === 'REVOCADO') {
                $query->where(function ($q) {
                    $q->whereNotNull('revoked_at')
                        ->orWhere('status', Certificate::STATUS_REVOCADO);
                });
            }
        }

        if ($activityId = $request->input('activity_id')) {
            $query->where('activity_id', $activityId);
        }

        if ($typeId = $request->input('certificate_type_id')) {
            $query->where('certificate_type_id', $typeId);
        }

        $certificates = $query->latest('id')->paginate(15)->withQueryString();

        $activitiesQuery = Activity::orderBy('name');
        if ($user->isUnitScoped()) {
            $activitiesQuery->where('organizational_unit_id', $user->organizational_unit_id);
        }
        $activities = $activitiesQuery->get();

        $types = CertificateType::where('is_active', true)->get();
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();

        return view('certificates.index', compact('certificates', 'activities', 'types', 'units'));
    }

    public function create(Request $request)
    {
        return redirect()->route('activities.index')->with(
            'info',
            'La emisión de certificados oficiales se gestiona directamente desde cada Actividad Académica a través de su nómina de participantes.'
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'beneficiary_id' => ['required', 'exists:beneficiaries,id'],
            'activity_id' => ['required', 'exists:activities,id'],
            'certificate_type_id' => ['required', 'exists:certificate_types,id'],
            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'issue_date' => ['required', 'date'],
            'validity_type' => ['nullable', 'string', 'in:permanent,temporal,custom'],
            'validity_years' => ['nullable', 'integer', 'min:1', 'max:50'],
            'expiration_date' => ['nullable', 'date'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'score_label' => ['nullable', 'string', 'max:100'],
            'special_note' => ['nullable', 'string', 'max:500'],
            'logo_left_id' => ['nullable', 'exists:institutional_assets,id'],
            'logo_right_id' => ['nullable', 'exists:institutional_assets,id'],
            'watermark_asset_id' => ['nullable', 'exists:institutional_assets,id'],
        ]);

        // Evitar duplicado de mismo beneficiario + actividad + tipo de certificado
        $exists = Certificate::where('beneficiary_id', $validated['beneficiary_id'])
            ->where('activity_id', $validated['activity_id'])
            ->where('certificate_type_id', $validated['certificate_type_id'])
            ->whereNull('revoked_at')
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'Ya existe un certificado activo emitido para este beneficiario en esta actividad con el mismo tipo.');
        }

        $certType = CertificateType::find($validated['certificate_type_id']);
        if ($certType && $certType->supportsScore()) {
            $minScore = (float) ($certType->min_passing_score ?? 51.0);
            $score = isset($validated['score']) ? (float) $validated['score'] : null;
            if ($score === null || $score < $minScore) {
                $activity = Activity::findOrFail($validated['activity_id']);
                $beneficiary = Beneficiary::findOrFail($validated['beneficiary_id']);
                $allowed = $activity->isCareerAllowed($beneficiary->primary_organizational_unit_id);
                if (! $activity->certificates()->where('beneficiary_id', $beneficiary->id)->exists()) {
                    $activity->participants()->syncWithoutDetaching([$beneficiary->id => [
                        'score' => $score,
                        'score_label' => $validated['score_label'] ?? null,
                        'status' => ! $allowed || $score === null ? 'observado' : 'reprobado',
                        'career_eligible' => $allowed,
                        'ineligibility_reason' => $allowed ? null : 'Carrera no autorizada para este evento.',
                    ]]);
                }

                $message = $score === null ? 'Falta la calificación.' : "Calificación reprobatoria: {$score} pts.";

                return back()->withInput()->with('error', "{$message} No se emitió un certificado. La nota mínima es de {$minScore} pts; el participante permanece en la nómina.");
            }
        }

        $customFields = [];
        if (! empty($validated['special_note'])) {
            $customFields['nota_especial'] = $validated['special_note'];
        }
        if (! empty($validated['logo_left_id'])) {
            $customFields['logo_left_id'] = $validated['logo_left_id'];
        }
        if (! empty($validated['logo_right_id'])) {
            $customFields['logo_right_id'] = $validated['logo_right_id'];
        }
        if (! empty($validated['watermark_asset_id'])) {
            $customFields['watermark_asset_id'] = $validated['watermark_asset_id'];
        }

        if (! empty($customFields)) {
            $validated['custom_fields_data'] = $customFields;
        }

        $certificate = $this->certificateService->issueCertificate($validated, $request->user());

        return redirect()->route('certificates.show', $certificate)->with('success', "Certificado {$certificate->certificate_code} generado exitosamente.");
    }

    public function show(Request $request, Certificate $certificate)
    {
        if ($request->user()->isUnitScoped() && $certificate->organizational_unit_id !== $request->user()->organizational_unit_id) {
            abort(403, 'No tiene autorización para visualizar certificados de otra carrera o unidad.');
        }

        $certificate->load(['beneficiary', 'activity', 'certificateType', 'certificateTemplate', 'templateVersion', 'generatedBy', 'revokedBy', 'verifications', 'organizationalUnit']);

        return view('certificates.show', compact('certificate'));
    }

    public function previewPdf(Certificate $certificate)
    {
        $pdf = $this->pdfService->render($certificate);

        return $pdf->stream("{$certificate->certificate_code}.pdf");
    }

    public function downloadPdf(Certificate $certificate)
    {
        AuditLog::log('certificate_download', "Descarga de PDF para {$certificate->certificate_code}", $certificate);
        $pdf = $this->pdfService->render($certificate);

        return $pdf->download("{$certificate->certificate_code}.pdf");
    }

    public function previewDraft(Request $request)
    {
        $validated = $request->validate([
            'beneficiary_id' => ['required', 'exists:beneficiaries,id'],
            'activity_id' => ['required', 'exists:activities,id'],
            'certificate_type_id' => ['required', 'exists:certificate_types,id'],
            'certificate_template_id' => ['nullable', 'exists:certificate_templates,id'],
            'issue_date' => ['required', 'date'],
            'validity_type' => ['nullable', 'string', 'in:permanent,temporal,custom'],
            'validity_years' => ['nullable', 'integer', 'min:1', 'max:50'],
            'expiration_date' => ['nullable', 'date'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'score_label' => ['nullable', 'string', 'max:100'],
            'special_note' => ['nullable', 'string', 'max:500'],
            'logo_left_id' => ['nullable', 'exists:institutional_assets,id'],
            'logo_right_id' => ['nullable', 'exists:institutional_assets,id'],
            'watermark_asset_id' => ['nullable', 'exists:institutional_assets,id'],
        ]);

        $beneficiary = Beneficiary::findOrFail($validated['beneficiary_id']);
        $activity = Activity::findOrFail($validated['activity_id']);
        $type = CertificateType::findOrFail($validated['certificate_type_id']);

        $templateId = $validated['certificate_template_id']
            ?? $activity->certificateConfig?->certificate_template_id
            ?? CertificateTemplate::where('is_default', true)->first()?->id
            ?? CertificateTemplate::where('is_active', true)->first()?->id
            ?? CertificateTemplate::firstOrFail()->id;

        $template = CertificateTemplate::findOrFail($templateId);

        $customFields = [];
        if (! empty($validated['special_note'])) {
            $customFields['nota_especial'] = $validated['special_note'];
        }
        if (! empty($validated['logo_left_id'])) {
            $customFields['logo_left_id'] = $validated['logo_left_id'];
        }
        if (! empty($validated['logo_right_id'])) {
            $customFields['logo_right_id'] = $validated['logo_right_id'];
        }
        if (! empty($validated['watermark_asset_id'])) {
            $customFields['watermark_asset_id'] = $validated['watermark_asset_id'];
        }

        $validityType = $validated['validity_type']
            ?? ($activity->certificateConfig?->validity_mode ?? ($type->validity_mode ?? 'temporal'));

        $expirationDate = null;
        if ($validityType !== 'permanent') {
            if (! empty($validated['expiration_date'])) {
                $expirationDate = $validated['expiration_date'];
            } else {
                $years = (int) ($validated['validity_years'] ?? ($activity->certificateConfig?->validity_years ?? ($type->default_validity_years ?? 3)));
                $expirationDate = Carbon::parse($validated['issue_date'])->addYears($years)->toDateString();
            }
        }

        $dummyCert = new Certificate([
            'certificate_code' => 'VISTA-PREVIA-'.date('Y'),
            'verification_token' => '00000000-0000-0000-0000-000000000000',
            'issue_date' => $validated['issue_date'],
            'expiration_date' => $expirationDate,
            'validity_type' => $validityType,
            'score' => isset($validated['score']) && $validated['score'] !== '' ? (float) $validated['score'] : null,
            'score_label' => $validated['score_label'] ?? null,
            'status' => Certificate::STATUS_VIGENTE,
            'custom_fields_data' => $customFields,
        ]);
        $dummyCert->setRelation('beneficiary', $beneficiary);
        $dummyCert->setRelation('activity', $activity);
        $dummyCert->setRelation('certificateType', $type);
        $dummyCert->setRelation('certificateTemplate', $template);

        $pdf = $this->pdfService->render($dummyCert);

        return $pdf->stream('vista-previa-borrador.pdf');
    }

    public function revoke(Request $request, Certificate $certificate)
    {
        // Solo administradores pueden revocar
        if (! $request->user()->isAdmin()) {
            abort(403, 'No tiene permisos para anular o revocar certificados.');
        }

        $validated = $request->validate([
            'revocation_reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $this->certificateService->revokeCertificate($certificate, $request->user(), $validated['revocation_reason']);

        return redirect()->route('certificates.show', $certificate)->with('warning', "El certificado {$certificate->certificate_code} ha sido marcado como REVOCADO.");
    }
}
