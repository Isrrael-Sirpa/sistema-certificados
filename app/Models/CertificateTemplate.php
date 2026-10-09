<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CertificateTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'category',
        'description',
        'view_path',
        'orientation',
        'background_image',
        'configuration',
        'current_version',
        'is_active',
        'is_default',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'current_version' => 'integer',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(Certificate::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CertificateTemplateVersion::class)->orderBy('version_number', 'desc');
    }

    public function currentVersionRecord(): ?CertificateTemplateVersion
    {
        return $this->versions()->where('version_number', $this->current_version)->first()
            ?? $this->versions()->first();
    }

    public function activityConfigs(): HasMany
    {
        return $this->hasMany(ActivityCertificateConfig::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Duplica la plantilla actual generando una nueva versión independiente.
     */
    public function duplicate(string $newName, ?User $user = null): self
    {
        $newSlug = Str::slug($newName) . '-' . Str::lower(Str::random(5));

        $newTemplate = self::create([
            'name' => $newName,
            'slug' => $newSlug,
            'category' => $this->category,
            'description' => "Copia creada a partir de: {$this->name}",
            'view_path' => $this->view_path,
            'orientation' => $this->orientation,
            'background_image' => $this->background_image,
            'configuration' => $this->configuration,
            'current_version' => 1,
            'is_active' => true,
            'is_default' => false,
            'created_by_user_id' => $user?->id,
        ]);

        // Clonar versión inicial
        $newTemplate->versions()->create([
            'version_number' => 1,
            'name' => "Versión Inicial (Duplicado)",
            'orientation' => $this->orientation,
            'design_schema' => $this->configuration ?? [],
            'change_summary' => "Duplicado desde {$this->name} (v{$this->current_version})",
            'created_by_user_id' => $user?->id,
        ]);

        return $newTemplate;
    }
}
