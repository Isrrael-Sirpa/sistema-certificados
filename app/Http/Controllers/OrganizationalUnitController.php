<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\OrganizationalUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrganizationalUnitController extends Controller
{
    /**
     * Muestra la lista de Carreras y Departamentos de la Facultad de Tecnología.
     */
    public function index(Request $request): View
    {
        $query = OrganizationalUnit::query()
            ->withCount(['users', 'activities', 'certificates', 'templates']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('responsible_name', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('type')) {
            if ($type !== 'all') {
                $query->where('type', $type);
            }
        }

        if ($status = $request->input('status')) {
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $units = $query->orderBy('type')->orderBy('name')->paginate(15)->withQueryString();

        $stats = [
            'total_carreras' => OrganizationalUnit::where('type', OrganizationalUnit::TYPE_CARRERA)->count(),
            'total_departamentos' => OrganizationalUnit::where('type', '!=', OrganizationalUnit::TYPE_CARRERA)->count(),
            'total_active' => OrganizationalUnit::where('is_active', true)->count(),
            'total_inactive' => OrganizationalUnit::where('is_active', false)->count(),
            'total_certificates' => Certificate::count(),
        ];

        return view('units.index', compact('units', 'stats'));
    }

    /**
     * Registra una nueva Carrera o Departamento.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:organizational_units,name',
            'code' => 'required|string|max:50|unique:organizational_units,code',
            'type' => ['required', Rule::in(array_keys(OrganizationalUnit::TYPE_LABELS))],
            'faculty_name' => 'nullable|string|max:150',
            'responsible_title' => 'nullable|string|max:100',
            'responsible_name' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['faculty_name'] = $validated['faculty_name'] ?? 'Facultad de Tecnología';

        $unit = OrganizationalUnit::create($validated);

        AuditLog::log(
            action: 'ORGANIZATIONAL_UNIT_CREATED',
            description: "Creó la unidad organizativa: {$unit->name} ({$unit->code})",
            entity: $unit,
            newValues: $unit->toArray()
        );

        return redirect()->route('carreras-departamentos.index')
            ->with('success', "La unidad '{$unit->name}' fue creada exitosamente.");
    }

    /**
     * Actualiza los datos de una Carrera o Departamento.
     */
    public function update(Request $request, OrganizationalUnit $carreras_departamento): RedirectResponse
    {
        $unit = $carreras_departamento;

        $validated = $request->validate([
            'name' => 'required|string|max:150|unique:organizational_units,name,'.$unit->id,
            'code' => 'required|string|max:50|unique:organizational_units,code,'.$unit->id,
            'type' => ['required', Rule::in(array_keys(OrganizationalUnit::TYPE_LABELS))],
            'faculty_name' => 'nullable|string|max:150',
            'responsible_title' => 'nullable|string|max:100',
            'responsible_name' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:500',
        ]);

        $oldValues = $unit->toArray();
        $unit->update($validated);

        AuditLog::log(
            action: 'ORGANIZATIONAL_UNIT_UPDATED',
            description: "Actualizó datos de la unidad: {$unit->name}",
            entity: $unit,
            oldValues: $oldValues,
            newValues: $unit->toArray()
        );

        return redirect()->route('carreras-departamentos.index')
            ->with('success', "La unidad '{$unit->name}' fue actualizada correctamente.");
    }

    /**
     * Alterna el estado activo/inactivo de una Carrera o Departamento.
     * La desactivación resguarda íntegramente los certificados y actividades emitidas históricamente.
     */
    public function toggleStatus(OrganizationalUnit $carreras_departamento): RedirectResponse
    {
        $unit = $carreras_departamento;
        $unit->is_active = ! $unit->is_active;
        $unit->save();

        $statusStr = $unit->is_active ? 'activada' : 'desactivada temporalmente';

        AuditLog::log(
            action: 'ORGANIZATIONAL_UNIT_STATUS_TOGGLED',
            description: "Cambió estado de la unidad {$unit->name} a: {$statusStr}",
            entity: $unit,
            newValues: ['is_active' => $unit->is_active]
        );

        return redirect()->route('carreras-departamentos.index')
            ->with('success', "La unidad '{$unit->name}' ha sido {$statusStr}. Los registros históricos se mantienen protegidos.");
    }
}
