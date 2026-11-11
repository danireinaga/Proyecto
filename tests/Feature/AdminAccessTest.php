<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
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

    public function test_admin_has_all_panel_permissions_and_can_access_caja_and_pedidos(): void
    {
        $admin = $this->admin();

        $this->assertTrue($admin->can('admin.productos.ver'));
        $this->assertTrue($admin->can('admin.ventas.ver'));
        $this->assertTrue($admin->can('admin.caja.ver'));
        $this->assertTrue($admin->can('admin.caja.anular'));
        $this->assertTrue($admin->can('admin.pedidos.ver'));

        $this->actingAs($admin)->get('/admin/caja')->assertOk();
        $this->actingAs($admin)->get(route('admin.pedidos.index'))->assertOk();
        $this->actingAs($admin)->get('/admin/configuracion')->assertOk();
    }

    public function test_admin_sidebar_uses_branding_grouped_sections_and_a_logout_action(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Azucar y Yo')
            ->assertSee('img/logo_reposteria_transparente.png')
            ->assertSee('Cerrar sesión')
            ->assertSee('sidebar-logout')
            ->assertSee('href="'.route('logout').'"', false)
            ->assertSee('GESTIÓN DE ACCESOS')
            ->assertSee('VENTAS Y REPORTES')
            ->assertSee('AJUSTES DE CUENTA')
            ->assertSee('js/admin-panel.js')
            ->assertDontSee('navbar-search')
            ->assertDontSee('sidebar-menu-search')
            ->assertDontSee('AdminLTELogo.png')
            ->assertDontSee('account_settings');
    }

    public function test_public_registration_and_password_recovery_are_disabled(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();
        $this->get('/forgot-password')->assertNotFound();
        $this->post('/forgot-password')->assertNotFound();
    }

    public function test_admin_can_change_password_and_must_sign_in_again(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->put('/admin/configuracion/contrasena', [
                'current_password' => 'admin-test-password',
                'password' => 'updated-admin-password',
                'password_confirmation' => 'updated-admin-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors();

        $this->assertGuest();
        $this->assertTrue(Hash::check('updated-admin-password', $admin->fresh()->password));
    }

    public function test_user_table_hides_the_signed_in_admin_and_shows_employees(): void
    {
        $admin = $this->admin();
        $staffRole = Role::create(['name' => 'cajero']);
        $employee = User::factory()->create([
            'name' => 'Empleado de prueba',
            'email' => 'empleado@example.test',
        ]);
        $employee->assignRole($staffRole);

        $this->actingAs($admin)->get(route('admin.usuarios.index'))
            ->assertOk()
            ->assertSee('empleado@example.test')
            ->assertDontSee($admin->email);
    }

    public function test_roles_with_assigned_users_cannot_be_disabled(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'cajero']);
        $employee = User::factory()->create();
        $employee->assignRole($role);

        $this->actingAs($admin)
            ->patch(route('admin.roles.status', $role))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHas('error', 'No se puede deshabilitar “cajero” porque tiene 1 usuario(s) asignado(s). Reasigna primero a esas personas.');

        $this->assertTrue((bool) $role->fresh()->is_active);
    }

    public function test_disabling_a_role_removes_it_from_new_user_role_choices(): void
    {
        $admin = $this->admin();
        $role = Role::create(['name' => 'ayudante']);

        $this->actingAs($admin)
            ->patch(route('admin.roles.status', $role))
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHasNoErrors();

        $this->assertFalse((bool) $role->fresh()->is_active);
        $this->actingAs($admin)->get(route('admin.usuarios.create'))
            ->assertOk()
            ->assertDontSee('value="ayudante"', false);
    }

    public function test_role_names_must_be_unique_and_cannot_use_system_role_name(): void
    {
        $admin = $this->admin();
        Role::create(['name' => 'barista']);
        $toUpdate = Role::create(['name' => 'ayudante']);

        $this->actingAs($admin)
            ->from(route('admin.roles.create'))
            ->post(route('admin.roles.store'), ['name' => ' barista '])
            ->assertRedirect(route('admin.roles.create'))
            ->assertSessionHasErrors('name');

        $this->post(route('admin.roles.store'), ['name' => ' ADMIN '])
            ->assertSessionHasErrors('name');

        $this->from(route('admin.roles.edit', $toUpdate))
            ->put(route('admin.roles.update', $toUpdate), ['name' => 'barista'])
            ->assertRedirect(route('admin.roles.edit', $toUpdate))
            ->assertSessionHasErrors('name');

        $this->assertSame('ayudante', $toUpdate->fresh()->name);
    }

    public function test_admin_can_reset_an_employee_password_but_not_an_admin_password(): void
    {
        $admin = $this->admin();
        $staffRole = Role::create(['name' => 'cajero']);
        $employee = User::factory()->create(['email' => 'staff@example.test']);
        $employee->assignRole($staffRole);

        $this->actingAs($admin)
            ->patch(route('admin.usuarios.reset-password', $employee), [
                'current_password' => 'admin-test-password',
                'password' => 'employee-new-password',
                'password_confirmation' => 'employee-new-password',
            ])
            ->assertRedirect(route('admin.usuarios.index'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('employee-new-password', $employee->fresh()->password));

        $this->actingAs($admin)
            ->patch(route('admin.usuarios.reset-password', $admin), [
                'current_password' => 'admin-test-password',
                'password' => 'other-password',
                'password_confirmation' => 'other-password',
            ])
            ->assertForbidden();
    }

    public function test_user_management_cannot_create_another_system_administrator(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.usuarios.store'), [
                'name' => 'Admin adicional',
                'email' => 'extra-admin@example.test',
                'password' => 'employee-temporary-password',
                'role' => User::ROL_ADMIN,
            ])
            ->assertSessionHasErrors('role');

        $this->assertDatabaseMissing('users', ['email' => 'extra-admin@example.test']);
    }
}
