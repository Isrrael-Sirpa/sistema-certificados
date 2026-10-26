<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CertificateTemplate;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * Definiciones de configuración para el portal de verificación y parámetros globales.
     */
    protected array $settingDefinitions = [
        'portal_verify_title' => ['group' => 'verification_portal', 'display_name' => 'Título Principal de Verificación', 'type' => 'string'],
        'portal_verify_subtitle' => ['group' => 'verification_portal', 'display_name' => 'Subtítulo e Instrucciones del Portal', 'type' => 'text'],
        'portal_verify_tagline' => ['group' => 'verification_portal', 'display_name' => 'Etiqueta Superior Institucional', 'type' => 'string'],
        'portal_verify_code_placeholder' => ['group' => 'verification_portal', 'display_name' => 'Ejemplo de Código en el Buscador', 'type' => 'string'],
        'portal_verify_show_umsa_logo' => ['group' => 'verification_portal', 'display_name' => 'Mostrar Escudo Oficial de la UMSA', 'type' => 'boolean'],
        'portal_verify_show_ft_logo' => ['group' => 'verification_portal', 'display_name' => 'Mostrar Escudo de la Facultad de Tecnología', 'type' => 'boolean'],
        'portal_verify_show_qr_card' => ['group' => 'verification_portal', 'display_name' => 'Mostrar Tarjeta de Escaneo QR', 'type' => 'boolean'],
        'portal_verify_qr_card_title' => ['group' => 'verification_portal', 'display_name' => 'Título de la Tarjeta QR', 'type' => 'string'],
        'portal_verify_qr_card_desc' => ['group' => 'verification_portal', 'display_name' => 'Descripción de la Tarjeta QR', 'type' => 'string'],
        'portal_verify_show_validity_card' => ['group' => 'verification_portal', 'display_name' => 'Mostrar Tarjeta de Vigencia', 'type' => 'boolean'],
        'portal_verify_validity_card_title' => ['group' => 'verification_portal', 'display_name' => 'Título de la Tarjeta de Vigencia', 'type' => 'string'],
        'portal_verify_validity_card_desc' => ['group' => 'verification_portal', 'display_name' => 'Descripción de la Tarjeta de Vigencia', 'type' => 'string'],
    ];

    public function index()
    {
        $allSettings = Setting::all()->keyBy('key');
        $templates = CertificateTemplate::all();

        return view('settings.index', compact('allSettings', 'templates'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);
        $oldSettings = Setting::all()->pluck('value', 'key')->toArray();

        // Si se actualizaron conmutadores del portal de verificación, tratar los checkboxes ausentes como 0
        if ($request->has('tab_verification_portal')) {
            $booleanKeys = [
                'portal_verify_show_umsa_logo',
                'portal_verify_show_ft_logo',
                'portal_verify_show_qr_card',
                'portal_verify_show_validity_card',
            ];

            foreach ($booleanKeys as $bKey) {
                if (! isset($data[$bKey])) {
                    $data[$bKey] = '0';
                }
            }
            unset($data['tab_verification_portal']);
        }

        foreach ($data as $key => $value) {
            $def = $this->settingDefinitions[$key] ?? [];
            $group = $def['group'] ?? 'institutional';
            $displayName = $def['display_name'] ?? $key;
            $type = $def['type'] ?? 'string';

            $existing = Setting::where('key', $key)->first();
            if ($existing) {
                $existing->update(['value' => is_string($value) ? trim($value) : $value]);
            } else {
                Setting::create([
                    'key' => $key,
                    'value' => is_string($value) ? trim($value) : $value,
                    'group' => $group,
                    'display_name' => $displayName,
                    'type' => $type,
                ]);
            }
        }

        AuditLog::log('settings_updated', 'Configuraciones institucionales actualizadas', null, $oldSettings, $data);

        return back()->with('success', 'Configuración institucional guardada exitosamente.');
    }
}
