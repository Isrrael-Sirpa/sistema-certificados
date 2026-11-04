<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'role_id',
        'organizational_unit_id',
        'name',
        'email',
        'password',
        'is_active',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function organizationalUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationalUnit::class);
    }

    public function generatedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'generated_by_user_id');
    }

    public function revokedCertificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'revoked_by_user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function isAdmin(): bool
    {
        return $this->role && $this->role->name === Role::ADMIN;
    }

    public function isSuperAdmin(): bool
    {
        return $this->isAdmin();
    }

    public function isOperator(): bool
    {
        return $this->role && $this->role->name === Role::OPERATOR;
    }

    public function isJefeCarrera(): bool
    {
        return $this->role && $this->role->name === Role::JEFE_CARRERA;
    }

    public function isSecretaria(): bool
    {
        return $this->role && $this->role->name === Role::SECRETARIA;
    }

    public function isUnitScoped(): bool
    {
        return $this->isJefeCarrera() || $this->isSecretaria() || ($this->isOperator() && ! empty($this->organizational_unit_id));
    }

    public function canAccessReports(): bool
    {
        return $this->isAdmin() || $this->isJefeCarrera();
    }

    public function canAccessOrganizationalUnit(?int $unitId): bool
    {
        return ! $this->isUnitScoped()
            || ($this->organizational_unit_id !== null && $unitId !== null
                && (int) $this->organizational_unit_id === $unitId);
    }

    public function canManageTemplates(): bool
    {
        return $this->isAdmin() || $this->isJefeCarrera() || $this->isSecretaria();
    }

    public function isUnitActive(): bool
    {
        if ($this->isAdmin() || empty($this->organizational_unit_id)) {
            return true;
        }

        return (bool) ($this->organizationalUnit && $this->organizationalUnit->is_active);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }
}
