<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Da de alta el permiso del módulo Ventas y se lo concede SÓLO al rol admin.
 *
 * Es la alternativa quirúrgica a RolePermissionSeeder: ese seeder también crea
 * los roles supervisor/cajero y el usuario admin@azucaryyo.com, que aquí no se
 * necesitan. Este seeder es idempotente (se puede repetir sin efecto) y no
 * crea, modifica ni elimina ningún rol, usuario o permiso existente: sólo
 * añade un permiso nuevo y lo otorga de forma aditiva con givePermissionTo,
 * que nunca revoca lo que ya estaba asignado.
 *
 * Para que un empleado vea Ventas, el administrador marca la casilla
 * admin.ventas.ver en admin/roles; no hace falta volver a correr este seeder.
 */
class VentasPermissionSeeder extends Seeder
{
    public const PERMISO = 'admin.ventas.ver';

    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permiso = Permission::firstOrCreate(['name' => self::PERMISO]);

        $admin = Role::where('name', User::ROL_ADMIN)->first();

        if (! $admin) {
            $this->command?->warn('No existe el rol "'.User::ROL_ADMIN.'": se creó el permiso '
                .self::PERMISO.' pero no se concedio a ningun rol.');

            return;
        }

        // givePermissionTo es aditivo: si el rol ya lo tenía, no hace nada.
        $admin->givePermissionTo($permiso);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->command?->info('Permiso '.self::PERMISO.' asignado al rol "'.$admin->name.'".');
    }
}