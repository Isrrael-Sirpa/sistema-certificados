<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Models\OrganizationalUnit;
use App\Services\BeneficiaryImportService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BeneficiaryController extends Controller
{
    public function __construct(
        protected BeneficiaryImportService $importService
    ) {}

    public function index(Request $request)
    {
        $user = $request->user();
        $query = Beneficiary::with(['primaryOrganizationalUnit'])->withCount('certificates');

        if ($user->isUnitScoped()) {
            // Ver estudiantes pertenecientes a su carrera o que tengan certificados emitidos por su carrera
            $unitId = $user->organizational_unit_id;
            $query->where(function ($q) use ($unitId) {
                $q->where('primary_organizational_unit_id', $unitId)
                    ->orWhereHas('certificates', function ($c) use ($unitId) {
                        $c->where('organizational_unit_id', $unitId);
                    });
            });
        } elseif ($request->filled('unit_id')) {
            $query->where('primary_organizational_unit_id', $request->input('unit_id'));
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $beneficiaries = $query->orderBy('last_name')->orderBy('first_name')->paginate(15)->withQueryString();
        $units = OrganizationalUnit::active()->orderBy('type')->orderBy('name')->get();

        return view('beneficiaries.index', compact('beneficiaries', 'units'));
    }

    public function create(Request $request): View
    {
        $units = OrganizationalUnit::active()
            ->when($request->user()->isUnitScoped(), fn ($query) => $query->whereKey($request->user()->organizational_unit_id))
            ->orderBy('type')->orderBy('name')->get();

        return view('beneficiaries.create', compact('units'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->enforceAssignedUnit($request);
        $documentType = $request->input('document_type');
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:30'],
            'document_number' => ['required', 'string', 'max:50', Rule::unique('beneficiaries', 'document_number')->where('document_type', is_string($documentType) ? $documentType : null)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'academic_title' => ['nullable', 'string', 'max:50'],
            'primary_organizational_unit_id' => ['required', 'integer', 'exists:organizational_units,id'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'institution_or_department' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ], [
            'primary_organizational_unit_id.required' => 'Seleccione la carrera o departamento al que pertenece el beneficiario.',
            'primary_organizational_unit_id.exists' => 'Seleccione una carrera o departamento válido.',
            'document_number.unique' => 'Este documento ya está registrado en el padrón institucional.',
        ]);

        $beneficiary = Beneficiary::create($validated);

        AuditLog::log('beneficiary_created', "Beneficiario {$beneficiary->full_name} registrado en el padrón", $beneficiary);

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Beneficiario registrado exitosamente en el padrón.');
    }

    public function show(Beneficiary $beneficiary)
    {
        $beneficiary->load([
            'primaryOrganizationalUnit',
            'certificates' => function ($q) {
                $q->forListing()->with(['activity.allowedUnits', 'activity.organizationalUnit', 'certificateType', 'certificateTemplate:id,name', 'organizationalUnit'])
                    ->latest('issue_date')->latest('id');
            },
            'activities' => function ($q) {
                $q->with(['organizationalUnit', 'allowedUnits'])->latest('start_date');
            },
        ]);

        return view('beneficiaries.show', compact('beneficiary'));
    }

    public function edit(Request $request, Beneficiary $beneficiary): View
    {
        $units = OrganizationalUnit::where(function ($query) use ($beneficiary) {
            $query->where('is_active', true)->orWhere('id', $beneficiary->primary_organizational_unit_id);
        })->when($request->user()->isUnitScoped(), fn ($query) => $query->whereKey($request->user()->organizational_unit_id))
            ->orderBy('type')->orderBy('name')->get();

        return view('beneficiaries.edit', compact('beneficiary', 'units'));
    }

    public function update(Request $request, Beneficiary $beneficiary): RedirectResponse
    {
        $this->enforceAssignedUnit($request);
        $documentType = $request->input('document_type');
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:30'],
            'document_number' => ['required', 'string', 'max:50', Rule::unique('beneficiaries', 'document_number')->ignore($beneficiary)->where('document_type', is_string($documentType) ? $documentType : null)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'academic_title' => ['nullable', 'string', 'max:50'],
            'primary_organizational_unit_id' => ['required', 'integer', 'exists:organizational_units,id'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'institution_or_department' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ], [
            'primary_organizational_unit_id.required' => 'Seleccione la carrera o departamento al que pertenece el beneficiario.',
            'primary_organizational_unit_id.exists' => 'Seleccione una carrera o departamento válido.',
            'document_number.unique' => 'Este documento ya está registrado en el padrón institucional.',
        ]);

        $old = $beneficiary->toArray();
        $beneficiary->update($validated);

        AuditLog::log('beneficiary_updated', "Beneficiario {$beneficiary->full_name} actualizado", $beneficiary, $old, $beneficiary->toArray());

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Datos del beneficiario actualizados.');
    }

    private function enforceAssignedUnit(Request $request): void
    {
        $user = $request->user();
        if (! $user->isUnitScoped()) {
            return;
        }

        $unitId = $user->organizational_unit_id;
        abort_if($unitId === null, 403, 'Su cuenta requiere una carrera o departamento asignado. Contacte al administrador.');
        $submittedUnitId = $request->input('primary_organizational_unit_id');
        if ($request->filled('primary_organizational_unit_id')) {
            abort_unless(is_scalar($submittedUnitId) && (string) $submittedUnitId === (string) $unitId, 403, 'Solo puede registrar o editar beneficiarios de su propia carrera o departamento.');
        }

        $request->merge(['primary_organizational_unit_id' => $unitId]);
    }

    public function destroy(Request $request, Beneficiary $beneficiary)
    {
        if ($beneficiary->certificates()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un beneficiario que tiene certificados emitidos asociados. Puede editar sus datos si es necesario.');
        }

        $beneficiary->delete();
        AuditLog::log('beneficiary_deleted', "Beneficiario {$beneficiary->full_name} eliminado", $beneficiary);

        return redirect()->route('beneficiaries.index')->with('success', 'Beneficiario eliminado correctamente.');
    }

    /**
     * Muestra la interfaz de importación masiva de beneficiarios.
     */
    public function importForm()
    {
        $preview = session('beneficiary_bulk_import_data');

        return view('beneficiaries.import', compact('preview'));
    }

    /**
     * Descarga la plantilla oficial en formato CSV con cabeceras y filas de ejemplo.
     */
    public function downloadTemplate(Request $request)
    {
        $csv = $this->importService->generateTemplateCsv();

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="plantilla_beneficiarios_ft_umsa.csv"',
        ]);
    }

    /**
     * Procesa y valida el archivo subido, generando un diagnóstico previo sin guardar.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240'],
        ], [
            'file.required' => 'Debe seleccionar un archivo Excel (.xlsx) o CSV.',
            'file.max' => 'El tamaño máximo del archivo es de 10 MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['csv', 'txt', 'xlsx'])) {
            return back()->with('error', 'Formato de archivo no admitido. Por favor suba un archivo .xlsx o .csv.');
        }

        try {
            $preview = $this->importService->parseAndPreview($file, $request->user());
            session(['beneficiary_bulk_import_data' => $preview]);

            if ($request->wantsJson()) {
                return response()->json($preview);
            }

            return redirect()->route('beneficiaries.import')->with('success', 'Archivo analizado exitosamente. Verifique el diagnóstico antes de confirmar.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al procesar el archivo: '.$e->getMessage());
        }
    }

    /**
     * Confirma e inserta definitivamente los beneficiarios validados en la base de datos.
     */
    public function storeBulk(Request $request)
    {
        $preview = session('beneficiary_bulk_import_data');

        if (! $preview || empty($preview['rows'])) {
            return redirect()->route('beneficiaries.import')->with('error', 'No hay datos cargados para importar. Por favor suba un archivo.');
        }

        if (empty($preview['summary']['ready'])) {
            return redirect()->route('beneficiaries.import')->with('warning', 'No se encontraron beneficiarios nuevos válidos para registrar en este archivo.');
        }

        try {
            $summary = $this->importService->executeImport($preview['rows'], $request->user());
            session()->forget('beneficiary_bulk_import_data');

            $message = "Importación masiva completada: se registraron {$summary['created_count']} nuevos beneficiarios. Se omitieron {$summary['skipped_count']} registros existentes o no válidos.";
            if (($summary['pending_career_count'] ?? 0) > 0) {
                $message .= " {$summary['pending_career_count']} registro(s) requieren revisar su carrera.";
            }

            return redirect()->route('beneficiaries.index')->with($summary['created_count'] > 0 ? 'success' : 'warning', $message);
        } catch (\Throwable $e) {
            return back()->with('error', 'Ocurrió un error al guardar los beneficiarios: '.$e->getMessage());
        }
    }

    /**
     * Cancela o descarta la vista previa actual.
     */
    public function cancelPreview()
    {
        session()->forget('beneficiary_bulk_import_data');

        return redirect()->route('beneficiaries.import')->with('info', 'Vista previa descartada.');
    }
}
