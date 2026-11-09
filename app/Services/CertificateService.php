<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        return DB::transaction(fn (): Certificate => $this->createCertificate($data, $generatedBy), attempts: 3);
    }

    private function createCertificate(array $data, User $generatedBy): Certificate
    {
        $issueDate = $data['issue_date'] ?? now()->toDateString();
        $issueCarbon = Carbon::parse($issueDate);

        $beneficiary = Beneficiary::findOrFail($data['beneficiary_id']);
        $activity = Activity::query()->lockForUpdate()->findOrFail($data['activity_id']);
        $certificateType = CertificateType::findOrFail($data['certificate_type_id']);

        if (! $generatedBy->canAccessOrganizationalUnit($activity->organizational_unit_id)) {
            throw new AuthorizationException('No puede emitir certificados para actividades de otra carrera o unidad.');
        }

        // Validación de unidad activa
        if ($activity->organizationalUnit && ! $activity->organizationalUnit->is_active) {
            throw new \InvalidArgumentException("La unidad '{$activity->organizationalUnit->name}' se encuentra temporalmente inactiva. No se pueden emitir nuevos certificados.");
        }

        // Validación de resolución y carrera autorizada
        if (! $activity->isCareerAllowed($beneficiary->primary_organizational_unit_id)) {
            $resMsg = $activity->resolution_number ? "según Resolución {$activity->resolution_number}" : 'por restricciones de la actividad';
            throw new \InvalidArgumentException("El estudiante {$beneficiary->full_name} no pertenece a una carrera autorizada ({$resMsg}).");
        }

        $participant = ActivityParticipant::where('activity_id', $activity->id)
            ->where('beneficiary_id', $beneficiary->id)->lockForUpdate()->first();

        if ($activity->requires_payment) {
            if (! $participant?->payment_confirmed) {
                throw ValidationException::withMessages(['payment_confirmed' => 'Confirmación de pago pendiente. El encargado debe marcar Completado en la nómina antes de emitir el certificado.']);
            }
        }

        if ($activity->certificates()->where('beneficiary_id', $beneficiary->id)
            ->where('certificate_type_id', $certificateType->id)->notRevoked()->lockForUpdate()->value('id')) {
            throw ValidationException::withMessages(['beneficiary_id' => 'Este participante ya tiene un certificado activo de este tipo en la actividad.']);
        }

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
                ?? (int) Setting::get('certificate_validity_years', 3);
            $expirationDate = $issueCarbon->copy()->addYears((int) $years)->toDateString();
        }

        // 2. Resolver calificación y puntaje
        $score = isset($data['score']) && $data['score'] !== '' ? (float) $data['score'] : null;
        $scoreLabel = ! empty($data['score_label']) ? trim((string) $data['score_label']) : null;
        $issuedCertificate = $activity->certificates()->notRevoked()
            ->where('beneficiary_id', $beneficiary->id)->latest('id')->first(['id', 'score', 'score_label']);
        if ($issuedCertificate) {
            $score = $participant ? $participant->score : $issuedCertificate->score;
            $scoreLabel = $participant ? $participant->score_label : $issuedCertificate->score_label;
        }
        if (! empty($score) && empty($scoreLabel)) {
            $scoreLabel = $score.' pts';
        }

        $minScore = $activity->passingScoreForIssuance($certificateType);
        if ($minScore !== null) {
            if ($score === null) {
                throw new \InvalidArgumentException('Debe registrar una calificación antes de emitir este certificado.');
            }
            if ($score < $minScore) {
                throw new \InvalidArgumentException("No se puede emitir un '{$certificateType->name}' con calificación de {$score} pts. La nota mínima reglamentaria de aprobación es de {$minScore} pts.");
            }
        }

        $templateId = $data['certificate_template_id']
            ?? $activity->certificateConfig?->certificate_template_id
            ?? CertificateTemplate::active()->forUnit($activity->organizational_unit_id)
                ->orderByDesc('is_default')->orderBy('id')->value('id')
            ?? throw ValidationException::withMessages(['certificate_template_id' => 'No hay una plantilla activa disponible para esta unidad. Seleccione o cree una plantilla antes de emitir.']);

        $template = CertificateTemplate::findOrFail($templateId);
        if ($template->organizational_unit_id !== null && ! $generatedBy->canAccessOrganizationalUnit($template->organizational_unit_id)) {
            throw new AuthorizationException('No puede utilizar plantillas privadas de otra carrera o unidad.');
        }
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
            'organizational_unit_id' => $activity->organizational_unit_id,
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
        if (! $revokedBy->isAdmin()) {
            throw new AuthorizationException('Solo el superadministrador puede anular certificados.');
        }

        return DB::transaction(function () use ($certificate, $revokedBy, $reason): Certificate {
            $activity = Activity::withTrashed()->lockForUpdate()->findOrFail($certificate->activity_id);
            $certificate->refresh();
            if ($certificate->isRevoked()) {
                return $certificate;
            }

            $oldValues = ['status' => $certificate->status, 'revoked_at' => $certificate->revoked_at];
            $certificate->revoke($revokedBy, $reason);
            $activity->refreshParticipantEligibility($certificate->beneficiary_id);

            AuditLog::log(
                'certificate_revoked',
                "Certificado {$certificate->certificate_code} revocado. Motivo: {$reason}",
                $certificate,
                $oldValues,
                $certificate->toArray()
            );

            return $certificate;
        }, attempts: 3);
    }
}
