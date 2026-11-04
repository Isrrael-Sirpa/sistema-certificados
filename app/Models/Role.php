<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public const ADMIN = 'admin';

    public const OPERATOR = 'operator';

    public const JEFE_CARRERA = 'jefe_carrera';

    public const SECRETARIA = 'secretaria';

    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->name === self::OPERATOR;
    }

    public function isJefeCarrera(): bool
    {
        return $this->name === self::JEFE_CARRERA;
    }

    public function isSecretaria(): bool
    {
        return $this->name === self::SECRETARIA;
    }
}
