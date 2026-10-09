<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InstitutionalAsset;
use App\Services\AssetManagerService;
use Illuminate\Http\Request;

class InstitutionalAssetController extends Controller
{
    public function __construct(
        protected AssetManagerService $assetManager
    ) {}

    public function index(Request $request)
    {
        $type = $request->input('type');
        $query = InstitutionalAsset::orderBy('display_order')->orderBy('name');

        if ($type) {
            $query->where('type', $type);
        }

        $assets = $query->paginate(12)->withQueryString();
        $counts = [
            'total' => InstitutionalAsset::count(),
            'logo' => InstitutionalAsset::where('type', InstitutionalAsset::TYPE_LOGO)->count(),
            'signature' => InstitutionalAsset::where('type', InstitutionalAsset::TYPE_SIGNATURE)->count(),
            'stamp' => InstitutionalAsset::where('type', InstitutionalAsset::TYPE_STAMP)->count(),
            'watermark' => InstitutionalAsset::where('type', InstitutionalAsset::TYPE_WATERMARK)->count(),
        ];

        return view('assets.index', compact('assets', 'counts', 'type'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', 'string', 'in:logo,signature,stamp,watermark,background,decorative'],
            'file' => ['required', 'file', 'mimes:svg,png,jpg,jpeg,webp,gif,bmp', 'max:5120'],
            'authority_name' => ['nullable', 'string', 'max:150'],
            'authority_title' => ['nullable', 'string', 'max:150'],
            'institution' => ['nullable', 'string', 'max:150'],
            'is_mandatory' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $asset = $this->assetManager->storeAsset($request->file('file'), $validated, $request->user());

        AuditLog::log(
            'asset_created',
            "Recurso institucional '{$asset->name}' ({$asset->type}) subido al sistema",
            $asset
        );

        return back()->with('success', "Recurso '{$asset->name}' registrado correctamente en la biblioteca institucional.");
    }

    public function toggleStatus(InstitutionalAsset $asset)
    {
        $asset->is_active = !$asset->is_active;
        $asset->save();

        AuditLog::log(
            'asset_updated',
            "Estado del recurso '{$asset->name}' cambiado a " . ($asset->is_active ? 'Activo' : 'Inactivo'),
            $asset
        );

        return back()->with('success', "Estado del recurso actualizado.");
    }

    public function destroy(InstitutionalAsset $asset)
    {
        $name = $asset->name;
        $asset->delete();

        AuditLog::log('asset_deleted', "Recurso '{$name}' eliminado de la biblioteca", null);

        return back()->with('success', "Recurso '{$name}' eliminado correctamente.");
    }
}
