<?php

namespace App\Http\Controllers\Admin;

use App\BusinessRules\RoleBusinessRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:'.User::ROL_ADMIN);
    }

    public function index(): View
    {
        $roles = Role::with('permissions')
            ->where('name', '!=', User::ROL_ADMIN)
            ->orderBy('name')
            ->get();

        $roles->each(function (Role $role): void {
            $role->setAttribute('assigned_users_count', User::role($role->name)->count());
        });

        return view('admin.roles.index', compact('roles'));
    }

    public function create(): View
    {
        $permissions = Permission::query()->where('guard_name', 'web')->get();

        return view('admin.roles.create', compact('permissions'));
    }

    public function store(StoreRoleRequest $request, RoleBusinessRules $businessRules): RedirectResponse
    {
        $businessRules->create($request->validated());

        return redirect()->route('admin.roles.index')->with('success', 'Rol creado exitosamente.');
    }

    public function edit(Role $role, RoleBusinessRules $businessRules): View
    {
        $businessRules->ensureEditable($role);
        $permissions = Permission::query()->where('guard_name', 'web')->get();

        return view('admin.roles.edit', compact('role', 'permissions'));
    }

    public function update(
        UpdateRoleRequest $request,
        Role $role,
        RoleBusinessRules $businessRules
    ): RedirectResponse {
        $businessRules->update($role, $request->validated());

        return redirect()->route('admin.roles.index')->with('success', 'Rol actualizado correctamente.');
    }

    public function toggleStatus(Role $role, RoleBusinessRules $businessRules): RedirectResponse
    {
        $result = $businessRules->toggleStatus($role);

        if (! $result['changed']) {
            return redirect()->route('admin.roles.index')
                ->with(
                    'error',
                    'No se puede deshabilitar “'.$role->name.'” porque tiene '.$result['assigned_users']
                    .' usuario(s) asignado(s). Reasigna primero a esas personas.'
                );
        }

        $status = $role->fresh()->is_active;

        return redirect()->route('admin.roles.index')->with(
            'success',
            $status
                ? 'Rol “'.$role->name.'” habilitado.'
                : 'Rol “'.$role->name.'” deshabilitado.'
        );
    }
}
