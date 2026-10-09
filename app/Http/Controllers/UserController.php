<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->latest('id')->paginate(15);
        $roles = Role::all();
        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'is_active' => ['boolean'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['is_active'] = $request->boolean('is_active', true);

        $user = User::create($validated);

        AuditLog::log('user_created', "Usuario creado: {$user->name} ({$user->email})", $user);

        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role_id' => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'is_active' => ['boolean'],
        ]);

        $old = $user->toArray();

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $validated['is_active'] = $request->boolean('is_active');

        $user->update($validated);

        AuditLog::log('user_updated', "Usuario actualizado: {$user->name}", $user, $old, $user->toArray());

        return redirect()->route('users.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function toggleStatus(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'No puede desactivar su propia cuenta.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        AuditLog::log('user_status_toggled', "Estado de {$user->name} cambiado a " . ($user->is_active ? 'Activo' : 'Inactivo'), $user);

        return back()->with('success', "Estado del usuario actualizado a: " . ($user->is_active ? 'Activo' : 'Inactivo'));
    }

    public function show(User $user)
    {
        return redirect()->route('users.edit', $user);
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return redirect()->route('users.index')->with('error', 'No puede eliminar su propia cuenta de usuario.');
        }

        $adminRoleId = Role::where('name', Role::ADMIN)->value('id');
        if ($user->role_id === $adminRoleId) {
            $totalAdmins = User::where('role_id', $adminRoleId)->where('is_active', true)->count();
            if ($totalAdmins <= 1) {
                return redirect()->route('users.index')->with('error', 'No se puede eliminar al único Administrador activo del sistema.');
            }
        }

        $hasCertificates = \App\Models\Certificate::where('generated_by_user_id', $user->id)
            ->orWhere('revoked_by_user_id', $user->id)
            ->exists();
        $hasAuditLogs = \App\Models\AuditLog::where('user_id', $user->id)->exists();
        $hasAssets = \App\Models\InstitutionalAsset::where('created_by_user_id', $user->id)->exists();

        if ($hasCertificates || $hasAuditLogs || $hasAssets) {
            $user->update(['is_active' => false]);
            AuditLog::log('user_deactivated_historic', "Usuario '{$user->name}' desactivado para preservar trazabilidad documental histórica", $user);
            return redirect()->route('users.index')->with('warning', "El usuario '{$user->name}' tiene registros vinculados (certificados o auditoría). Para proteger la integridad documental histórica, su cuenta fue desactivada en lugar de eliminarse.");
        }

        $name = $user->name;
        $user->delete();
        AuditLog::log('user_deleted', "Usuario '{$name}' eliminado del sistema", null);

        return redirect()->route('users.index')->with('success', "Usuario '{$name}' eliminado correctamente.");
    }
}
