<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InstitutionalAsset extends Model
{
    use HasFactory;

    public const TYPE_LOGO = 'logo';
    public const TYPE_SIGNATURE = 'signature';
    public const TYPE_STAMP = 'stamp';
    public const TYPE_WATERMARK = 'watermark';
    public const TYPE_BACKGROUND = 'background';
    public const TYPE_DECORATIVE = 'decorative';

    protected $fillable = [
        'name',
        'type',
        'file_path',
        'mime_type',
        'metadata',
        'is_mandatory',
        'is_active',
        'display_order',
        'created_by_user_id',
    ];

    protected $appends = [
        'url',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_mandatory' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getUrlAttribute(): string
    {
        // 1. Si existe físicamente en public/ (ej. recursos predeterminados como public/assets/institutional/logo_umsa.svg)
        if (file_exists(public_path($this->file_path))) {
            return asset($this->file_path);
        }

        // 2. Si fue cargado dinámicamente y reside en storage/app/public/
        return Storage::disk('public')->url($this->file_path);
    }

    /**
     * Retorna el recurso codificado en Data URI Base64 optimizado para DomPDF.
     */
    public function getBase64DataUriAttribute(): ?string
    {
        if (file_exists(public_path($this->file_path))) {
            $content = file_get_contents(public_path($this->file_path));
            $ext = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
            $mime = match ($ext) {
                'svg' => 'image/svg+xml',
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                default => 'image/png',
            };
            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        if (Storage::disk('public')->exists($this->file_path)) {
            $content = Storage::disk('public')->get($this->file_path);
            $mime = $this->mime_type ?? 'image/png';
            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        return null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('display_order')->orderBy('name');
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
