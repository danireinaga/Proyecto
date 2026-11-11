<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run()
    {
        // Limpiar caché de permisos de Spatie
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 1. Definir todos los permisos globales que existen en los módulos del sistema
        $permissions = [
            'admin.catalogo.ver',
            'admin.productos.ver',
            'admin.usuarios.ver',
            'admin.menu-digital.ver',
            'admin.caja.ver', // El permiso global unificado para la caja y sus 3 niveles
            'admin.caja.anular', // Anular una venta y devolver el stock
            'admin.pedidos.ver', // Tablero y detalle de pedidos con fecha de entrega
            'admin.pedidos.crear',
            'admin.pedidos.editar',
            'admin.pedidos.anular', // Cancelar o eliminar un pedido
            'admin.ventas.ver', // Lo crea aquí, pero sólo lo recibe el rol admin (ver paso 3)
            'admin.reportes.ver',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // El rol administrador es el único rol del sistema y no se asigna
        // desde los formularios normales de gestión de personal.
        $roleAdmin = Role::firstOrCreate(['name' => User::ROL_ADMIN]);

        $permisos = Permission::all();
        $roleAdmin->syncPermissions($permisos);

        // Los roles de empleados son creados por el administrador según las
        // necesidades del negocio; este seeder solo configura los roles del sistema.
    }
}
