<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;

    public const STATUS_VIGENTE = 'VIGENTE';

    public const STATUS_EXPIRADO = 'EXPIRADO';

    public const STATUS_REVOCADO = 'REVOCADO';

    public const VALIDITY_PERMANENT = 'permanent';

    public const VALIDITY_YEARS = 'years';

    public const VALIDITY_CUSTOM = 'custom_date';

    protected $fillable = [
        'certificate_code',
        'verification_token',
        'beneficiary_id',
        'activity_id',
        'certificate_type_id',
        'certificate_template_id',
        'template_version_id',
        'generated_by_user_id',
        'issue_date',
        'expiration_date',
        'validity_type',
        'status',
        'revoked_at',
        'revocation_reason',
        'revoked_by_user_id',
        'pdf_path',
        'qr_path',
        'integrity_hash',
        'score',
        'score_label',
        'snapshot_data',
        'custom_fields_data',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'expiration_date' => 'date',
            'revoked_at' => 'datetime',
            'score' => 'float',
            'snapshot_data' => 'array',
            'custom_fields_data' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Certificate $certificate) {
            if (empty($certificate->verification_token)) {
                $certificate->verification_token = (string) Str::uuid();
            }

            if (empty($certificate->issue_date)) {
                $certificate->issue_date = now()->toDateString();
            }

            if (empty($certificate->validity_type)) {
                $certificate->validity_type = self::VALIDITY_YEARS;
            }

            // Si es permanente, la fecha de vencimiento es nula
            if ($certificate->validity_type === self::VALIDITY_PERMANENT) {
                $certificate->expiration_date = null;
            } elseif (empty($certificate->expiration_date)) {
                $issueCarbon = Carbon::parse($certificate->issue_date);
                $certificate->expiration_date = $issueCarbon->copy()->addYears(3)->toDateString();
            }

            if (empty($certificate->certificate_code)) {
                $certificate->certificate_code = self::generateUniqueCode($certificate->issue_date);
            }

            if (empty($certificate->status)) {
                $certificate->status = self::STATUS_VIGENTE;
            }
        });
    }

    /**
     * Generador de código institucional único: CERT-YYYY-XXXXXX
     */
    public static function generateUniqueCode(?string $issueDate = null): string
    {
        $year = $issueDate ? Carbon::parse($issueDate)->format('Y') : now()->format('Y');

        // Obtener el conteo anual para la secuencia correlativa
        $count = self::whereYear('issue_date', $year)->count() + 1;
        $code = sprintf('CERT-%s-%06d', $year, $count);

        // Asegurar que no colisione
        while (self::where('certificate_code', $code)->exists()) {
            $count++;
            $code = sprintf('CERT-%s-%06d', $year, $count);
        }

        return $code;
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class)->withTrashed();
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class)->withTrashed();
    }

    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class);
    }

    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

    public function templateVersion(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplateVersion::class, 'template_version_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by_user_id');
    }

    public function revokedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by_user_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(CertificateVerification::class);
    }

    /**
     * Cálculo de estado dinámico y seguro según las reglas de negocio
     */
    public function getCalculatedStatusAttribute(): string
    {
        if ($this->revoked_at !== null || $this->status === self::STATUS_REVOCADO) {
            return self::STATUS_REVOCADO;
        }

        // Si el certificado es permanente (sin vencimiento), siempre está vigente
        if ($this->isPermanent()) {
            return self::STATUS_VIGENTE;
        }

        if ($this->expiration_date && Carbon::now()->startOfDay()->greaterThan($this->expiration_date)) {
            return self::STATUS_EXPIRADO;
        }

        return self::STATUS_VIGENTE;
    }

    public function isPermanent(): bool
    {
        return $this->validity_type === self::VALIDITY_PERMANENT || empty($this->expiration_date);
    }

    public function hasScore(): bool
    {
        return $this->score !== null || ! empty($this->score_label);
    }

    public function getFormattedScoreAttribute(): ?string
    {
        if (! $this->hasScore()) {
            return null;
        }

        if (! empty($this->score_label)) {
            return $this->score_label;
        }

        return $this->score !== null ? ($this->score.' pts') : null;
    }

    public function isRevoked(): bool
    {
        return $this->calculated_status === self::STATUS_REVOCADO;
    }

    public function isExpired(): bool
    {
        return $this->calculated_status === self::STATUS_EXPIRADO;
    }

    public function isValid(): bool
    {
        return $this->calculated_status === self::STATUS_VIGENTE;
    }

    public function getVerificationUrlAttribute(): string
    {
        return url('/verificar/'.$this->verification_token);
    }

    public function revoke(User $user, string $reason): bool
    {
        $this->status = self::STATUS_REVOCADO;
        $this->revoked_at = now();
        $this->revoked_by_user_id = $user->id;
        $this->revocation_reason = $reason;

        return $this->save();
    }
}
