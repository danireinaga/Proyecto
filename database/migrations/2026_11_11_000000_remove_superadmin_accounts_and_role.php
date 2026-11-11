<?php

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['user_id']);
            $table->foreignId('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $rolePivot = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelPivot = $columnNames['model_morph_key'] ?? 'model_id';
        $userMorph = (new User)->getMorphClass();

        $role = DB::table($tableNames['roles'])
            ->where('name', 'superadmin')
            ->where('guard_name', 'web')
            ->first();

        if (! $role) {
            app(RolePermissionSeeder::class)->run();
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return;
        }

        $superadminIds = DB::table($tableNames['model_has_roles'])
            ->where($rolePivot, $role->id)
            ->where('model_type', $userMorph)
            ->pluck($modelPivot);

        $adminRoleId = DB::table($tableNames['roles'])
            ->where('name', User::ROL_ADMIN)
            ->where('guard_name', 'web')
            ->value('id');

        $adminIds = $adminRoleId
            ? DB::table($tableNames['model_has_roles'])
                ->where($rolePivot, $adminRoleId)
                ->where('model_type', $userMorph)
                ->whereIn($modelPivot, $superadminIds)
                ->pluck($modelPivot)
            : collect();

        $usersToDelete = $superadminIds->diff($adminIds)->values();

        if ($usersToDelete->isNotEmpty()) {
            DB::transaction(function () use ($tableNames, $role, $usersToDelete, $modelPivot, $userMorph): void {
                DB::table('orders')->whereIn('user_id', $usersToDelete)->update(['user_id' => null]);
                DB::table('pedidos')->whereIn('user_id', $usersToDelete)->update(['user_id' => null]);
                DB::table('pedidos')->whereIn('created_by', $usersToDelete)->update(['created_by' => null]);
                DB::table('pedidos')->whereIn('updated_by', $usersToDelete)->update(['updated_by' => null]);

                DB::table($tableNames['model_has_roles'])
                    ->where('model_type', $userMorph)
                    ->whereIn($modelPivot, $usersToDelete)
                    ->delete();
                DB::table($tableNames['model_has_permissions'])
                    ->where('model_type', $userMorph)
                    ->whereIn($modelPivot, $usersToDelete)
                    ->delete();

                if (Schema::hasTable('password_reset_tokens')) {
                    DB::table('password_reset_tokens')
                        ->whereIn('email', DB::table('users')->select('email')->whereIn('id', $usersToDelete))
                        ->delete();
                }

                if (Schema::hasTable('sessions') && Schema::hasColumn('sessions', 'user_id')) {
                    DB::table('sessions')->whereIn('user_id', $usersToDelete)->delete();
                }

                DB::table('users')->whereIn('id', $usersToDelete)->delete();
                DB::table($tableNames['roles'])->where('id', $role->id)->delete();
            });
        } else {
            DB::table($tableNames['roles'])->where('id', $role->id)->delete();
        }

        app(RolePermissionSeeder::class)->run();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        throw new RuntimeException('La eliminación de cuentas y credenciales de superadministrador no se puede revertir automáticamente.');
    }
};
