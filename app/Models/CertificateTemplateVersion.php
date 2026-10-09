<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CertificateTemplateVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_template_id',
        'version_number',
        'name',
        'orientation',
        'design_schema',
        'change_summary',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'design_schema' => 'array',
        ];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class, 'template_version_id');
    }
}
