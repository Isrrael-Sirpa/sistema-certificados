<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityCertificateConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'certificate_template_id',
        'custom_title',
        'custom_body_text',
        'primary_color',
        'secondary_color',
        'selected_asset_ids',
        'custom_signatures',
        'custom_fields_schema',
    ];

    protected function casts(): array
    {
        return [
            'selected_asset_ids' => 'array',
            'custom_signatures' => 'array',
            'custom_fields_schema' => 'array',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class, 'certificate_template_id');
    }
}
