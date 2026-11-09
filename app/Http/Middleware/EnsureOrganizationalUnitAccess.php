<?php

namespace App\Http\Middleware;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\CertificateTemplate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationalUnitAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Su cuenta se encuentra desactivada por la administración.']);
        }

        if (! $user?->isUnitScoped() || $request->routeIs('certificates.download', 'certificates.preview', 'activities.download-zip')) {
            return $next($request);
        }

        abort_if($user->organizational_unit_id === null, 403, 'Su cuenta requiere una carrera o departamento asignado. Contacte al administrador.');

        $activity = $request->route('activity');
        if (! $activity instanceof Activity && $request->routeIs('certificates.store', 'certificates.preview-draft')
            && $request->filled('activity_id') && is_scalar($request->input('activity_id'))) {
            $activity = Activity::find($request->input('activity_id'));
        }

        if ($activity instanceof Activity) {
            abort_unless($user->canAccessOrganizationalUnit($activity->organizational_unit_id), 403, 'No puede gestionar actividades de otra carrera o unidad.');
        }

        $beneficiary = $request->route('beneficiary');
        if ($beneficiary instanceof Beneficiary && ! $activity instanceof Activity) {
            $ownsBeneficiary = $user->canAccessOrganizationalUnit($beneficiary->primary_organizational_unit_id);
            $canView = $request->isMethod('GET') && $request->routeIs('beneficiaries.show')
                && $beneficiary->certificates()->where('organizational_unit_id', $user->organizational_unit_id)->exists();

            abort_unless($ownsBeneficiary || $canView, 403, 'No puede modificar estudiantes de otra carrera o unidad.');
            if (! $request->isMethod('GET') && $request->filled('primary_organizational_unit_id')) {
                abort_unless($user->canAccessOrganizationalUnit((int) $request->input('primary_organizational_unit_id')), 403, 'Solo el administrador puede trasladar un estudiante a otra unidad.');
            }
        }

        $template = $request->route('template');
        if (! $template instanceof CertificateTemplate && $request->filled('certificate_template_id') && is_scalar($request->input('certificate_template_id'))) {
            $template = CertificateTemplate::find($request->input('certificate_template_id'));
        }

        if ($template instanceof CertificateTemplate && $template->organizational_unit_id !== null) {
            abort_unless($user->canAccessOrganizationalUnit($template->organizational_unit_id), 403, 'No puede utilizar plantillas privadas de otra carrera o unidad.');
        }

        return $next($request);
    }
}
