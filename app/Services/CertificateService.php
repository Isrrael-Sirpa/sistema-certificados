<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;

class CertificateService
{
    public function __construct(
        protected CertificatePdfService $pdfService,
        protected CertificateSnapshotService $snapshotService
    ) {}

    /**
     * Emite un nuevo certificado individual con todas las validaciones institucionales,
     * cálculo automático de vigencia (3 años) y congelación inmutable de diseño (Snapshot).
     */
    public function issueCertificate(array $data, User $generatedBy): Certificate
    {
        $issueDate = $data['issue_date'] ?? now()->toDateString();
        $issueCarbon = Carbon::parse($issueDate);

        $beneficiary = Beneficiary::findOrFail($data['beneficiary_id']);
        $activity = Activity::findOrFail($data['activity_id']);
        $certificateType = CertificateType::findOrFail($data['certificate_type_id']);

        // 1. Resolver modo de vigencia y fecha de vencimiento
        $validityType = $data['validity_type']
            ?? $activity->certificateConfig?->validity_mode
            ?? ($certificateType->isPermanentByDefault() ? Certificate::VALIDITY_PERMANENT : Certificate::VALIDITY_YEARS);

        if ($validityType === Certificate::VALIDITY_PERMANENT) {
            $expirationDate = null;
        } elseif (! empty($data['expiration_date'])) {
            $expirationDate = $data['expiration_date'];
        } else {
            $years = $data['validity_years']
                ?? $activity->certificateConfig?->validity_years
                ?? $certificateType->default_validity_years
                ?? 3;
            $expirationDate = $issueCarbon->copy()->addYears((int) $years)->toDateString();
        }

        // 2. Resolver calificación y puntaje
        $score = isset($data['score']) && $data['score'] !== '' ? (float) $data['score'] : null;
        $scoreLabel = ! empty($data['score_label']) ? trim((string) $data['score_label']) : null;
        if (! empty($score) && empty($scoreLabel)) {
            $scoreLabel = $score.' pts';
        }

        // Validación de aprobación institucional: si el tipo de certificado evalúa notas, no se emite a reprobados
        if ($certificateType->supportsScore()) {
            $minScore = (float) ($certificateType->min_passing_score ?? 51.0);
            if ($score !== null && $score < $minScore) {
                throw new \InvalidArgumentException("No se puede emitir un '{$certificateType->name}' con calificación de {$score} pts. La nota mínima reglamentaria de aprobación es de {$minScore} pts.");
            }
        }

        $templateId = $data['certificate_template_id']
            ?? $activity->certificateConfig?->certificate_template_id
            ?? CertificateTemplate::where('is_default', true)->first()?->id
            ?? CertificateTemplate::where('is_active', true)->first()?->id
            ?? CertificateTemplate::firstOrFail()->id;

        $template = CertificateTemplate::findOrFail($templateId);
        $currentVersion = $template->currentVersionRecord();

        $token = (string) Str::uuid();
        $code = Certificate::generateUniqueCode($issueDate);
        $customFields = $data['custom_fields_data'] ?? [];

        // Snapshot documental 100% congelado de diseño, textos, logos, firmas y sellos en el momento exacto de emisión
        $immutableSnapshot = $this->snapshotService->buildImmutableSnapshot(
            $beneficiary,
            $activity,
            $certificateType,
            $template,
            $issueDate,
            $code,
            $customFields,
            $expirationDate,
            $score,
            $scoreLabel,
            $validityType
        );

        // Hash SHA-256 de integridad para prevenir cualquier falsificación o alteración
        $integrityHash = hash('sha256', json_encode([
            'code' => $code,
            'token' => $token,
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'issue_date' => $issueDate,
            'expiration_date' => $expirationDate,
            'validity_type' => $validityType,
            'score' => $score,
            'score_label' => $scoreLabel,
            'template_id' => $template->id,
            'version' => $template->current_version,
            'snapshot_hash' => hash('sha256', json_encode($immutableSnapshot)),
        ]));

        $certificate = Certificate::create([
            'certificate_code' => $code,
            'verification_token' => $token,
            'beneficiary_id' => $beneficiary->id,
            'activity_id' => $activity->id,
            'certificate_type_id' => $certificateType->id,
            'certificate_template_id' => $template->id,
            'template_version_id' => $currentVersion?->id,
            'generated_by_user_id' => $generatedBy->id,
            'issue_date' => $issueDate,
            'expiration_date' => $expirationDate,
            'validity_type' => $validityType,
            'score' => $score,
            'score_label' => $scoreLabel,
            'status' => Certificate::STATUS_VIGENTE,
            'integrity_hash' => $integrityHash,
            'snapshot_data' => $immutableSnapshot,
            'custom_fields_data' => $customFields,
        ]);

        // Registrar en auditoría
        AuditLog::log(
            'certificate_issued',
            "Certificado {$certificate->certificate_code} emitido para {$beneficiary->full_name} con plantilla '{$template->name}' (v{$template->current_version}.0)",
            $certificate,
            null,
            $certificate->toArray()
        );

        return $certificate;
    }

    /**
     * Revoca un certificado y registra los motivos de auditoría.
     */
    public function revokeCertificate(Certificate $certificate, User $revokedBy, string $reason): Certificate
    {
        $oldValues = ['status' => $certificate->status, 'revoked_at' => $certificate->revoked_at];

        $certificate->revoke($revokedBy, $reason);

        AuditLog::log(
            'certificate_revoked',
            "Certificado {$certificate->certificate_code} revocado. Motivo: {$reason}",
            $certificate,
            $oldValues,
            $certificate->toArray()
        );

        return $certificate;
    }
}
