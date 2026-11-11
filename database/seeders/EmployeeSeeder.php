<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('EmployeeSeeder solo debe ejecutarse en entornos local o testing.');
        }

        $roleName = config('auth.demo_employee.role');
        $role = Role::query()
            ->where('name', $roleName)
            ->where('guard_name', 'web')
            ->where('is_active', true)
            ->first();

        if (blank($roleName) || ! $role) {
            throw new RuntimeException(
                'Configura DEMO_EMPLOYEE_ROLE con el nombre de un rol de empleado activo antes de ejecutar este seeder.'
            );
        }

        $email = config('auth.demo_employee.email');

        if (blank($email)) {
            throw new RuntimeException('Configura DEMO_EMPLOYEE_EMAIL antes de sembrar el empleado de prueba.');
        }

        $employee = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Empleado de prueba',
                'nombres' => 'Empleado',
                'apellido_paterno' => 'Prueba',
                'apellido_materno' => null,
                'celular' => '70000000',
                'carnet_identidad' => 'DEMO0001',
                'password' => Hash::make(
                    config('auth.demo_employee.password') ?: Str::random(48)
                ),
            ]
        );

        if ($employee->esAdministrador()) {
            throw new RuntimeException('El correo configurado para el empleado de prueba pertenece a una cuenta protegida.');
        }

        if ($employee->isDirty()) {
            $employee->save();
        }

        $employee->syncRoles([$role]);
    }
}
