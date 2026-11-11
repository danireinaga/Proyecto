<?php

namespace App\Http\Controllers\Admin;

use App\BusinessRules\UserBusinessRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResetUserPasswordRequest;
use App\Http\Requests\Admin\StoreEmployeeRequest;
use App\Http\Requests\Admin\UpdateEmployeeRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.User::ROL_ADMIN);
    }

    public function index(): View
    {
        $users = User::with('roles')
            ->whereKeyNot(auth()->id())
            ->orderBy('name')
            ->get();

        return view('admin.usuarios.index', compact('users'));
    }

    public function create(UserBusinessRules $businessRules): View
    {
        $roles = $businessRules->assignableRoles();

        return view('admin.usuarios.create', compact('roles'));
    }

    public function store(StoreEmployeeRequest $request, UserBusinessRules $businessRules): RedirectResponse
    {
        $businessRules->createEmployee($request->validated());

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario creado exitosamente.');
    }

    public function edit(User $usuario, UserBusinessRules $businessRules): View
    {
        $businessRules->ensureCanManageEmployee($usuario);
        $roles = $businessRules->assignableRoles();

        return view('admin.usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(
        UpdateEmployeeRequest $request,
        User $usuario,
        UserBusinessRules $businessRules
    ): RedirectResponse {
        $businessRules->updateEmployee($usuario, $request->validated());

        return redirect()->route('admin.usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $usuario, UserBusinessRules $businessRules): RedirectResponse
    {
        $businessRules->deactivateEmployee($usuario);

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'Usuario eliminado correctamente. Su historial se conservó.');
    }

    public function editPasswordReset(User $usuario, UserBusinessRules $businessRules): View
    {
        $businessRules->authorizePasswordReset($usuario);

        return view('admin.usuarios.restablecer-contrasena', compact('usuario'));
    }

    public function resetPassword(
        ResetUserPasswordRequest $request,
        User $usuario,
        UserBusinessRules $businessRules
    ): RedirectResponse {
        $businessRules->resetPassword($usuario, $request->validated());

        return redirect()->route('admin.usuarios.index')
            ->with('success', 'La contraseña de '.$usuario->name.' fue restablecida.');
    }
}
