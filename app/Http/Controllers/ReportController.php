<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Beneficiary;
use App\Models\Certificate;
use App\Models\CertificateType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $activityId = $request->input('activity_id');
        $typeId = $request->input('certificate_type_id');
        $status = $request->input('status');

        $query = Certificate::with(['beneficiary', 'activity', 'certificateType', 'generatedBy'])
            ->whereBetween('issue_date', [$startDate, $endDate]);

        if ($activityId) {
            $query->where('activity_id', $activityId);
        }

        if ($typeId) {
            $query->where('certificate_type_id', $typeId);
        }

        if ($status) {
            $now = Carbon::now()->startOfDay();
            if ($status === 'VIGENTE') {
                $query->whereNull('revoked_at')->where('status', '!=', Certificate::STATUS_REVOCADO)->where('expiration_date', '>=', $now);
            } elseif ($status === 'EXPIRADO') {
                $query->whereNull('revoked_at')->where('status', '!=', Certificate::STATUS_REVOCADO)->where('expiration_date', '<', $now);
            } elseif ($status === 'REVOCADO') {
                $query->where(fn($q) => $q->whereNotNull('revoked_at')->orWhere('status', Certificate::STATUS_REVOCADO));
            }
        }

        $certificates = $query->latest('issue_date')->get();

        // Métricas resumidas para el reporte
        $total = $certificates->count();
        $totalVigentes = $certificates->filter(fn($c) => $c->isValid())->count();
        $totalExpirados = $certificates->filter(fn($c) => $c->isExpired())->count();
        $totalRevocados = $certificates->filter(fn($c) => $c->isRevoked())->count();

        $activities = Activity::orderBy('name')->get();
        $types = CertificateType::where('is_active', true)->get();

        return view('reports.index', compact(
            'certificates',
            'startDate',
            'endDate',
            'activityId',
            'typeId',
            'status',
            'total',
            'totalVigentes',
            'totalExpirados',
            'totalRevocados',
            'activities',
            'types'
        ));
    }
}
