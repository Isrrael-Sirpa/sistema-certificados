<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationalUnit extends Model
{
    use HasFactory;

    public const TYPE_CARRERA = 'carrera';

    public const TYPE_DEPARTAMENTO = 'departamento';

    public const TYPE_INSTITUTO = 'instituto';

    public const TYPE_ADMINISTRATIVO = 'administrativo';

    public const TYPE_LABELS = [
        self::TYPE_CARRERA => 'Carrera',
        self::TYPE_DEPARTAMENTO => 'Departamento',
        self::TYPE_INSTITUTO => 'Instituto',
        self::TYPE_ADMINISTRATIVO => 'Unidad administrativa',
    ];

    protected $fillable = [
        'name',
        'code',
        'type',
        'faculty_name',
        'responsible_title',
        'responsible_name',
        'is_active',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPE_LABELS[$this->type] ?? 'Unidad';
    }

    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(CertificateTemplate::class);
    }

    public function beneficiaries(): HasMany
    {
        return $this->hasMany(Beneficiary::class, 'primary_organizational_unit_id');
    }

    public function allowedActivities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_allowed_units');
    }

    public function isCareer(): bool
    {
        return $this->type === self::TYPE_CARRERA;
    }

    public function isDepartment(): bool
    {
        return in_array($this->type, [self::TYPE_DEPARTAMENTO, self::TYPE_ADMINISTRATIVO, self::TYPE_INSTITUTO], true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCareers($query)
    {
        return $query->where('type', self::TYPE_CARRERA);
    }

    public function scopeDepartments($query)
    {
        return $query->where('type', '!=', self::TYPE_CARRERA);
    }
}
