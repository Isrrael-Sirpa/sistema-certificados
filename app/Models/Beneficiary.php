<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Beneficiary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'academic_title',
        'email',
        'phone',
        'institution_or_department',
        'notes',
    ];

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullTitleNameAttribute(): string
    {
        $prefix = $this->academic_title ? "{$this->academic_title} " : '';
        return trim("{$prefix}{$this->first_name} {$this->last_name}");
    }

    public function getFormattedDocumentAttribute(): string
    {
        return "{$this->document_type}: {$this->document_number}";
    }
}
