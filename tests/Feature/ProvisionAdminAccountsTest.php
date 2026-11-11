<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProvisionAdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    private function setValidCredentials(): void
    {
        config([
            'auth.administrator.email' => 'administrator@example.test',
            'auth.administrator.password' => 'RememberAdmin2026!',
        ]);
    }

    public function test_command_provisions_the_administrator_once_without_overwriting_credentials(): void
    {
        $this->setValidCredentials();

        $this->artisan('app:provision-admin-accounts')
            ->expectsOutputToContain('Cuenta admin creada.')
            ->assertSuccessful();

        $admin = User::role(User::ROL_ADMIN)->firstOrFail();

        $this->assertSame('administrator@example.test', $admin->email);
        $this->assertTrue(Hash::check('RememberAdmin2026!', $admin->password));
        $this->assertTrue($admin->can('admin.caja.ver'));
        $this->assertTrue($admin->can('admin.pedidos.ver'));
        $this->assertDatabaseMissing('roles', ['name' => 'superadmin']);

        config([
            'auth.administrator.email' => 'changed-admin@example.test',
            'auth.administrator.password' => 'ChangedAdmin2026!',
        ]);

        $this->artisan('app:provision-admin-accounts')
            ->expectsOutputToContain('La cuenta admin ya existe; no se modificó.')
            ->doesntExpectOutputToContain('RememberAdmin2026!')
            ->assertSuccessful();

        $this->assertTrue(Hash::check('RememberAdmin2026!', $admin->fresh()->password));
        $this->assertSame('administrator@example.test', $admin->fresh()->email);
        $this->assertSame(1, User::count());
    }

    public function test_command_requires_a_strong_password_and_valid_email(): void
    {
        $this->setValidCredentials();
        config([
            'auth.administrator.email' => 'not-an-email',
            'auth.administrator.password' => 'easy-password',
        ]);

        $this->artisan('app:provision-admin-accounts')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseMissing('roles', ['name' => 'superadmin']);
    }

    public function test_command_will_not_promote_an_existing_employee_by_email(): void
    {
        $this->setValidCredentials();
        $this->seed(RolePermissionSeeder::class);
        $employee = User::factory()->create([
            'email' => 'administrator@example.test',
        ]);

        $this->artisan('app:provision-admin-accounts')
            ->expectsOutputToContain('ya pertenece a otra cuenta')
            ->assertFailed();

        $this->assertDatabaseMissing('model_has_roles', [
            'model_id' => $employee->id,
            'model_type' => User::class,
        ]);
        $this->assertDatabaseMissing('users', ['email' => 'administrator@example.test', 'name' => 'Administrador']);
    }

    public function test_role_permission_seeder_creates_only_the_admin_system_role(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseHas('roles', ['name' => User::ROL_ADMIN]);
        $this->assertDatabaseMissing('roles', ['name' => 'superadmin']);
    }

    public function test_command_requires_explicit_confirmation_in_production(): void
    {
        $this->setValidCredentials();
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('app:provision-admin-accounts')
            ->expectsOutputToContain('debes ejecutar el comando con --force')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseMissing('roles', ['name' => 'superadmin']);
    }
}
