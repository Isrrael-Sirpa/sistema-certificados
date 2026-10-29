<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CertificateVerification;
use App\Models\Setting;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    /**
     * Muestra el formulario público para introducir manualmente el código del certificado o escanear con cámara.
     */
    public function index()
    {
        $settings = $this->getMergedSettings();

        return view('public.verify-search', compact('settings'));
    }

    /**
     * Procesa la búsqueda manual por código.
     */
    public function search(Request $request)
    {
        $request->validate([
            'certificate_code' => ['required', 'string'],
        ]);

        $code = strtoupper(trim($request->input('certificate_code')));
        $certificate = Certificate::where('certificate_code', $code)->first();

        if (! $certificate) {
            return back()->withInput()->with('error', "No se encontró ningún certificado registrado con el código '{$code}'. Verifique que esté escrito exactamente como figura en el documento.");
        }

        return redirect()->route('verification.show', ['token' => $certificate->verification_token, 'method' => 'manual_code']);
    }

    /**
     * Muestra la página pública de verificación institucional con el estado en tiempo real.
     */
    public function show(Request $request, string $token)
    {
        $certificate = Certificate::with(['beneficiary', 'activity', 'certificateType', 'certificateTemplate'])
            ->where('verification_token', $token)
            ->first();

        $settings = $this->getMergedSettings();

        if (! $certificate) {
            return response()->view('public.verify-not-found', [
                'token' => $token,
                'settings' => $settings,
            ], 404);
        }

        $method = $request->query('method', 'qr_scan');

        // Registrar consulta pública en el log de verificaciones
        CertificateVerification::create([
            'certificate_id' => $certificate->id,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'verification_method' => $method,
            'verified_at' => now(),
        ]);

        return view('public.verify-result', compact('certificate', 'settings'));
    }

    /**
     * Retorna las configuraciones institucionales combinadas con valores seguros por defecto.
     */
    protected function getMergedSettings(): array
    {
        $raw = Setting::all()->pluck('value', 'key')->toArray();

        $defaults = [
            'portal_verify_title' => 'Verificación Oficial de Certificados',
            'portal_verify_subtitle' => 'Consulte la autenticidad, mérito académico, calificaciones y vigencia oficial de certificados emitidos por el Vicedecanato.',
            'portal_verify_tagline' => 'Universidad Mayor de San Andrés • Facultad de Tecnología',
            'portal_verify_code_placeholder' => 'EJ: CERT-2026-000001',
            'portal_verify_show_umsa_logo' => '1',
            'portal_verify_show_ft_logo' => '1',
            'portal_verify_show_qr_card' => '1',
            'portal_verify_qr_card_title' => 'Escaneo QR Instantáneo',
            'portal_verify_qr_card_desc' => 'Escanee con la cámara de su dispositivo móvil o computadora para validación directa e inmediata sin teclear.',
            'portal_verify_show_validity_card' => '1',
            'portal_verify_validity_card_title' => 'Vigencia Institucional',
            'portal_verify_validity_card_desc' => 'Certificados con vigencia permanente o validez reglamentaria según el tipo de mérito académico acreditado.',
            'portal_verify_show_score_badge' => '1',
            'portal_verify_show_type_badge' => '1',
            'portal_verify_show_validity_badge' => '1',
            'portal_verify_show_types_card' => '1',
            'portal_verify_types_card_title' => 'Tipos de Certificación Institucional',
            'portal_verify_types_card_desc' => 'Acreditaciones oficiales emitidas por la Facultad: Aprobación con evaluación, Asistencia, Disertante y Reconocimientos Vitalicios.',
            'portal_verify_footer_note' => 'Documento oficial debidamente validado y registrado en la base de datos institucional del Vicedecanato de la Facultad de Tecnología - UMSA.',
            'institution_name' => 'UNIVERSIDAD MAYOR DE SAN ANDRÉS',
            'faculty_name' => 'FACULTAD DE TECNOLOGÍA',
            'department_name' => 'VICEDECANATO',
            'faculty_motto' => 'Ciencia, cálculo y jerarquía Adelante Tecnología',
            'institution_address' => 'Av. Arce No. 2295',
            'institution_city' => 'La Paz - Bolivia',
        ];

        return array_merge($defaults, $raw);
    }
}
