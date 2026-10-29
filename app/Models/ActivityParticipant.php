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
        'score',
        'score_label',
    ];

    protected function casts(): array
    {
        return [
            'score' => 'float',
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

    public function isPassing(?float $minScore = 51.0): bool
    {
        if ($this->score === null) {
            return false;
        }

        return (float) $this->score >= ($minScore ?? 51.0);
    }

    public function isFailing(?float $minScore = 51.0): bool
    {
        if ($this->score === null) {
            return false;
        }

        return (float) $this->score < ($minScore ?? 51.0);
    }
}
