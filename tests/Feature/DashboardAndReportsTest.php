<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Pedido;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardAndReportsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create(['name' => 'Administradora']);
        $this->admin->assignRole(User::ROL_ADMIN);

        $employeeRole = Role::create(['name' => 'empleado-reportes']);
        $employeeRole->givePermissionTo([
            'admin.ventas.ver',
            'admin.reportes.ver',
        ]);
        $this->employee = User::factory()->create(['name' => 'Empleado']);
        $this->employee->assignRole($employeeRole);
    }

    public function test_dashboard_summarizes_admin_and_employee_sales(): void
    {
        $this->createSale($this->admin, 'Venta admin', 20);
        $this->createSale($this->employee, 'Venta empleado', 30);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Bs. 50,00');
    }

    public function test_reports_show_admin_and_employee_sales_totals(): void
    {
        $this->createSale($this->admin, 'Venta administradora', 20);
        $this->createSale($this->employee, 'Venta empleado', 30);
        $this->createSale($this->admin, 'Venta anulada', 12, Order::ESTADO_CANCELADO);

        $this->actingAs($this->admin)
            ->get(route('admin.reportes.index', ['tipo' => 'ventas']))
            ->assertOk()
            ->assertSee('Bs. 50,00')
            ->assertSee('Venta administradora')
            ->assertSee('Venta empleado')
            ->assertSee('Venta anulada');
    }

    public function test_admin_can_access_sales_and_order_reports(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reportes.index', ['tipo' => 'ventas']))
            ->assertOk();

        $this->actingAs($this->admin)
            ->get(route('admin.reportes.index', ['tipo' => 'pedidos']))
            ->assertOk();
    }

    public function test_sidebar_groups_reporting_and_order_management_links_under_reports(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Reportes')
            ->assertSee('Reporte general')
            ->assertSee('Reporte de ventas')
            ->assertSee('Gestión de pedidos')
            ->assertSee('Pedidos personalizados')
            ->assertSee('href="'.route('admin.reportes.index').'"', false)
            ->assertSee('href="'.route('admin.ventas.index').'"', false)
            ->assertSee('href="'.route('admin.pedidos.index').'"', false)
            ->assertSee('href="'.route('admin.pedidos.personalizados.index').'"', false)
            ->assertSee('Calendario de entregas');
    }

    public function test_employee_reports_are_limited_to_own_sales_and_exports_escape_spreadsheet_formulas(): void
    {
        $this->createSale($this->employee, '=HYPERLINK("https://example.test")', 15);
        $this->createSale($this->admin, 'Venta ajena', 35);

        $this->actingAs($this->employee)
            ->get(route('admin.reportes.index', ['tipo' => 'ventas']))
            ->assertOk()
            ->assertSee('=HYPERLINK')
            ->assertDontSee('Venta ajena');

        $response = $this->actingAs($this->employee)
            ->get(route('admin.reportes.excel', ['tipo' => 'ventas']));
        $csv = $response->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertStringNotContainsString('Venta ajena', $csv);
    }

    public function test_order_report_filters_delivery_dates_and_print_view_is_available(): void
    {
        Pedido::create([
            'cliente_nombre' => 'Pedido en rango',
            'fecha_pedido' => today(),
            'fecha_entrega' => today()->addDay(),
            'total_estimado' => 80,
            'user_id' => $this->employee->id,
        ]);
        Pedido::create([
            'cliente_nombre' => 'Pedido fuera de rango',
            'fecha_pedido' => today(),
            'fecha_entrega' => today()->addDays(3),
            'total_estimado' => 40,
            'user_id' => $this->employee->id,
        ]);

        $filters = [
            'tipo' => 'pedidos',
            'desde' => today()->toDateString(),
            'hasta' => today()->addDays(1)->toDateString(),
        ];

        $this->actingAs($this->admin)
            ->get(route('admin.reportes.index', $filters))
            ->assertOk()
            ->assertSee('Pedido en rango')
            ->assertDontSee('Pedido fuera de rango');

        $this->actingAs($this->admin)
            ->get(route('admin.reportes.imprimir', $filters))
            ->assertOk()
            ->assertSee('Pedido en rango')
            ->assertDontSee('Pedido fuera de rango')
            ->assertSee('Guardar como PDF');
    }

    private function createSale(User $user, string $customer, float $total, string $state = Order::ESTADO_COMPLETADO): Order
    {
        $order = Order::create([
            'user_id' => $user->id,
            'cliente_nombre' => $customer,
            'cliente_nit' => '1234567',
            'total' => $total,
            'total_final' => $total,
            'pago_tipo' => 'efectivo',
            'estado' => $state,
        ]);

        if ($state === Order::ESTADO_COMPLETADO) {
            OrderItem::create([
                'order_id' => $order->id,
                'nombre_producto' => 'Torta de prueba',
                'precio' => $total,
                'cantidad' => 1,
            ]);
        }

        return $order;
    }
}
