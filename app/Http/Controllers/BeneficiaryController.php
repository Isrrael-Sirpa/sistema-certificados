<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Beneficiary;
use Illuminate\Http\Request;

class BeneficiaryController extends Controller
{
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
            'document_number' => ['required', 'string', 'max:50', 'unique:beneficiaries,document_number,NULL,id,document_type,' . $request->document_type],
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
            'document_number' => ['required', 'string', 'max:50', 'unique:beneficiaries,document_number,' . $beneficiary->id . ',id,document_type,' . $request->document_type],
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
}
