<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Pedido;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RemoveSuperadminMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_migration_removes_superadmin_and_preserves_history_without_user_attribution(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $admin->assignRole(User::ROL_ADMIN);

        $oldRole = Role::create(['name' => 'superadmin']);
        $oldAccount = User::factory()->create(['email' => 'old-admin@example.test']);
        $oldAccount->assignRole($oldRole);
        $staffRole = Role::create(['name' => 'cajero']);
        $oldAccount->assignRole($staffRole);
        $oldAccount->givePermissionTo(Permission::create(['name' => 'temporary.permission']));

        $sale = Order::create([
            'user_id' => $oldAccount->id,
            'cliente_nombre' => 'Venta histórica',
            'total' => 25,
            'total_final' => 25,
            'pago_tipo' => 'efectivo',
            'estado' => Order::ESTADO_COMPLETADO,
        ]);
        $pedido = Pedido::create([
            'cliente_nombre' => 'Pedido histórico',
            'fecha_pedido' => today(),
            'fecha_entrega' => today()->addDay(),
            'user_id' => $oldAccount->id,
            'created_by' => $oldAccount->id,
            'updated_by' => $oldAccount->id,
        ]);

        $salesMigration = require database_path('migrations/2026_11_11_120000_rename_sales_tables_and_drop_saved_recipes.php');
        $salesMigration->down();

        $migration = require database_path('migrations/2026_11_11_000000_remove_superadmin_accounts_and_role.php');
        $migration->up();

        $this->assertDatabaseMissing('users', ['id' => $oldAccount->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseMissing('roles', ['name' => 'superadmin']);
        $this->assertDatabaseMissing('model_has_roles', ['model_id' => $oldAccount->id, 'model_type' => User::class]);
        $this->assertDatabaseMissing('model_has_permissions', ['model_id' => $oldAccount->id, 'model_type' => User::class]);
        $this->assertTrue($admin->fresh()->can('admin.caja.ver'));
        $this->assertTrue($admin->fresh()->can('admin.pedidos.ver'));
        $this->assertDatabaseHas('orders', ['id' => $sale->id, 'user_id' => null]);
        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'user_id' => null,
            'created_by' => null,
            'updated_by' => null,
        ]);
    }

    public function test_migration_keeps_an_account_that_also_has_the_admin_role(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $account = User::factory()->create(['email' => 'admin@example.test']);
        $account->assignRole([User::ROL_ADMIN, Role::create(['name' => 'superadmin'])]);

        $salesMigration = require database_path('migrations/2026_11_11_120000_rename_sales_tables_and_drop_saved_recipes.php');
        $salesMigration->down();

        $migration = require database_path('migrations/2026_11_11_000000_remove_superadmin_accounts_and_role.php');
        $migration->up();

        $this->assertDatabaseHas('users', ['id' => $account->id]);
        $this->assertTrue($account->fresh()->hasRole(User::ROL_ADMIN));
        $this->assertTrue($account->fresh()->can('admin.caja.ver'));
        $this->assertDatabaseMissing('roles', ['name' => 'superadmin']);
    }
}
