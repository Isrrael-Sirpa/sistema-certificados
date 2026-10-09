<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CertificateTemplate;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->groupBy('group');
        $templates = CertificateTemplate::all();
        return view('settings.index', compact('settings', 'templates'));
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token', '_method']);
        $oldSettings = Setting::all()->pluck('value', 'key')->toArray();

        foreach ($data as $key => $value) {
            Setting::where('key', $key)->update(['value' => $value]);
        }

        AuditLog::log('settings_updated', 'Configuraciones institucionales actualizadas', null, $oldSettings, $data);

        return back()->with('success', 'Configuración institucional guardada exitosamente.');
    }
}
