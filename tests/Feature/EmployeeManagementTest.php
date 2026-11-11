<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Database\Seeders\EmployeeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    private function administrator(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create([
            'name' => 'Administrador de prueba',
            'email' => 'admin@example.test',
            'password' => 'admin-test-password',
        ]);
        $admin->assignRole(User::ROL_ADMIN);

        return $admin;
    }

    private function employeeData(array $overrides = []): array
    {
        return array_replace([
            'nombres' => 'Ana',
            'apellido_paterno' => 'Empleada',
            'apellido_materno' => 'Prueba',
            'email' => 'ana@example.test',
            'celular' => '70012345',
            'carnet_identidad' => '1234567',
            'password' => 'employee-temporary-password',
            'role' => 'barista',
        ], $overrides);
    }

    public function test_administrator_can_create_an_employee_with_an_active_role(): void
    {
        $admin = $this->administrator();
        $role = Role::create(['name' => 'barista']);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                ...$this->employeeData([
                    'nombres' => '  Ana  ',
                    'apellido_paterno' => '  Empleada  ',
                    'apellido_materno' => '  Prueba  ',
                    'email' => ' ANA@EXAMPLE.TEST ',
                    'carnet_identidad' => ' 123 4567 ',
                    'role' => $role->name,
                ]),
            ])
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHasNoErrors();

        $employee = User::where('email', 'ana@example.test')->firstOrFail();

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'Ana Empleada Prueba',
            'nombres' => 'Ana',
            'apellido_paterno' => 'Empleada',
            'apellido_materno' => 'Prueba',
            'email' => 'ana@example.test',
            'celular' => '70012345',
            'carnet_identidad' => '1234567',
        ]);
        $this->assertTrue(Hash::check('employee-temporary-password', $employee->password));
        $this->assertSame([$role->name], $employee->getRoleNames()->all());
    }

    public function test_employee_can_change_password_and_sign_in_with_the_new_password(): void
    {
        $this->administrator();
        $role = Role::create(['name' => 'barista']);
        $employee = User::factory()->create([
            'email' => 'empleada@example.test',
            'password' => Hash::make('employee-current-password'),
        ]);
        $employee->assignRole($role);

        $this->actingAs($employee)
            ->put(route('admin.configuracion.password'), [
                'current_password' => 'employee-current-password',
                'password' => 'employee-new-password',
                'password_confirmation' => 'employee-new-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertGuest();
        $this->assertTrue(Hash::check('employee-new-password', $employee->fresh()->password));

        $this->post(route('login'), [
            'email' => $employee->email,
            'password' => 'employee-new-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($employee);
    }

    public function test_employee_creation_rejects_duplicate_emails_and_system_roles(): void
    {
        $admin = $this->administrator();
        $role = Role::create(['name' => 'cajero']);
        $employee = User::factory()->create([
            'email' => 'existing-employee@example.test',
        ]);
        $employee->assignRole($role);

        $this->actingAs($admin)
            ->from(route('admin.usuarios.create'))
            ->post(route('admin.usuarios.store'), $this->employeeData([
                'email' => ' EXISTING-EMPLOYEE@example.test ',
                'carnet_identidad' => '9876543',
                'role' => $role->name,
            ]))
            ->assertRedirect(route('admin.usuarios.create'))
            ->assertSessionHasErrors('email');

        $this->post(route('admin.usuarios.store'), $this->employeeData([
            'email' => 'another-admin@example.test',
            'carnet_identidad' => '7654321',
            'role' => User::ROL_ADMIN,
        ]))->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'another-admin@example.test']);
        $this->assertSame(1, User::where('email', 'existing-employee@example.test')->count());
    }

    public function test_employee_creation_requires_a_unique_national_id(): void
    {
        $admin = $this->administrator();
        $role = Role::create(['name' => 'barista']);
        User::factory()->create(['carnet_identidad' => 'ABC-123']);

        $this->actingAs($admin)
            ->from(route('admin.usuarios.create'))
            ->post(route('admin.usuarios.store'), $this->employeeData([
                'carnet_identidad' => ' abc-123 ',
                'role' => $role->name,
            ]))
            ->assertRedirect(route('admin.usuarios.create'))
            ->assertSessionHasErrors('carnet_identidad');
    }

    public function test_employee_update_rejects_an_email_already_used_by_another_user(): void
    {
        $admin = $this->administrator();
        $role = Role::create(['name' => 'barista']);
        $existingEmployee = User::factory()->create([
            'email' => 'existing@example.test',
            'carnet_identidad' => '1111111',
        ]);
        $employeeToUpdate = User::factory()->create([
            'email' => 'employee@example.test',
            'carnet_identidad' => '2222222',
        ]);
        $existingEmployee->assignRole($role);
        $employeeToUpdate->assignRole($role);

        $this->actingAs($admin)
            ->from(route('admin.usuarios.edit', $employeeToUpdate))
            ->put(route('admin.usuarios.update', $employeeToUpdate), $this->employeeData([
                'email' => ' EXISTING@example.test ',
                'carnet_identidad' => '2222222',
                'role' => $role->name,
            ]))
            ->assertRedirect(route('admin.usuarios.edit', $employeeToUpdate))
            ->assertSessionHasErrors('email');

        $this->assertSame('employee@example.test', $employeeToUpdate->fresh()->email);
        $this->assertSame(1, User::where('email', 'existing@example.test')->count());
    }

    public function test_administrator_can_delete_an_employee_while_preserving_sales_history(): void
    {
        $admin = $this->administrator();
        $employee = User::factory()->create([
            'email' => 'employee-to-delete@example.test',
        ]);
        $employee->assignRole(Role::create(['name' => 'barista']));
        $sale = Order::create([
            'user_id' => $employee->id,
            'total' => 25,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('Eliminar usuario');

        $this->actingAs($admin)
            ->delete(route('admin.usuarios.destroy', $employee))
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHas('success', 'Usuario eliminado correctamente. Su historial se conservó.');

        $this->assertSoftDeleted('users', ['id' => $employee->id]);
        $this->assertDatabaseHas('ventas', ['id' => $sale->id, 'user_id' => $employee->id]);
    }

    public function test_employee_cannot_be_created_until_an_active_staff_role_exists(): void
    {
        $admin = $this->administrator();

        $this->assertDatabaseMissing('roles', ['name' => 'cajero']);
        $this->assertDatabaseMissing('roles', ['name' => 'supervisor']);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.create'))
            ->assertOk()
            ->assertSee('No hay roles de personal activos')
            ->assertSee('disabled', false);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), $this->employeeData())
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.test']);
    }

    public function test_role_permission_seeder_does_not_create_default_employee_roles(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseMissing('roles', ['name' => 'cajero']);
        $this->assertDatabaseMissing('roles', ['name' => 'supervisor']);
        $this->assertDatabaseHas('roles', ['name' => User::ROL_ADMIN]);
        $this->assertDatabaseCount('users', 0);
    }

    public function test_inactive_roles_cannot_be_assigned_to_new_employees(): void
    {
        $admin = $this->administrator();
        $inactiveRole = Role::create([
            'name' => 'rol-inactivo',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.create'))
            ->assertOk()
            ->assertDontSee('value="rol-inactivo"', false);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), $this->employeeData([
                'role' => $inactiveRole->name,
            ]))
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.test']);
    }

    public function test_employee_creation_returns_validation_errors_for_malformed_field_types(): void
    {
        $admin = $this->administrator();
        Role::create(['name' => 'barista']);

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), $this->employeeData([
                'nombres' => ['Ana'],
                'email' => ['ana@example.test'],
                'carnet_identidad' => ['1234567'],
            ]))
            ->assertSessionHasErrors(['nombres', 'email', 'carnet_identidad']);

        $this->assertDatabaseMissing('users', ['email' => 'ana@example.test']);
    }

    public function test_employee_can_be_updated_with_personal_information_and_a_dynamic_role(): void
    {
        $admin = $this->administrator();
        $oldRole = Role::create(['name' => 'barista']);
        $newRole = Role::create(['name' => 'encargado']);
        $employee = User::factory()->create([
            'nombres' => 'Ana',
            'apellido_paterno' => 'Empleada',
            'apellido_materno' => 'Prueba',
            'email' => 'ana@example.test',
            'celular' => '70012345',
            'carnet_identidad' => '1234567',
            'name' => 'Ana Empleada Prueba',
        ]);
        $employee->assignRole($oldRole);

        $this->actingAs($admin)
            ->put(route('admin.usuarios.update', $employee), $this->employeeData([
                'nombres' => 'María',
                'apellido_paterno' => 'Pérez',
                'apellido_materno' => '',
                'email' => 'MARIA@example.test',
                'celular' => '+591 700 12345',
                'carnet_identidad' => 'ab-9876',
                'role' => $newRole->name,
            ]))
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHasNoErrors();

        $employee->refresh();
        $this->assertSame('María Pérez', $employee->name);
        $this->assertSame('maria@example.test', $employee->email);
        $this->assertSame('+591 700 12345', $employee->celular);
        $this->assertSame('AB-9876', $employee->carnet_identidad);
        $this->assertSame([$newRole->name], $employee->getRoleNames()->all());
    }

    public function test_employee_edit_form_does_not_offer_a_password_field_that_is_not_saved(): void
    {
        $admin = $this->administrator();
        $role = Role::create(['name' => 'barista']);
        $employee = User::factory()->create();
        $employee->assignRole($role);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.edit', $employee))
            ->assertOk()
            ->assertDontSee('name="password"', false)
            ->assertSee('restablecimiento desde la lista de personal')
            ->assertSee('name="celular"', false)
            ->assertSee('name="carnet_identidad"', false)
            ->assertSee('name="nombres"', false)
            ->assertSee('name="apellido_paterno"', false)
            ->assertSee('name="apellido_materno"', false);

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee($employee->celular)
            ->assertSee($employee->carnet_identidad);
    }

    public function test_employee_seeder_creates_a_visible_employee_and_is_idempotent(): void
    {
        $this->administrator();
        config([
            'auth.demo_employee.email' => 'empleado.demo@example.test',
            'auth.demo_employee.password' => 'temporary-demo-password',
            'auth.demo_employee.role' => 'barista',
        ]);

        Role::create(['name' => 'barista']);
        $this->seed(EmployeeSeeder::class);
        $this->seed(EmployeeSeeder::class);

        $employee = User::where('email', 'empleado.demo@example.test')->firstOrFail();
        $admin = User::where('email', 'admin@example.test')->firstOrFail();

        $this->assertSame(1, User::where('email', 'empleado.demo@example.test')->count());
        $this->assertSame('Empleado de prueba', $employee->name);
        $this->assertSame(['barista'], $employee->getRoleNames()->all());
        $this->assertTrue(Hash::check('temporary-demo-password', $employee->password));

        $this->actingAs($admin)
            ->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('empleado.demo@example.test');
    }
}
