<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'activity_type',
        'start_date',
        'end_date',
        'academic_hours',
        'location',
        'organizer',
        'responsible_authority',
        'certificate_type_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'academic_hours' => 'integer',
        ];
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
            ->withPivot(['id', 'role', 'status', 'score', 'score_label'])
            ->withTimestamps();
    }

    public function certificateConfig(): HasOne
    {
        return $this->hasOne(ActivityCertificateConfig::class);
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
