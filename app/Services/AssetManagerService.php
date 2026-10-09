<?php

namespace App\Services;

use App\Models\InstitutionalAsset;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AssetManagerService
{
    /**
     * Sube y registra un recurso institucional en disco y base de datos.
     * Admite archivos PNG, JPG, JPEG, SVG y WebP.
     */
    public function storeAsset(UploadedFile $file, array $data, ?User $user = null): InstitutionalAsset
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $safeName = Str::slug($data['name']) . '-' . Str::random(6) . '.' . $extension;
        $path = $file->storeAs('assets/institutional', $safeName, 'public');

        $metadata = $data['metadata'] ?? [];
        
        // Extraer dimensiones de la imagen para respetar proporciones en el lienzo
        if (in_array($extension, ['png', 'jpg', 'jpeg', 'webp', 'gif', 'bmp']) && file_exists($file->getRealPath())) {
            $imgSize = @getimagesize($file->getRealPath());
            if ($imgSize) {
                $metadata['width'] = $imgSize[0];
                $metadata['height'] = $imgSize[1];
                $metadata['aspect_ratio'] = round($imgSize[0] / max($imgSize[1], 1), 3);
            }
        }

        if ($data['type'] === InstitutionalAsset::TYPE_SIGNATURE) {
            $metadata['authority_name'] = $data['authority_name'] ?? '';
            $metadata['authority_title'] = $data['authority_title'] ?? '';
            $metadata['institution'] = $data['institution'] ?? '';
        }

        return InstitutionalAsset::create([
            'name' => $data['name'],
            'type' => $data['type'],
            'file_path' => $path,
            'mime_type' => $file->getMimeType(),
            'metadata' => $metadata,
            'is_mandatory' => !empty($data['is_mandatory']),
            'is_active' => true,
            'display_order' => $data['display_order'] ?? 0,
            'created_by_user_id' => $user?->id,
        ]);
    }

    /**
     * Retorna el contenido de un recurso en Data URI Base64 para incrustación segura y nítida en DomPDF.
     * Acepta un ID numérico de InstitutionalAsset o una ruta de archivo relativa.
     */
    public function getAssetBase64(string|int|null $pathOrId): ?string
    {
        if (empty($pathOrId)) {
            return null;
        }

        // Si es un ID numérico de InstitutionalAsset
        if (is_numeric($pathOrId)) {
            $asset = InstitutionalAsset::find($pathOrId);
            if ($asset) {
                $pathOrId = $asset->file_path;
            } else {
                return null;
            }
        }

        $cleanPath = ltrim($pathOrId, '/\\');
        $storagePath = preg_replace('/^storage\//', '', $cleanPath);

        // 1. Verificar en disco public de Storage
        if (Storage::disk('public')->exists($storagePath)) {
            $mime = Storage::disk('public')->mimeType($storagePath) ?? $this->detectMimeByExtension($storagePath);
            $content = Storage::disk('public')->get($storagePath);
            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        // 2. Verificar en public_path() directo
        $publicPath = public_path($cleanPath);
        if (File::exists($publicPath)) {
            $mime = File::mimeType($publicPath) ?? $this->detectMimeByExtension($cleanPath);
            $content = File::get($publicPath);
            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        // 3. Verificar en public/storage/
        $publicStoragePath = public_path('storage/' . $storagePath);
        if (File::exists($publicStoragePath)) {
            $mime = File::mimeType($publicStoragePath) ?? $this->detectMimeByExtension($storagePath);
            $content = File::get($publicStoragePath);
            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }

        return null;
    }

    /**
     * Detección auxiliar de tipo MIME por extensión de archivo.
     */
    protected function detectMimeByExtension(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'svg' => 'image/svg+xml',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            default => 'image/png',
        };
    }
}
