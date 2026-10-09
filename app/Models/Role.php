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

    public function isAdmin(): bool
    {
        return $this->name === self::ADMIN;
    }

    public function isOperator(): bool
    {
        return $this->name === self::OPERATOR;
    }
}
