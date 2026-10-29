<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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
        'portal_verify_show_score_badge' => ['group' => 'verification_portal', 'display_name' => 'Mostrar Placa de Calificación y Notas', 'type' => 'boolean'],
        'portal_verify_show_type_badge' => ['group' => 'verification_portal', 'display_name' => 'Mostrar Modalidad e Insignia del Certificado', 'type' => 'boolean'],
        'portal_verify_show_validity_badge' => ['group' => 'verification_portal', 'display_name' => 'Mostrar Distintivo de Vigencia Oficial', 'type' => 'boolean'],
        'portal_verify_show_types_card' => ['group' => 'verification_portal', 'display_name' => 'Mostrar Tarjeta de Tipos de Certificados', 'type' => 'boolean'],
        'portal_verify_types_card_title' => ['group' => 'verification_portal', 'display_name' => 'Título de la Tarjeta de Tipos', 'type' => 'string'],
        'portal_verify_types_card_desc' => ['group' => 'verification_portal', 'display_name' => 'Descripción de la Tarjeta de Tipos', 'type' => 'string'],
        'portal_verify_footer_note' => ['group' => 'verification_portal', 'display_name' => 'Mensaje Institucional al Pie', 'type' => 'text'],
    ];

    public function index()
    {
        $allSettings = Setting::all()->keyBy('key');
        $templates = CertificateTemplate::all();
        $types = CertificateType::orderBy('id')->get();

        return view('settings.index', compact('allSettings', 'templates', 'types'));
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
                'portal_verify_show_score_badge',
                'portal_verify_show_type_badge',
                'portal_verify_show_validity_badge',
                'portal_verify_show_types_card',
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

    public function storeCertificateType(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50', 'unique:certificate_types,code'],
            'description' => ['nullable', 'string', 'max:500'],
            'validity_mode' => ['required', 'in:permanent,years'],
            'default_validity_years' => ['nullable', 'integer', 'min:1', 'max:50'],
            'has_score' => ['nullable', 'boolean'],
            'score_scale' => ['nullable', 'string', 'max:100'],
            'min_passing_score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:10'],
            'badge_color' => ['nullable', 'string', 'max:30'],
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = Str::slug($validated['name'], '_');
        }

        $validated['has_score'] = (bool) ($request->has('has_score'));
        $validated['is_active'] = true;

        if ($validated['validity_mode'] === 'permanent') {
            $validated['default_validity_years'] = null;
        } elseif (empty($validated['default_validity_years'])) {
            $validated['default_validity_years'] = 3;
        }

        $type = CertificateType::create($validated);

        AuditLog::log('certificate_type_created', "Tipo de certificado '{$type->name}' creado", $type);

        return redirect()->route('settings.index', ['tab' => 'certificates'])
            ->with('success', "Tipo de certificado '{$type->name}' registrado exitosamente.");
    }

    public function updateCertificateType(Request $request, CertificateType $type)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'validity_mode' => ['required', 'in:permanent,years'],
            'default_validity_years' => ['nullable', 'integer', 'min:1', 'max:50'],
            'has_score' => ['nullable', 'boolean'],
            'score_scale' => ['nullable', 'string', 'max:100'],
            'min_passing_score' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:10'],
            'badge_color' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['has_score'] = (bool) ($request->has('has_score'));
        $validated['is_active'] = (bool) ($request->has('is_active'));

        if ($validated['validity_mode'] === 'permanent') {
            $validated['default_validity_years'] = null;
        } elseif (empty($validated['default_validity_years'])) {
            $validated['default_validity_years'] = 3;
        }

        $type->update($validated);

        AuditLog::log('certificate_type_updated', "Políticas del tipo '{$type->name}' actualizadas", $type);

        return redirect()->route('settings.index', ['tab' => 'certificates'])
            ->with('success', "Políticas del certificado '{$type->name}' actualizadas exitosamente.");
    }

    public function toggleCertificateType(CertificateType $type)
    {
        $type->update(['is_active' => ! $type->is_active]);
        $statusStr = $type->is_active ? 'activado' : 'desactivado';

        AuditLog::log('certificate_type_toggled', "Tipo de certificado '{$type->name}' {$statusStr}", $type);

        return redirect()->route('settings.index', ['tab' => 'certificates'])
            ->with('success', "Tipo de certificado '{$type->name}' {$statusStr}.");
    }
}
