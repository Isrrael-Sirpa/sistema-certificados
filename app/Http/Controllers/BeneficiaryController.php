<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use App\Services\BeneficiaryImportService;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
    public function __construct(
        protected BeneficiaryImportService $importService
    ) {}

    public function index(Request $request)
    {
        $query = Beneficiary::withCount('certificates');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('document_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $beneficiaries = $query->orderBy('last_name')->orderBy('first_name')->paginate(15)->withQueryString();

        return view('beneficiaries.index', compact('beneficiaries'));
    }

    public function create()
    {
        return view('beneficiaries.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:30'],
            'document_number' => ['required', 'string', 'max:50', 'unique:beneficiaries,document_number,NULL,id,document_type,'.$request->document_type],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'academic_title' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'institution_or_department' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ]);

        $beneficiary = Beneficiary::create($validated);

        AuditLog::log('beneficiary_created', "Beneficiario {$beneficiary->full_name} registrado", $beneficiary);

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Beneficiario registrado exitosamente.');
    }

    public function show(Beneficiary $beneficiary)
    {
        $beneficiary->load(['certificates' => function ($q) {
            $q->with(['activity', 'certificateType'])->latest('id');
        }]);

        return view('beneficiaries.show', compact('beneficiary'));
    }

    public function edit(Beneficiary $beneficiary)
    {
        return view('beneficiaries.edit', compact('beneficiary'));
    }

    public function update(Request $request, Beneficiary $beneficiary)
    {
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'max:30'],
            'document_number' => ['required', 'string', 'max:50', 'unique:beneficiaries,document_number,'.$beneficiary->id.',id,document_type,'.$request->document_type],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'academic_title' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'institution_or_department' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string'],
        ]);

        $old = $beneficiary->toArray();
        $beneficiary->update($validated);

        AuditLog::log('beneficiary_updated', "Beneficiario {$beneficiary->full_name} actualizado", $beneficiary, $old, $beneficiary->toArray());

        return redirect()->route('beneficiaries.show', $beneficiary)->with('success', 'Datos del beneficiario actualizados.');
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
            $preview = $this->importService->parseAndPreview($file);
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

            return redirect()->route('beneficiaries.index')->with(
                'success',
                "Importación masiva completada: se registraron {$summary['created_count']} nuevos beneficiarios. Se omitieron {$summary['skipped_count']} registros existentes o no válidos."
            );
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
