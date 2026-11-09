<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityParticipant extends Model
{
    use HasFactory;

    protected $table = 'activity_participants';

    protected $fillable = [
        'activity_id',
        'beneficiary_id',
        'role',
        'status',
        'career_eligible',
        'ineligibility_reason',
        'score',
        'score_label',
        'payment_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
            'career_eligible' => 'boolean',
            'payment_confirmed' => 'boolean',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function beneficiary(): BelongsTo
    {
        return $this->belongsTo(Beneficiary::class)->withTrashed();
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

    public function isPassing(?float $minScore = null): bool
    {
        $minScore ??= $this->activity?->min_passing_score;

        return $minScore !== null && $this->score !== null && (float) $this->score >= $minScore;
    }

    public function isFailing(?float $minScore = null): bool
    {
        $minScore ??= $this->activity?->min_passing_score;

        return $minScore !== null && $this->score !== null && (float) $this->score < $minScore;
    }

    /**
     * Determina si el participante está habilitado para recibir certificado
     * (cumple carrera/resolución, confirmación si tiene costo y nota mínima si tiene evaluación).
     */
    public function isEligibleForCertificate(?float $minScore = null): bool
    {
        if (! $this->career_eligible) {
            return false;
        }

        if ($this->activity?->requires_payment && ! $this->payment_confirmed) {
            return false;
        }

        return ($minScore === null && ! $this->activity?->certificateType?->supportsScore()) || $this->isPassing($minScore);
    }
}
