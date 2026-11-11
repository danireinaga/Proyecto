<?php

namespace App\BusinessRules;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleBusinessRules
{
    public function create(array $data): Role
    {
        $this->ensureNameIsNotProtected($data['name']);

        return DB::transaction(function () use ($data): Role {
            $role = Role::create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);
            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });
    }

    public function update(Role $role, array $data): void
    {
        $this->ensureEditable($role);
        $this->ensureNameIsNotProtected($data['name']);

        DB::transaction(function () use ($role, $data): void {
            $role->update(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);
        });
    }

    /**
     * @return array{changed: bool, assigned_users: int}
     */
    public function toggleStatus(Role $role): array
    {
        $this->ensureEditable($role);

        return DB::transaction(function () use ($role): array {
            $lockedRole = Role::query()->lockForUpdate()->findOrFail($role->id);
            $assignedUsers = User::role($lockedRole->name)->count();

            if ($lockedRole->is_active && $assignedUsers > 0) {
                return ['changed' => false, 'assigned_users' => $assignedUsers];
            }

            $lockedRole->is_active = ! $lockedRole->is_active;
            $lockedRole->save();

            return ['changed' => true, 'assigned_users' => 0];
        });
    }

    public function ensureEditable(Role $role): void
    {
        abort_if(
            $role->name === User::ROL_ADMIN,
            403,
            'Los roles principales del sistema no se pueden modificar desde este módulo.'
        );
    }

    private function ensureNameIsNotProtected(string $name): void
    {
        if (mb_strtolower(trim($name)) === User::ROL_ADMIN) {
            throw ValidationException::withMessages([
                'name' => 'Ese nombre está reservado para un rol del sistema.',
            ]);
        }
    }
}
