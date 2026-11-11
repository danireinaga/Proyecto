<?php

namespace App\BusinessRules;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UserBusinessRules
{
    public function assignableRoles()
    {
        return Role::query()
            ->where('name', '!=', User::ROL_ADMIN)
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEmployee(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $role = $this->activeStaffRole($data['role']);
            $employee = User::create([
                ...$this->profileAttributes($data),
                'password' => Hash::make($data['password']),
            ]);
            $employee->assignRole($role);

            return $employee;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEmployee(User $employee, array $data): void
    {
        $this->ensureCanManageEmployee($employee);

        DB::transaction(function () use ($employee, $data): void {
            $role = $this->activeStaffRole($data['role']);
            $employee->update($this->profileAttributes($data));
            $employee->syncRoles([$role]);
        });
    }

    public function deactivateEmployee(User $employee): void
    {
        $this->ensureCanManageEmployee($employee);
        $employee->delete();
    }

    public function authorizePasswordReset(User $user): void
    {
        abort_if($user->esAdministrador(), 403);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function resetPassword(User $user, array $data): void
    {
        DB::transaction(function () use ($user, $data): void {
            $user->password = Hash::make($data['password']);
            $user->save();
        });
    }

    public function ensureCanManageEmployee(User $employee): void
    {
        abort_if($employee->is(auth()->user()), 403, 'No puedes modificar tu propia cuenta desde Gestión de personal.');
        abort_if($employee->esAdministrador(), 403);
    }

    private function activeStaffRole(string $roleName): Role
    {
        $role = Role::query()
            ->lockForUpdate()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->where('name', '!=', User::ROL_ADMIN)
            ->first();

        if (! $role) {
            throw ValidationException::withMessages([
                'role' => 'Selecciona un rol de personal activo.',
            ]);
        }

        return $role;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, string|null>
     */
    private function profileAttributes(array $data): array
    {
        return [
            'name' => implode(' ', array_filter([
                $data['nombres'],
                $data['apellido_paterno'],
                $data['apellido_materno'] ?? null,
            ])),
            'nombres' => $data['nombres'],
            'apellido_paterno' => $data['apellido_paterno'],
            'apellido_materno' => $data['apellido_materno'] ?? null,
            'email' => $data['email'],
            'celular' => $data['celular'],
            'carnet_identidad' => $data['carnet_identidad'],
        ];
    }
}
