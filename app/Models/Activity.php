<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\ValidationException;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    public const AUDIENCE_OWN_UNIT = 'own_unit';

    public const AUDIENCE_ALL_FACULTY = 'all_faculty';

    public const AUDIENCE_SPECIFIC_UNITS = 'specific_units';

    protected $attributes = [
        'requires_payment' => false,
        'cost_amount' => '0.00',
    ];

    protected $fillable = [
        'name',
        'description',
        'activity_type',
        'start_date',
        'end_date',
        'academic_hours',
        'resolution_number',
        'target_audience_mode',
        'location',
        'organizer',
        'responsible_authority',
        'organizational_unit_id',
        'certificate_type_id',
        'min_passing_score',
        'status',
        'requires_payment',
        'cost_amount',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'academic_hours' => 'integer',
            'min_passing_score' => 'float',
            'requires_payment' => 'boolean',
            'cost_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (Activity $activity): void {
            if ($activity->isDirty(['requires_payment', 'cost_amount'])) {
                throw ValidationException::withMessages(['cost_amount' => 'La modalidad y el monto quedan definidos al crear la actividad y no se pueden modificar.']);
            }
        });
    }

    public function getFormattedCostAttribute(): string
    {
        return $this->requires_payment ? 'Bs '.number_format((float) $this->cost_amount, 2, '.', ',') : 'Sin costo';
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class, 'organizational_unit_id');
    }

    public function allowedUnits(): BelongsToMany
    {
        return $this->belongsToMany(OrganizationalUnit::class, 'activity_allowed_units', 'activity_id', 'organizational_unit_id')
            ->withTimestamps();
    }

    public function certificateType(): BelongsTo
    {
        return $this->belongsTo(CertificateType::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(Beneficiary::class, 'activity_participants')
            ->withPivot(['id', 'role', 'status', 'score', 'score_label', 'career_eligible', 'ineligibility_reason', 'payment_confirmed'])
            ->withTimestamps();
    }

    /**
     * Verifica si una carrera o unidad organizacional está autorizada a participar en esta actividad según la resolución.
     */
    public function isCareerAllowed(?int $unitId): bool
    {
        if ($this->target_audience_mode === self::AUDIENCE_ALL_FACULTY
            || ($this->organizational_unit_id === null && $this->target_audience_mode !== self::AUDIENCE_SPECIFIC_UNITS)) {
            return true;
        }

        if ($unitId === null) {
            return false;
        }

        if ($this->target_audience_mode === self::AUDIENCE_OWN_UNIT) {
            return $this->organizational_unit_id === $unitId;
        }

        if ($this->target_audience_mode === self::AUDIENCE_SPECIFIC_UNITS) {
            if ($this->organizational_unit_id === $unitId) {
                return true;
            }

            return $this->relationLoaded('allowedUnits')
                ? $this->allowedUnits->contains('id', $unitId)
                : $this->allowedUnits()->where('organizational_units.id', $unitId)->exists();
        }

        return true;
    }

    public function certificateConfig(): HasOne
    {
        return $this->hasOne(ActivityCertificateConfig::class);
    }

    public function passingScoreForIssuance(?CertificateType $type): ?float
    {
        if (! $type?->supportsScore()) {
            return null;
        }

        return $this->min_passing_score ?? throw ValidationException::withMessages([
            'min_passing_score' => 'Define la nota mínima de aprobación de la actividad antes de emitir certificados con calificación.',
        ]);
    }

    public function refreshParticipantEligibility(?int $beneficiaryId = null): void
    {
        $this->load(['certificateType', 'allowedUnits']);
        $issuedIds = $this->certificates()->notRevoked()->pluck('beneficiary_id')->flip();
        $minimum = $this->certificateType?->supportsScore() ? $this->min_passing_score : null;

        $participants = $this->participants()
            ->when($beneficiaryId !== null, fn ($query) => $query->where('beneficiaries.id', $beneficiaryId))
            ->get();

        foreach ($participants as $participant) {
            if ($issuedIds->has($participant->id)) {
                continue;
            }

            $allowed = $this->isCareerAllowed($participant->primary_organizational_unit_id);
            $reason = $allowed ? null : ($participant->primary_organizational_unit_id === null
                ? 'Carrera pendiente de identificación.'
                : 'Carrera no autorizada'.($this->resolution_number ? " según Resolución {$this->resolution_number}" : ' por el alcance del evento').'.');
            $score = $participant->pivot->score;
            $status = ! $allowed ? 'observado'
                : ($minimum !== null && $score !== null && (float) $score < $minimum ? 'reprobado' : 'inscrito');

            $this->participants()->updateExistingPivot($participant->id, [
                'career_eligible' => $allowed,
                'ineligibility_reason' => $reason,
                'status' => $status,
            ]);
        }
    }

    public function getFormattedDateRangeAttribute(): string
    {
        if ($this->start_date && $this->end_date) {
            if ($this->start_date->equalTo($this->end_date)) {
                return $this->start_date->translatedFormat('d \d\e F \d\e Y');
            }

            return 'Del '.$this->start_date->translatedFormat('d \d\e F').' al '.$this->end_date->translatedFormat('d \d\e F \d\e Y');
        }

        return '';
    }
}
