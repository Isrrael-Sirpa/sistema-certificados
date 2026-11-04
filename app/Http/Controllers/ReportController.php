<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Certificate;
use App\Models\CertificateType;
use App\Models\OrganizationalUnit;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $startDate = $request->input('start_date', now()->startOfYear()->toDateString());
        $endDate = $request->input('end_date', now()->toDateString());
        $activityId = $request->input('activity_id');
        $typeId = $request->input('certificate_type_id');
        $status = $request->input('status');
        $unitId = $request->input('unit_id');

        $query = Certificate::forListing()->with(['beneficiary', 'activity', 'certificateType', 'generatedBy', 'organizationalUnit'])
            ->whereDate('issue_date', '>=', $startDate)
            ->whereDate('issue_date', '<=', $endDate);

        if ($user->isUnitScoped()) {
            $query->where('organizational_unit_id', $user->organizational_unit_id);
        } elseif ($unitId) {
            $query->where('organizational_unit_id', $unitId);
        }

        if ($activityId) {
            $query->where('activity_id', $activityId);
        }

        if ($typeId) {
            $query->where('certificate_type_id', $typeId);
        }

        if ($status) {
            $now = Carbon::now()->startOfDay();
            if ($status === 'VIGENTE') {
                $query->whereNull('revoked_at')->where('status', '!=', Certificate::STATUS_REVOCADO)
                    ->where(fn ($q) => $q->whereNull('expiration_date')->orWhere('expiration_date', '>=', $now));
            } elseif ($status === 'EXPIRADO') {
                $query->whereNull('revoked_at')->where('status', '!=', Certificate::STATUS_REVOCADO)->where('expiration_date', '<', $now);
            } elseif ($status === 'REVOCADO') {
                $query->where(fn ($q) => $q->whereNotNull('revoked_at')->orWhere('status', Certificate::STATUS_REVOCADO));
            }
        }

        $certificates = $query->latest('issue_date')->get();

        // Métricas resumidas para el reporte
        $total = $certificates->count();
        $totalVigentes = $certificates->filter(fn ($c) => $c->isValid())->count();
        $totalExpirados = $certificates->filter(fn ($c) => $c->isExpired())->count();
        $totalRevocados = $certificates->filter(fn ($c) => $c->isRevoked())->count();

        $activitiesQuery = Activity::orderBy('name');
        if ($user->isUnitScoped()) {
            $activitiesQuery->where('organizational_unit_id', $user->organizational_unit_id);
        }
        $activities = $activitiesQuery->get();
        $types = CertificateType::where('is_active', true)->get();
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();

        return view('reports.index', compact(
            'certificates',
            'startDate',
            'endDate',
            'activityId',
            'typeId',
            'status',
            'unitId',
            'total',
            'totalVigentes',
            'totalExpirados',
            'totalRevocados',
            'activities',
            'types',
            'units'
        ));
    }
}
