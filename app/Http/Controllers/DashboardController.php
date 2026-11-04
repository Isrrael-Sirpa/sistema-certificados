<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateVerification;
use App\Models\OrganizationalUnit;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now()->startOfDay();

        $certQuery = Certificate::query();
        $activityQuery = Activity::query();

        if ($user && $user->isUnitScoped()) {
            $unitId = $user->organizational_unit_id;
            $certQuery->where('organizational_unit_id', $unitId);
            $activityQuery->where('organizational_unit_id', $unitId);
        }

        $totalCertificates = (clone $certQuery)->count();
        $totalRevoked = (clone $certQuery)->where(function ($q) {
            $q->whereNotNull('revoked_at')->orWhere('status', Certificate::STATUS_REVOCADO);
        })->count();

        $totalExpired = (clone $certQuery)->whereNull('revoked_at')
            ->where('status', '!=', Certificate::STATUS_REVOCADO)
            ->whereNotNull('expiration_date')
            ->where('expiration_date', '<', $now)
            ->count();

        $totalActive = (clone $certQuery)->whereNull('revoked_at')
            ->where('status', '!=', Certificate::STATUS_REVOCADO)
            ->where(function ($q) use ($now) {
                $q->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>=', $now);
            })
            ->count();

        $totalBeneficiaries = $user && $user->isUnitScoped()
            ? Beneficiary::where('primary_organizational_unit_id', $user->organizational_unit_id)->count()
            : Beneficiary::count();

        $totalActivities = $activityQuery->count();
        $verificationQuery = CertificateVerification::query();
        if ($user && $user->isUnitScoped()) {
            $verificationQuery->whereHas('certificate', fn ($q) => $q->where('organizational_unit_id', $user->organizational_unit_id));
        }
        $totalVerifications = (clone $verificationQuery)->count();

        $recentCertificates = (clone $certQuery)->forListing()->with(['beneficiary', 'activity', 'certificateType', 'generatedBy', 'organizationalUnit'])
            ->latest('id')
            ->take(8)
            ->get();

        $recentVerifications = $verificationQuery->with(['certificate' => fn ($query) => $query->forListing()->with('beneficiary')])
            ->latest('id')
            ->take(6)
            ->get();

        $settings = Setting::all()->pluck('value', 'key')->toArray();
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();

        return view('dashboard.index', compact(
            'totalCertificates',
            'totalActive',
            'totalExpired',
            'totalRevoked',
            'totalBeneficiaries',
            'totalActivities',
            'totalVerifications',
            'recentCertificates',
            'recentVerifications',
            'settings',
            'units'
        ));
    }
}
