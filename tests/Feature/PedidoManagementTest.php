<?php

namespace Tests\Feature;

use App\Models\Pedido;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PedidoManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $employee;

    private function accounts(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $this->admin = User::factory()->create([
            'name' => 'Administradora',
            'email' => 'admin@example.test',
        ]);
        $this->admin->assignRole(User::ROL_ADMIN);

        $employeeRole = Role::create(['name' => 'empleado-pedidos']);
        $employeeRole->givePermissionTo([
            'admin.pedidos.ver',
            'admin.pedidos.crear',
            'admin.pedidos.editar',
            'admin.pedidos.anular',
        ]);
        $this->employee = User::factory()->create([
            'name' => 'Empleado Uno',
            'email' => 'empleado@example.test',
        ]);
        $this->employee->assignRole($employeeRole);
    }

    private function product(): Product
    {
        return Product::create([
            'nombre' => 'Torta de chocolate',
            'precio' => 35.50,
            'stock' => 10,
            'estado' => true,
            'en_menu_digital' => true,
        ]);
    }

    private function pedidoData(array $overrides = []): array
    {
        return array_replace([
            'cliente_nombre' => 'Daniela',
            'cliente_apellido' => 'Pérez',
            'cliente_telefono' => '70123456',
            'fecha_pedido' => today()->toDateString(),
            'fecha_entrega' => today()->addDay()->toDateString(),
            'hora_entrega' => '15:30',
            'estado' => Pedido::ESTADO_PENDIENTE,
            'total_estimado' => '71.00',
            'abono' => '10.00',
        ], $overrides);
    }

    public function test_admin_can_access_orders_and_the_orders_calendar(): void
    {
        $this->accounts();

        $this->assertTrue(Gate::forUser($this->admin)->allows('admin.pedidos.ver'));
        $this->actingAs($this->admin)->get(route('admin.pedidos.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.pedidos.create'))->assertOk();
        $this->actingAs($this->admin)->get(route('admin.pedidos.personalizados.index'))->assertOk();
        $this->actingAs($this->admin)
            ->get(route('admin.pedidos.personalizados.create'))
            ->assertOk()
            ->assertSee('action="'.route('admin.pedidos.personalizados.store').'"', false);
        $this->actingAs($this->admin)->get(route('admin.calendar.index'))->assertOk();
    }

    public function test_custom_order_uses_manual_price_persists_audit_and_feeds_calendar(): void
    {
        $this->accounts();
        $product = $this->product();

        $response = $this->actingAs($this->employee)
            ->post(route('admin.pedidos.store'), $this->pedidoData([
                'user_id' => $this->admin->id,
                'fecha_pedido' => today()->addDays(5)->toDateString(),
                'tipo_encargo' => 'Torta personalizada',
                'ocasion' => 'Cumpleaños',
                'porciones' => 20,
                'sabor' => 'Vainilla',
                'relleno' => 'Frutilla',
                'cobertura' => 'Crema chantilly',
                'colores' => 'Verde y dorado',
                'texto_torta' => 'Feliz cumpleaños',
                'alergias' => 'Sin nueces',
                'decoracion' => 'Diseño floral moderno',
                'detalles' => 'Entregar con caja alta',
            ]))
            ->assertRedirect();

        $pedido = Pedido::with(['items', 'creator', 'lastEditor'])->firstOrFail();

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'tipo' => Pedido::TIPO_PERSONALIZADO,
            'user_id' => $this->employee->id,
            'created_by' => $this->employee->id,
            'updated_by' => $this->employee->id,
            'total_estimado' => '71.00',
            'abono' => '10.00',
        ]);
        $this->assertCount(0, $pedido->items);
        $this->assertSame('Torta personalizada', $pedido->tipo_encargo);
        $this->assertSame('Cumpleaños', $pedido->ocasion);
        $this->assertSame(today()->toDateString(), $pedido->fecha_pedido->toDateString());
        $this->assertSame(20, $pedido->porciones);
        $this->assertSame('Vainilla', $pedido->sabor);
        $this->assertSame('Sin nueces', $pedido->alergias);
        $this->assertSame('Diseño floral moderno', $pedido->decoracion);
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame($this->employee->id, $pedido->creator->id);
        $this->assertSame($this->employee->id, $pedido->lastEditor->id);

        $this->actingAs($this->employee)
            ->get(route('admin.calendar.events', [
                'start' => today()->toDateString(),
                'end' => today()->addDays(4)->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => 'pedido-'.$pedido->id,
                'title' => $pedido->codigo.' · Daniela Pérez',
            ]);

        $this->actingAs($this->employee)
            ->get(route('admin.calendar.events', [
                'start' => today()->toDateString(),
                'end' => today()->addDay()->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonMissing(['id' => 'pedido-'.$pedido->id]);

        $this->actingAs($this->employee)
            ->get(route('admin.calendar.events', [
                'start' => today()->addDay()->toDateString(),
                'end' => today()->addDays(2)->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => 'pedido-'.$pedido->id,
                'start' => $pedido->inicio_calendar,
            ]);

        $this->actingAs($this->employee)
            ->get(route('admin.pedidos.edit', ['pedido' => $pedido, 'modal' => 1]))
            ->assertOk()
            ->assertSee('Editar pedido '.$pedido->codigo)
            ->assertSee('action="'.route('admin.pedidos.update', $pedido).'"', false);
    }

    public function test_employee_can_see_other_orders_but_cannot_edit_them(): void
    {
        $this->accounts();
        $otherEmployee = User::factory()->create([
            'name' => 'Empleado Dos',
            'email' => 'empleado2@example.test',
        ]);
        $otherEmployee->assignRole($this->employee->roles()->first());

        $otherOrder = Pedido::create([
            'cliente_nombre' => 'Cliente de otro empleado',
            'fecha_pedido' => today(),
            'fecha_entrega' => today()->addDay(),
            'user_id' => $otherEmployee->id,
            'created_by' => $otherEmployee->id,
            'updated_by' => $otherEmployee->id,
        ]);

        $this->actingAs($this->employee)
            ->get(route('admin.pedidos.index'))
            ->assertOk()
            ->assertSee('Cliente de otro empleado')
            ->assertSee('Empleado Dos')
            ->assertDontSee('Administrador Maestro');

        $this->actingAs($this->employee)
            ->get(route('admin.pedidos.edit', $otherOrder))
            ->assertForbidden();

        $this->actingAs($this->employee)
            ->put(route('admin.pedidos.update', $otherOrder), $this->pedidoData())
            ->assertForbidden();

        $this->assertSame('Cliente de otro empleado', $otherOrder->fresh()->cliente_nombre);
    }

    public function test_employee_can_edit_own_order_and_admin_can_edit_any_order_with_audit(): void
    {
        $this->accounts();
        $pedido = Pedido::create([
            'cliente_nombre' => 'Cliente',
            'fecha_pedido' => today(),
            'fecha_entrega' => today()->addDay(),
            'user_id' => $this->employee->id,
            'created_by' => $this->employee->id,
            'updated_by' => $this->employee->id,
        ]);
        $pedido->items()->create([
            'nombre_producto' => 'Torta histórica',
            'precio' => '20.00',
            'cantidad' => 1,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.pedidos.update', $pedido), $this->pedidoData([
                'user_id' => $this->admin->id,
            ]))
            ->assertRedirect(route('admin.pedidos.show', $pedido))
            ->assertSessionHasNoErrors();

        $pedido->refresh();
        $this->assertSame($this->employee->id, $pedido->user_id);
        $this->assertSame($this->employee->id, $pedido->created_by);
        $this->assertSame($this->admin->id, $pedido->updated_by);
        $this->assertSame('Torta histórica', $pedido->items()->sole()->nombre_producto);

        $this->actingAs($this->admin)
            ->get(route('admin.pedidos.show', $pedido))
            ->assertOk()
            ->assertSee('Registrado por:')
            ->assertSee('Última modificación por:')
            ->assertSee('Administradora');
    }

    public function test_creation_requires_manual_price_and_valid_delivery_date(): void
    {
        $this->accounts();

        $this->actingAs($this->employee)
            ->from(route('admin.pedidos.create'))
            ->post(route('admin.pedidos.store'), $this->pedidoData(['total_estimado' => null]))
            ->assertRedirect(route('admin.pedidos.create'))
            ->assertSessionHasErrors('total_estimado');

        $this->post(route('admin.pedidos.store'), $this->pedidoData([
            'fecha_entrega' => today()->subDay()->toDateString(),
        ]))->assertSessionHasErrors('fecha_entrega');

        $this->post(route('admin.pedidos.store'), $this->pedidoData([
            'cliente_telefono' => '',
        ]))->assertSessionHasErrors('cliente_telefono');

        $this->post(route('admin.pedidos.store'), $this->pedidoData([
            'abono' => '71.01',
        ]))->assertSessionHasErrors('abono');

        $this->assertDatabaseCount('pedidos', 0);
    }

    public function test_custom_order_reference_image_is_stored_and_can_be_removed(): void
    {
        $this->accounts();
        Storage::fake('public');

        $this->actingAs($this->employee)
            ->post(route('admin.pedidos.personalizados.store'), array_merge(
                $this->pedidoData(),
                ['imagen_referencia' => UploadedFile::fake()->image('diseno.png')]
            ))
            ->assertRedirect();

        $pedido = Pedido::firstOrFail();
        $path = $pedido->imagen_referencia;
        $this->assertNotNull($path);
        Storage::disk('public')->assertExists($path);
        $this->get(route('admin.pedidos.imagen-referencia', $pedido))->assertOk();

        $this->actingAs($this->employee)
            ->put(route('admin.pedidos.update', $pedido), $this->pedidoData([
                'quitar_imagen_referencia' => '1',
            ]))
            ->assertRedirect();

        $this->assertNull($pedido->fresh()->imagen_referencia);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_custom_order_form_has_no_catalog_picker_and_saves_without_items_or_stock_changes(): void
    {
        $this->accounts();
        $product = $this->product();
        $product->update(['en_menu_digital' => false]);

        $this->actingAs($this->employee)
            ->get(route('admin.pedidos.personalizados.create'))
            ->assertOk()
            ->assertSee('Personalización del encargo')
            ->assertSee('Monto acordado')
            ->assertDontSee('Torta de chocolate')
            ->assertDontSee('items[', false);

        $data = $this->pedidoData([
            'ocasion' => 'Boda',
            'colores' => 'Blanco y verde',
            'total_estimado' => '450.00',
            'items' => [[
                'id' => $product->id,
                'nombre' => 'Torta de chocolate',
                'precio' => '0.01',
                'cantidad' => 999,
            ]],
        ]);

        $this->actingAs($this->employee)
            ->post(route('admin.pedidos.personalizados.store'), $data)
            ->assertRedirect();

        $pedido = Pedido::with('items')->firstOrFail();
        $this->assertSame(Pedido::TIPO_PERSONALIZADO, $pedido->tipo);
        $this->assertSame('450.00', $pedido->total_estimado);
        $this->assertSame('Boda', $pedido->ocasion);
        $this->assertCount(0, $pedido->items);
        $this->assertSame(10, $product->fresh()->stock);

        $this->actingAs($this->employee)
            ->put(route('admin.pedidos.update', $pedido), $data)
            ->assertRedirect();

        $this->assertSame(Pedido::TIPO_PERSONALIZADO, $pedido->fresh()->tipo);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_custom_orders_list_excludes_digital_menu_orders(): void
    {
        $this->accounts();
        Pedido::create([
            'tipo' => Pedido::TIPO_PERSONALIZADO,
            'cliente_nombre' => 'Encargo a medida',
            'fecha_pedido' => today(),
            'fecha_entrega' => today()->addDay(),
        ]);
        Pedido::create([
            'tipo' => Pedido::TIPO_MENU_DIGITAL,
            'cliente_nombre' => 'Pedido del menú',
            'fecha_pedido' => today(),
            'fecha_entrega' => today()->addDay(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.pedidos.personalizados.index'))
            ->assertOk()
            ->assertSee('Encargo a medida')
            ->assertDontSee('Pedido del menú');
    }

    public function test_calendar_modal_uses_the_shared_order_create_update_and_cancel_flow(): void
    {
        $this->accounts();
        $data = $this->pedidoData([
            'fecha_entrega' => today()->addDays(2)->toDateString(),
        ]);

        $created = $this->actingAs($this->employee)
            ->postJson(route('admin.pedidos.store'), $data)
            ->assertOk()
            ->assertJsonPath('success', true);
        $pedido = Pedido::with('items')->findOrFail($created->json('pedido_id'));

        $this->assertSame($this->employee->id, $pedido->user_id);
        $this->assertDatabaseHas('pedidos', ['id' => $pedido->id]);

        $this->actingAs($this->employee)
            ->get(route('admin.calendar.events', [
                'start' => today()->toDateString(),
                'end' => today()->addDays(4)->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonFragment(['id' => 'pedido-'.$pedido->id]);

        $updatedData = array_replace($data, ['cliente_nombre' => 'Nombre corregido']);
        $this->actingAs($this->employee)
            ->putJson(route('admin.pedidos.update', $pedido), $updatedData)
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'cliente_nombre' => 'Nombre corregido',
        ]);

        $this->actingAs($this->employee)
            ->patchJson(route('admin.pedidos.cancel', $pedido))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'estado' => Pedido::ESTADO_CANCELADO,
        ]);
    }

    public function test_cancelling_keeps_the_order_and_calendar_record(): void
    {
        $this->accounts();
        $pedido = Pedido::create([
            'cliente_nombre' => 'Cliente a cancelar',
            'fecha_pedido' => today(),
            'fecha_entrega' => today()->addDay(),
            'user_id' => $this->employee->id,
            'created_by' => $this->employee->id,
            'updated_by' => $this->employee->id,
        ]);

        $this->actingAs($this->employee)
            ->patch(route('admin.pedidos.cancel', $pedido))
            ->assertRedirect(route('admin.pedidos.index'));

        $this->assertDatabaseHas('pedidos', [
            'id' => $pedido->id,
            'estado' => Pedido::ESTADO_CANCELADO,
            'updated_by' => $this->employee->id,
        ]);

        $this->actingAs($this->employee)
            ->get(route('admin.calendar.events', [
                'start' => today()->toDateString(),
                'end' => today()->addDays(3)->toDateString(),
            ]))
            ->assertOk()
            ->assertJsonFragment([
                'id' => 'pedido-'.$pedido->id,
                'estado' => 'Cancelado',
            ]);
    }

    public function test_order_and_calendar_views_have_one_complete_creation_flow(): void
    {
        $this->accounts();

        $this->actingAs($this->admin)
            ->get(route('admin.pedidos.index'))
            ->assertOk()
            ->assertDontSee('Crear pedido')
            ->assertDontSee('Pedido Personalizado')
            ->assertDontSee('Crear otro pedido');

        $this->actingAs($this->admin)
            ->get(route('admin.pedidos.personalizados.index'))
            ->assertOk()
            ->assertSee('Nuevo pedido personalizado');

        $this->actingAs($this->admin)
            ->get(route('admin.calendar.index'))
            ->assertOk()
            ->assertDontSee('Nuevo Pedido / Evento')
            ->assertSee('id="pedidoModal"', false);

        $this->actingAs($this->admin)->post('/admin/calendar/store')->assertNotFound();

        $this->actingAs($this->admin)
            ->get(route('admin.pedidos.create', [
                'modal' => 1,
                'fecha_entrega' => today()->addDays(4)->toDateString(),
            ]))
            ->assertOk()
            ->assertSee('Nuevo pedido personalizado')
            ->assertSee('name="fecha_entrega"', false)
            ->assertSee('value="'.today()->addDays(4)->toDateString().'"', false);
    }
}
