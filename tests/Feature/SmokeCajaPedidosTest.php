<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pedido;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SmokeCajaPedidosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);

        $user = User::factory()->create([
            'name' => 'Administrador de prueba',
            'email' => 'admin@example.test',
        ]);
        $user->assignRole(User::ROL_ADMIN);

        return $user;
    }

    private function account(string $role): User
    {
        $user = User::factory()->create([
            'email' => $role.'@example.test',
        ]);
        $user->assignRole($role);

        return $user;
    }

    private function cashierRole(): Role
    {
        $role = Role::firstOrCreate(['name' => 'cajero']);
        $role->givePermissionTo([
            'admin.caja.ver',
            'admin.pedidos.ver',
            'admin.pedidos.crear',
            'admin.pedidos.editar',
        ]);

        return $role;
    }

    public function test_caja_muestra_el_formulario_de_cobro(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get('/admin/caja')
            ->assertOk()
            ->assertSee('Cobrar e Imprimir Ficha', false)
            ->assertSee('id="cliente_nombre"', false)
            ->assertSee('id="cobro-pago"', false)
            ->assertSee('id="cobro-recibido"', false);
    }

    public function test_listados_administrativos_de_productos_ordenan_por_id_ascendente(): void
    {
        $admin = $this->admin();

        foreach (['Producto 1', 'Producto 2', 'Producto 3'] as $nombre) {
            Product::create([
                'nombre' => $nombre,
                'precio' => 10,
                'stock' => 5,
                'estado' => true,
            ]);
        }

        $this->actingAs($admin)->get(route('admin.productos.index'))
            ->assertOk()
            ->assertSeeInOrder(['Producto 1', 'Producto 2', 'Producto 3']);

        $this->actingAs($admin)->get(route('admin.catalogo.index'))
            ->assertOk()
            ->assertSeeInOrder(['Producto 1', 'Producto 2', 'Producto 3']);
    }

    public function test_listados_administrativos_de_ventas_ordenan_por_id_ascendente(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->cashierRole()->givePermissionTo('admin.ventas.ver');
        $admin = $this->account('cajero');
        $ventas = [];

        foreach (['Cliente 1', 'Cliente 2', 'Cliente 3'] as $cliente) {
            $ventas[] = Order::create([
                'user_id' => $admin->id,
                'cliente_nombre' => $cliente,
                'total' => 10,
                'total_final' => 10,
                'pago_tipo' => 'efectivo',
                'estado' => Order::ESTADO_COMPLETADO,
                'origen' => 'caja',
            ]);
        }

        $this->actingAs($admin)->get(route('admin.ventas.index'))
            ->assertOk()
            ->assertSeeInOrder(['Cliente 1', 'Cliente 2', 'Cliente 3']);

        $this->actingAs($admin)->get(route('admin.caja.mis-ventas'))
            ->assertOk()
            ->assertSeeInOrder(array_map(fn (Order $venta) => '#'.$venta->id, $ventas));
    }

    public function test_la_venta_se_registra_y_la_ficha_se_puede_imprimir(): void
    {
        $admin = $this->admin();

        $producto = Product::create([
            'nombre' => 'Tres Leches',
            'precio' => 25,
            'stock' => 10,
            'estado' => true,
            'en_menu_digital' => true,
        ]);

        $respuesta = $this->actingAs($admin)->postJson('/admin/caja/store', [
            'cliente_nombre' => 'Juan',
            'cliente_apellido' => 'Pérez',
            'cliente_telefono' => '70012345',
            'items' => [[
                'id' => $producto->id,
                'nombre' => 'Tres Leches',
                'precio' => 25,
                'cantidad' => 2,
                'note' => 'Sin crema',
            ]],
            'pago_tipo' => 'efectivo',
            'pago_recibido' => 100,
            'descuento' => 0,
            'propina' => 0,
        ]);

        $respuesta->assertOk()->assertJson(['success' => true]);
        $this->assertSame(50.0, (float) $respuesta->json('total_final'));
        $this->assertSame(50.0, (float) $respuesta->json('pago_vuelto'));
        $this->assertStringStartsWith('F-', (string) $respuesta->json('numero_ficha'));
        $this->assertSame(8, $producto->fresh()->stock);

        // La ficha que devuelve el servidor se abre y queda visible para imprimir.
        $ficha = $respuesta->json('ficha_url');
        $this->actingAs($admin)->get($ficha)
            ->assertOk()
            ->assertSee($respuesta->json('numero_ficha'), false)
            ->assertSee('VUELTO', false)
            ->assertSee('EFECTIVO', false);
    }

    public function test_caja_separa_mis_ventas_y_numeracion_de_tickets_por_usuario(): void
    {
        $admin = $this->admin();
        $cajero = User::factory()->create([
            'name' => 'Cajero de prueba',
            'email' => 'cajero@example.test',
        ]);
        $cajero->assignRole($this->cashierRole());

        $registrarVenta = function (User $usuario, string $cliente) {
            return $this->actingAs($usuario)->postJson(route('admin.caja.store'), [
                'cliente_nombre' => $cliente,
                'items' => [[
                    'nombre' => $cliente.' producto',
                    'precio' => 10,
                    'cantidad' => 1,
                ]],
                'pago_tipo' => 'efectivo',
                'pago_recibido' => 10,
            ])->assertOk()->json('numero_ficha');
        };

        $ticketCajero = $registrarVenta($cajero, 'Venta del cajero');
        $ticketAdmin = $registrarVenta($admin, 'Venta del administrador');
        $siguienteTicketCajero = $registrarVenta($cajero, 'Segunda venta del cajero');

        $prefijoDelDia = 'F-'.now()->format('Ymd').'-';
        $this->assertSame($prefijoDelDia.'0001', $ticketCajero);
        $this->assertSame($prefijoDelDia.'0001', $ticketAdmin);
        $this->assertSame($prefijoDelDia.'0002', $siguienteTicketCajero);

        $this->actingAs($admin)->get(route('admin.caja.mis-ventas'))
            ->assertOk()
            ->assertSee('Venta del administrador producto')
            ->assertDontSee('Venta del cajero producto')
            ->assertDontSee('Segunda venta del cajero producto');

        $this->actingAs($cajero)->get(route('admin.caja.mis-ventas'))
            ->assertOk()
            ->assertSee('Venta del cajero producto')
            ->assertSee('Segunda venta del cajero producto')
            ->assertDontSee('Venta del supervisor producto');
    }

    public function test_pedidos_muestra_una_pestana_por_usuario_y_cada_una_solo_su_pedido(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->account(User::ROL_ADMIN);

        $cajero = User::create([
            'name' => 'Cajero de prueba',
            'email' => 'cajero@prueba.test',
            'password' => 'password123',
        ]);
        $cajero->assignRole($this->cashierRole());

        $pedidoAdmin = Pedido::create([
            'cliente_nombre' => 'Cliente Admin',
            'fecha_pedido' => now()->toDateString(),
            'fecha_entrega' => now()->toDateString(),
            'hora_entrega' => '10:00',
            'total_estimado' => 99,
            'user_id' => $admin->id,
        ]);

        $pedidoCajero = Pedido::create([
            'cliente_nombre' => 'Cliente Programado',
            'fecha_pedido' => now()->toDateString(),
            'fecha_entrega' => now()->toDateString(),
            'hora_entrega' => '10:00',
            'total_estimado' => 99,
            'user_id' => $cajero->id,
        ]);

        // La pestaña por defecto es "Todos los pedidos": se ve todo
        $this->actingAs($admin)->get('/admin/pedidos')
            ->assertOk()
            ->assertSee('Todos los pedidos')
            ->assertSee('Administrador')
            ->assertSee('Cajero de prueba')
            ->assertSee('Cliente Admin')
            ->assertSee('Cliente Programado');

        // La pestaña del empleado muestra sólo su pedido
        $this->actingAs($admin)->get('/admin/pedidos?pestana=user:'.$cajero->id)
            ->assertOk()
            ->assertSee('Pedidos de Cajero de prueba')
            ->assertSee('Cliente Programado')
            ->assertDontSee('Cliente Admin');

        // La pestaña del administrador también
        $this->actingAs($admin)->get('/admin/pedidos?pestana=admin')
            ->assertOk()
            ->assertSee('Pedidos del Administrador')
            ->assertDontSee('Cliente Programado');

        // El personal puede consultar pedidos de otros usuarios, pero sólo
        // puede editar o cancelar sus propios pedidos.
        $this->actingAs($cajero)->get('/admin/pedidos')
            ->assertOk()
            ->assertSee('Todos los pedidos')
            ->assertSee('Cliente Admin')
            ->assertSee('Cliente Programado')
            ->assertDontSee(route('admin.pedidos.edit', $pedidoAdmin), false)
            ->assertSee(route('admin.pedidos.edit', $pedidoCajero), false);

        // Las pestañas por responsable sólo filtran los resultados; no otorgan permisos de edición.
        $this->actingAs($cajero)->get('/admin/pedidos?pestana=admin')
            ->assertOk()
            ->assertSee('Cliente Admin')
            ->assertDontSee(route('admin.pedidos.edit', $pedidoAdmin), false);

        // Un usuario nuevo recibe su propia pestaña automáticamente
        $nuevo = User::create([
            'name' => 'Empleado Nuevo',
            'email' => 'nuevo@prueba.test',
            'password' => 'password123',
        ]);
        $nuevo->assignRole($this->cashierRole());

        $this->actingAs($admin)->get('/admin/pedidos')
            ->assertOk()
            ->assertSee('Empleado Nuevo')
            ->assertSee(route('admin.pedidos.index', ['pestana' => 'user:'.$nuevo->id]), false);

        // Y el apartado "Pedido del cliente" muestra el pedido elegido
        $this->actingAs($admin)->get('/admin/pedidos?pestana=admin&pedido='.$pedidoAdmin->id)
            ->assertOk()
            ->assertSee($pedidoAdmin->codigo);
    }

    public function test_ventas_muestra_una_pestana_por_usuario_y_cada_una_solo_su_venta(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->account(User::ROL_ADMIN);

        $cajero = User::create([
            'name' => 'Cajero de prueba',
            'email' => 'cajero@prueba.test',
            'password' => 'password123',
        ]);
        $cajero->assignRole($this->cashierRole());
        $ventaAdmin = Order::create([
            'user_id' => $admin->id,
            'cliente_nombre' => 'Cliente Admin',
            'total' => 30,
            'total_final' => 30,
            'pago_tipo' => 'efectivo',
            'estado' => Order::ESTADO_COMPLETADO,
            'origen' => 'caja',
            'numero_ficha' => 'F-20260929-0001',
        ]);

        $ventaEmpleado = Order::create([
            'user_id' => $cajero->id,
            'cliente_nombre' => 'Cliente Cajero',
            'cliente_nit' => '1234567 LP',
            'total' => 20,
            'total_final' => 20,
            'pago_tipo' => 'tarjeta',
            'estado' => Order::ESTADO_COMPLETADO,
            'origen' => 'caja',
            'numero_ficha' => 'F-20260929-0002',
        ]);

        // La pestaña por defecto es "Todas las ventas": se ve todo
        $this->actingAs($admin)->get('/admin/ventas')
            ->assertOk()
            ->assertSee('Todas las ventas')
            ->assertSee('Administrador')
            ->assertSee('Cajero de prueba')
            ->assertDontSee('Administrador Maestro')
            ->assertSee('F-20260929-0001')
            ->assertSee('F-20260929-0002')
            ->assertSee(route('admin.caja.ficha', $ventaAdmin), false)
            ->assertSee(route('admin.caja.ficha', $ventaEmpleado), false)
            ->assertSee('Producto más vendido')
            ->assertSee('Bs. 30.00')
            ->assertSee('Bs. 20.00');

        $this->actingAs($admin)->get(route('admin.caja.ficha', $ventaAdmin))
            ->assertOk()
            ->assertSee('Cliente Admin')
            ->assertSee($ventaAdmin->numero_ficha);

        // La pestaña del empleado muestra sólo lo suyo
        $this->actingAs($admin)->get('/admin/ventas?pestana=user:'.$cajero->id)
            ->assertOk()
            ->assertSee('Ventas de Cajero de prueba')
            ->assertSee(route('admin.caja.ficha', $ventaEmpleado), false)
            ->assertSee('Bs. 20.00')
            ->assertDontSee('Bs. 30.00');

        // La pestaña del administrador también
        $this->actingAs($admin)->get('/admin/ventas?pestana=admin')
            ->assertOk()
            ->assertSee('Ventas del Administrador')
            ->assertSee(route('admin.caja.ficha', $ventaAdmin), false)
            ->assertDontSee(route('admin.caja.ficha', $ventaEmpleado), false);

        $this->actingAs($admin)->get('/admin/ventas?q=1234567')
            ->assertOk()
            ->assertSee('Cliente Cajero')
            ->assertSee('CI/NIT: 1234567 LP')
            ->assertDontSee('Cliente Admin');

        $this->actingAs($admin)->get('/admin/ventas?periodo=semana_anterior')
            ->assertOk()
            ->assertDontSee('Cliente Cajero')
            ->assertDontSee('Cliente Admin');

        // El módulo Ventas es privado del administrador: un empleado sin el permiso
        // admin.ventas.ver ni ve el menú ni puede entrar, manipule o no la URL.
        $this->actingAs($cajero)->get('/admin/ventas')
            ->assertForbidden();

        $this->actingAs($cajero)->get('/admin/ventas?pestana=admin')
            ->assertForbidden();

        // Un usuario nuevo recibe su propia pestaña automáticamente
        $nuevo = User::create([
            'name' => 'Empleado Nuevo',
            'email' => 'nuevo@prueba.test',
            'password' => 'password123',
        ]);
        $nuevo->assignRole($this->cashierRole());

        $this->actingAs($admin)->get('/admin/ventas')
            ->assertOk()
            ->assertSee('Empleado Nuevo')
            ->assertSee(route('admin.ventas.index', ['pestana' => 'user:'.$nuevo->id]), false);
    }

    public function test_ventas_lo_habilita_el_administrador_desde_la_pantalla_de_roles(): void
    {
        $admin = $this->admin();

        $cajero = User::create([
            'name' => 'Cajero de prueba',
            'email' => 'cajero@prueba.test',
            'password' => 'password123',
        ]);
        $cajero->assignRole($this->cashierRole());

        // El seeder de producción sólo lo da al rol admin
        $this->assertTrue($admin->can('admin.ventas.ver'));
        $this->assertFalse($cajero->can('admin.ventas.ver'));
        $this->actingAs($cajero)->get('/admin/ventas')->assertForbidden();

        // Cuando el administrador marca la casilla desde admin/roles, el
        // empleado entra y ve únicamente su propia pestaña.
        $cajero->roles->first()->givePermissionTo('admin.ventas.ver');

        $ventaDeOtro = Order::create([
            'user_id' => User::factory()->create()->id,
            'cliente_nombre' => 'Cliente Otro Cajero',
            'total' => 15,
            'total_final' => 15,
            'pago_tipo' => 'efectivo',
            'estado' => Order::ESTADO_COMPLETADO,
            'origen' => 'caja',
            'numero_ficha' => 'F-20260929-0032',
        ]);

        $ventaCajero = Order::create([
            'user_id' => $cajero->id,
            'cliente_nombre' => 'Cliente Cajero',
            'total' => 20,
            'total_final' => 20,
            'pago_tipo' => 'efectivo',
            'estado' => Order::ESTADO_COMPLETADO,
            'origen' => 'caja',
            'numero_ficha' => 'F-20260929-0031',
        ]);

        $this->actingAs($cajero)->get('/admin/ventas')
            ->assertOk()
            ->assertSee('Mis ventas')
            ->assertSee('Cliente Cajero')
            ->assertDontSee('Ventas del Administrador');

        $this->actingAs($cajero)->get(route('admin.caja.ficha', $ventaCajero))
            ->assertOk()
            ->assertSee('Cliente Cajero');

        $this->actingAs($cajero)->get(route('admin.caja.ficha', $ventaDeOtro))
            ->assertForbidden();
    }

    public function test_ventas_resume_solo_ventas_completadas_y_ranquea_productos_y_responsables(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->account(User::ROL_ADMIN);
        $employee = User::factory()->create([
            'name' => 'Empleado Fuerte',
            'email' => 'fuerte@example.test',
        ]);
        $employee->assignRole($this->cashierRole());

        $orderAdmin = Order::create([
            'user_id' => $admin->id,
            'cliente_nombre' => 'Venta del administrador',
            'total' => 30,
            'total_final' => 30,
            'pago_tipo' => 'efectivo',
            'estado' => Order::ESTADO_COMPLETADO,
            'origen' => 'caja',
            'numero_ficha' => 'F-20261011-0001',
        ]);
        $orderEmployee = Order::create([
            'user_id' => $employee->id,
            'cliente_nombre' => 'Venta del empleado',
            'total' => 55,
            'total_final' => 55,
            'pago_tipo' => 'efectivo',
            'estado' => Order::ESTADO_COMPLETADO,
            'origen' => 'caja',
            'numero_ficha' => 'F-20261011-0002',
        ]);
        OrderItem::create([
            'order_id' => $orderAdmin->id,
            'nombre_producto' => 'Torta de chocolate',
            'precio' => 10,
            'cantidad' => 1,
        ]);
        OrderItem::create([
            'order_id' => $orderEmployee->id,
            'nombre_producto' => 'Torta de chocolate',
            'precio' => 10,
            'cantidad' => 3,
        ]);

        $this->actingAs($admin)->get('/admin/ventas')
            ->assertOk()
            ->assertSee('Torta de chocolate')
            ->assertSee('4 unidades')
            ->assertSee('Mayor venta: Bs. 55.00')
            ->assertSee('Bs. 85.00')
            ->assertSee('Venta del empleado');

        $this->actingAs($admin)->get('/admin/ventas?producto=Torta%20de%20chocolate')
            ->assertOk()
            ->assertSee('Venta del administrador')
            ->assertSee('Venta del empleado');
    }

    public function test_pedidos_no_muestra_ventas(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->account(User::ROL_ADMIN);

        $cajero = User::create([
            'name' => 'Cajero de prueba',
            'email' => 'cajero@prueba.test',
            'password' => 'password123',
        ]);
        $cajero->assignRole($this->cashierRole());

        Order::create([
            'user_id' => $cajero->id,
            'cliente_nombre' => 'Cliente Cajero',
            'total' => 20,
            'total_final' => 20,
            'pago_tipo' => 'efectivo',
            'estado' => Order::ESTADO_COMPLETADO,
            'origen' => 'caja',
            'numero_ficha' => 'F-20260929-0009',
        ]);

        // El tablero de pedidos quedó dedicado sólo a pedidos: ni la ficha ni
        // el nombre del cliente de la venta pueden aparecer ahí.
        $this->actingAs($admin)->get('/admin/pedidos')
            ->assertOk()
            ->assertDontSee('Cliente Cajero', false)
            ->assertDontSee('F-20260929-0009', false)
            ->assertDontSee('Ventas cobradas en Caja');
    }

    public function test_el_apartado_pedido_del_cliente_muestra_productos_cantidades_y_total(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = $this->account(User::ROL_ADMIN);

        $producto = Product::create([
            'nombre' => 'Tres Leches',
            'precio' => 25,
            'stock' => 10,
            'estado' => true,
            'en_menu_digital' => true,
        ]);

        $pedido = Pedido::create([
            'cliente_nombre' => 'María',
            'cliente_apellido' => 'Quispe',
            'cliente_telefono' => '70123456',
            'detalles' => 'Entregar con vela de cumpleaños',
            'fecha_pedido' => now()->toDateString(),
            'fecha_entrega' => now()->addDay()->toDateString(),
            'hora_entrega' => '15:30',
            'total_estimado' => 75,
            'abono' => 25,
            'user_id' => $admin->id,
        ]);

        $pedido->items()->create([
            'product_id' => $producto->id,
            'nombre_producto' => 'Tres Leches',
            'precio' => 25,
            'cantidad' => 3,
            'observacion' => 'Sin crema de coco',
        ]);

        $this->actingAs($admin)->get('/admin/pedidos?pedido='.$pedido->id)
            ->assertOk()
            ->assertSee('Pedido del cliente')
            ->assertSee('María Quispe')
            ->assertSee('70123456')
            ->assertSee('Tres Leches')
            ->assertSee('Sin crema de coco')
            ->assertSee('Entregar con vela de cumpleaños')
            ->assertSee('Bs. 75.00')
            ->assertSee('Bs. 25.00')
            ->assertSee('Bs. 50.00');
    }
}
