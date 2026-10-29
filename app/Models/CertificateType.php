<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateType extends Model
{
    use HasFactory;

    public const VALIDITY_PERMANENT = 'permanent';

    public const VALIDITY_YEARS = 'years';

    public const VALIDITY_CUSTOM = 'custom';

    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'validity_mode',
        'default_validity_years',
        'has_score',
        'score_scale',
        'min_passing_score',
        'icon',
        'badge_color',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'has_score' => 'boolean',
            'default_validity_years' => 'integer',
            'min_passing_score' => 'float',
        ];
    }

    public function isPermanentByDefault(): bool
    {
        return $this->validity_mode === self::VALIDITY_PERMANENT;
    }

    public function supportsScore(): bool
    {
        return (bool) $this->has_score;
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }
}
