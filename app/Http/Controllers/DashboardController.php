<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateVerification;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $now = Carbon::now()->startOfDay();

        $totalCertificates = Certificate::count();
        $totalRevoked = Certificate::whereNotNull('revoked_at')->orWhere('status', Certificate::STATUS_REVOCADO)->count();
        $totalExpired = Certificate::whereNull('revoked_at')
            ->where('status', '!=', Certificate::STATUS_REVOCADO)
            ->where('expiration_date', '<', $now)
            ->count();
        
        $totalActive = Certificate::whereNull('revoked_at')
            ->where('status', '!=', Certificate::STATUS_REVOCADO)
            ->where('expiration_date', '>=', $now)
            ->count();

        $totalBeneficiaries = Beneficiary::count();
        $totalActivities = Activity::count();
        $totalVerifications = CertificateVerification::count();

        $recentCertificates = Certificate::with(['beneficiary', 'activity', 'certificateType', 'generatedBy'])
            ->latest('id')
            ->take(8)
            ->get();

        $recentVerifications = CertificateVerification::with('certificate.beneficiary')
            ->latest('id')
            ->take(6)
            ->get();

        $settings = Setting::all()->pluck('value', 'key')->toArray();

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
            'settings'
        ));
    }
}
