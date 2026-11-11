<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MenuDigitalTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private function setUpAdmin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $this->admin = User::factory()->create();
        $this->admin->assignRole(User::ROL_ADMIN);
    }

    private function product(array $attributes = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'tortas'],
            ['nombre' => 'Tortas']
        );

        return Product::create(array_replace([
            'category_id' => $category->id,
            'nombre' => 'Torta de frutilla',
            'descripcion' => 'Torta fresca artesanal',
            'precio' => 30,
            'stock' => 5,
            'estado' => true,
            'destacado' => false,
            'en_menu_digital' => true,
        ], $attributes));
    }

    public function test_public_menu_shows_selected_products_independently_of_stock_and_catalog_status(): void
    {
        $this->product(['nombre' => 'Visible en menú']);
        $this->product(['nombre' => 'No seleccionado', 'en_menu_digital' => false]);
        $this->product(['nombre' => 'Producto inactivo', 'estado' => false]);
        $this->product(['nombre' => 'Producto agotado', 'stock' => 0]);

        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('Visible en menú')
            ->assertSee('Producto inactivo')
            ->assertSee('scene-pills')
            ->assertSee('menu-digital-grid')
            ->assertDontSee('rotation-progress')
            ->assertDontSee('rotationDuration')
            ->assertDontSee('updateProgress')
            ->assertDontSee('nextPage')
            ->assertDontSee('No seleccionado')
            ->assertSee('Producto agotado')
            ->assertDontSee('5 disponibles')
            ->assertDontSee('Mi pedido');

        $this->get(route('menu', ['pantalla' => 1]))
            ->assertOk()
            ->assertSee('Modo Pantalla Completa')
            ->assertDontSee('Mi pedido');
    }

    public function test_digital_menu_is_visual_and_does_not_filter_products_by_stock(): void
    {
        $this->setUpAdmin();
        $this->product(['nombre' => 'Visible en ambos']);
        $this->product(['nombre' => 'Oculto en ambos', 'en_menu_digital' => false]);
        $this->product(['nombre' => 'Inactivo en ambos', 'estado' => false]);
        $this->product(['nombre' => 'Agotado en ambos', 'stock' => 0]);

        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('Visible en ambos')
            ->assertSee('Inactivo en ambos')
            ->assertSee('Agotado en ambos')
            ->assertDontSee('Oculto en ambos');

        $this->actingAs($this->admin)->get(route('admin.caja.index'))
            ->assertOk()
            ->assertSee('Visible en ambos')
            ->assertSee('Inactivo en ambos')
            ->assertDontSee('Agotado en ambos')
            ->assertDontSee('Oculto en ambos');
    }

    public function test_digital_display_renders_real_catalog_products_without_prototype_content(): void
    {
        $this->product([
            'nombre' => 'Torta real de frutilla',
            'descripcion' => 'Relleno de crema y fruta fresca',
            'precio' => 30,
            'destacado' => true,
        ]);

        $this->get(route('menu', ['pantalla' => 1]))
            ->assertOk()
            ->assertSee('Torta real de frutilla')
            ->assertSee('Relleno de crema y fruta fresca')
            ->assertSee('30,00')
            ->assertSee(asset('img/default.jpg'), false)
            ->assertSee('object-contain', false)
            ->assertDontSee('Tres Leches Artesanal')
            ->assertDontSee('googleusercontent');
    }

    public function test_public_menu_feed_returns_current_product_data_for_the_display(): void
    {
        $this->product([
            'nombre' => 'Producto del feed',
            'descripcion' => 'Descripción real',
            'precio' => 27.5,
            'destacado' => true,
            'imagen' => 'img/producto.jpg',
        ]);

        $feed = $this->getJson(route('menu.datos'))
            ->assertOk()
            ->assertJsonPath('slides.0.key', 'featured')
            ->assertJsonPath('slides.0.products.0.name', 'Producto del feed')
            ->assertJsonPath('slides.0.products.0.description', 'Descripción real')
            ->assertJsonPath('slides.0.products.0.category', 'Tortas')
            ->assertJsonPath('slides.0.products.0.price', 27.5)
            ->assertJsonPath('slides.0.products.0.image', asset('img/producto.jpg'));

        $this->assertArrayNotHasKey('stock', $feed->json('slides.0.products.0'));
    }

    public function test_public_menu_feed_includes_every_product_in_a_category_for_vertical_browsing(): void
    {
        foreach (range(1, 9) as $number) {
            $this->product(['nombre' => "Producto de categoría {$number}"]);
        }

        $this->getJson(route('menu.datos'))
            ->assertOk()
            ->assertJsonCount(9, 'slides.0.products');

        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('overflow-y: auto', false)
            ->assertSee('additional-menu-groups', false)
            ->assertSee('additional-menu-card', false)
            ->assertSee('grid-template-columns: repeat(2, minmax(0, 1fr))', false)
            ->assertSee('index += 4', false);
    }

    public function test_uploaded_product_images_are_served_from_the_public_storage_disk(): void
    {
        $this->setUpAdmin();
        Storage::fake('public');
        Storage::disk('public')->put('productos/producto-prueba.jpg', 'imagen-de-prueba');
        $product = $this->product([
            'imagen' => 'storage/productos/producto-prueba.jpg',
        ]);

        $this->get(route('product-images.show', 'producto-prueba.jpg'))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $imageUrl = asset($product->imagen);
        $this->get(route('menu'))
            ->assertOk()
            ->assertSee($imageUrl, false);
        $this->actingAs($this->admin)->get(route('admin.caja.index'))
            ->assertOk()
            ->assertSee($imageUrl, false);

        $this->getJson(route('menu.datos'))
            ->assertOk()
            ->assertJsonPath('slides.0.products.0.image', $imageUrl);
    }

    public function test_admin_can_manage_visibility_and_open_menu_management_screen(): void
    {
        $this->setUpAdmin();
        $product = $this->product(['en_menu_digital' => false]);
        $category = Category::create(['slug' => 'bebidas', 'nombre' => 'Bebidas']);
        $this->product([
            'category_id' => $category->id,
            'nombre' => 'Limonada',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.menu-digital.index'))
            ->assertOk()
            ->assertSee('Menú Digital')
            ->assertSee('Limonada')
            ->assertSeeInOrder(['Bebidas', 'Limonada'])
            ->assertSee('<details class="menu-category-block">', false)
            ->assertDontSee('<details class="menu-category-block" open>', false)
            ->assertSee('menu-qr-code', false)
            ->assertSee('Añadir menú')
            ->assertSee('data-menu-url="http://192.168.137.1:8000/menu"', false)
            ->assertSee('Imprimir QR')
            ->assertSee('text: menuUrl', false)
            ->assertSee(route('menu', ['pantalla' => 1]), false)
            ->assertDontSee('Vista para pantalla grande')
            ->assertDontSee('Productos registrados')
            ->assertDontSee('Añadir categoría')
            ->assertDontSee('Nuevo producto');

        $this->assertSame(
            1,
            substr_count($this->get(route('admin.menu-digital.index'))->getContent(), 'class="d-block text-truncate"')
        );

        $screenUrl = route('menu', ['pantalla' => 1]);
        $this->assertSame(
            1,
            substr_count($this->get(route('admin.menu-digital.index'))->getContent(), 'href="'.$screenUrl.'"')
        );

        $this->actingAs($this->admin)
            ->patch(route('admin.menu-digital.toggle', $product))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($product->fresh()->en_menu_digital);
    }

    public function test_admin_can_replace_the_digital_menu_selection_without_changing_stock(): void
    {
        $this->setUpAdmin();
        $selected = $this->product([
            'nombre' => 'Producto ya publicado',
            'en_menu_digital' => true,
            'stock' => 0,
        ]);
        $unselected = $this->product([
            'nombre' => 'Producto para añadir',
            'en_menu_digital' => false,
            'stock' => 3,
        ]);

        $selectionHtml = $this->actingAs($this->admin)
            ->get(route('admin.menu-digital.index'))
            ->assertOk()
            ->assertSee('Producto ya publicado')
            ->assertSee('Producto para añadir')
            ->getContent();
        $selectionHtml = preg_replace('/\s+/', ' ', $selectionHtml);
        $this->assertStringContainsString(
            'name="product_ids[]" value="'.$selected->id.'" class="js-product-selection-checkbox" checked',
            $selectionHtml
        );
        $this->assertStringContainsString(
            'name="product_ids[]" value="'.$unselected->id.'" class="js-product-selection-checkbox"',
            $selectionHtml
        );
        $this->assertStringNotContainsString(
            'name="product_ids[]" value="'.$unselected->id.'" class="js-product-selection-checkbox" checked',
            $selectionHtml
        );

        $this->actingAs($this->admin)
            ->put(route('admin.menu-digital.selection'), [
                'product_ids' => [$unselected->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($selected->fresh()->en_menu_digital);
        $this->assertTrue($unselected->fresh()->en_menu_digital);
        $this->assertSame(0, $selected->fresh()->stock);
        $this->assertSame(3, $unselected->fresh()->stock);

        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('Producto para añadir')
            ->assertDontSee('Producto ya publicado');

        $this->actingAs($this->admin)
            ->put(route('admin.menu-digital.selection'))
            ->assertRedirect();

        $this->assertFalse($selected->fresh()->en_menu_digital);
        $this->assertFalse($unselected->fresh()->en_menu_digital);
        $this->assertSame(0, $selected->fresh()->stock);
        $this->assertSame(3, $unselected->fresh()->stock);
    }

    public function test_catalog_admin_can_select_products_in_bulk_and_public_catalog_updates_without_stock_changes(): void
    {
        $this->setUpAdmin();
        $visible = $this->product([
            'nombre' => 'Torta visible en catálogo',
            'estado' => true,
            'stock' => 0,
            'imagen' => 'img/torta-catalogo.jpg',
        ]);
        $hidden = $this->product([
            'nombre' => 'Postre para publicar',
            'estado' => false,
            'stock' => 6,
        ]);

        $catalogHtml = $this->actingAs($this->admin)
            ->get(route('admin.catalogo.index'))
            ->assertOk()
            ->assertSee('Seleccionar productos')
            ->assertSee('Productos actualmente en el catálogo')
            ->assertSee('Torta visible en catálogo')
            ->assertDontSee('Postre para publicar</h3>', false)
            ->assertSee('type="search"', false)
            ->assertSee(route('admin.catalogo.selection'), false)
            ->getContent();
        $catalogHtml = preg_replace('/\s+/', ' ', $catalogHtml);
        $this->assertStringContainsString(
            'name="product_ids[]" value="'.$visible->id.'" class="js-product-selection-checkbox" checked',
            $catalogHtml
        );
        $this->assertStringContainsString(
            'name="product_ids[]" value="'.$hidden->id.'" class="js-product-selection-checkbox"',
            $catalogHtml
        );

        $this->actingAs($this->admin)
            ->put(route('admin.catalogo.selection'), [
                'product_ids' => [$hidden->id],
            ])
            ->assertRedirect(route('admin.catalogo.index'))
            ->assertSessionHas('success');

        $this->assertFalse($visible->fresh()->estado);
        $this->assertTrue($hidden->fresh()->estado);
        $this->assertSame(0, $visible->fresh()->stock);
        $this->assertSame(6, $hidden->fresh()->stock);

        $this->get(route('catalogo'))
            ->assertOk()
            ->assertSee('Postre para publicar')
            ->assertDontSee('Torta visible en catálogo');

        $this->actingAs($this->admin)
            ->put(route('admin.catalogo.selection'))
            ->assertRedirect(route('admin.catalogo.index'));

        $this->assertFalse($hidden->fresh()->estado);
        $this->assertSame(6, $hidden->fresh()->stock);
        $this->get(route('catalogo'))->assertDontSee('Postre para publicar');
    }

    public function test_catalog_selection_requires_catalog_permission(): void
    {
        $this->setUpAdmin();
        $role = Role::create(['name' => 'empleado-sin-catalogo']);
        $employee = User::factory()->create();
        $employee->assignRole($role);
        $product = $this->product(['estado' => false]);

        $this->actingAs($employee)
            ->put(route('admin.catalogo.selection'), ['product_ids' => [$product->id]])
            ->assertForbidden();

        $this->assertFalse($product->fresh()->estado);
    }

    public function test_enabling_a_product_adds_only_that_product_to_its_existing_menu_category_without_stock_changes(): void
    {
        $this->setUpAdmin();
        $category = Category::create(['nombre' => 'Postres', 'slug' => 'postres']);
        $product = $this->product([
            'nombre' => 'Tres leches de prueba',
            'category_id' => $category->id,
            'en_menu_digital' => false,
            'stock' => 7,
        ]);
        $otherProduct = $this->product([
            'nombre' => 'Brownie no publicado',
            'category_id' => $category->id,
            'en_menu_digital' => false,
            'stock' => 4,
        ]);

        $this->actingAs($this->admin)
            ->patch(route('admin.menu-digital.toggle', $product))
            ->assertRedirect();

        $this->assertSame(1, Category::query()->where('slug', 'postres')->count());
        $this->assertSame(7, $product->fresh()->stock);
        $this->assertSame(4, $otherProduct->fresh()->stock);

        $feed = $this->getJson(route('menu.datos'))
            ->assertOk()
            ->assertJsonCount(1, 'slides')
            ->assertJsonPath('slides.0.key', 'category-'.$category->id)
            ->assertJsonPath('slides.0.label', 'Postres')
            ->assertJsonCount(1, 'slides.0.products')
            ->assertJsonPath('slides.0.products.0.name', 'Tres leches de prueba');

        $this->assertFalse(collect($feed->json('slides.0.products'))->contains(
            fn (array $menuProduct): bool => $menuProduct['name'] === 'Brownie no publicado'
        ));

        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('Postres')
            ->assertSee('Tres leches de prueba')
            ->assertDontSee('Brownie no publicado');

        $this->assertSame(7, $product->fresh()->stock);
        $this->assertSame(4, $otherProduct->fresh()->stock);
    }

    public function test_caja_custom_order_button_opens_the_custom_orders_module(): void
    {
        $this->setUpAdmin();

        $this->actingAs($this->admin)
            ->get(route('admin.caja.index'))
            ->assertOk()
            ->assertSee('href="'.route('admin.pedidos.personalizados.index').'"', false)
            ->assertSee('Pedidos personalizados')
            ->assertDontSee('customOrderModal')
            ->assertDontSee('btn-custom-order');
    }

    public function test_categories_are_managed_from_products_not_the_digital_menu(): void
    {
        $this->setUpAdmin();
        $existing = Category::create(['nombre' => 'Tortas', 'slug' => 'tortas']);

        $this->actingAs($this->admin)
            ->get(route('admin.menu-digital.index'))
            ->assertOk()
            ->assertDontSee('Añadir categoría')
            ->assertSee('Añadir menú');

        $this->actingAs($this->admin)
            ->post(route('admin.categorias.store'), [
                'nombre' => 'Galletas artesanales',
                'return_to' => 'productos',
            ])
            ->assertRedirect(route('admin.productos.index'));

        $newCategory = Category::query()->where('nombre', 'Galletas artesanales')->sole();
        $this->assertSame('galletas-artesanales', $newCategory->slug);
        $this->assertDatabaseHas('categories', ['id' => $existing->id, 'nombre' => 'Tortas']);

        $this->actingAs($this->admin)
            ->get(route('admin.productos.create'))
            ->assertOk()
            ->assertSee('Tortas')
            ->assertSee('Galletas artesanales');

        $this->actingAs($this->admin)
            ->post(route('admin.productos.store'), [
                'nombre' => 'Galleta de avena',
                'category_id' => $newCategory->id,
                'precio' => 8,
                'stock' => 10,
            ])
            ->assertRedirect(route('admin.productos.index'));

        $product = Product::query()->where('nombre', 'Galleta de avena')->sole();
        $this->assertSame($newCategory->id, $product->category_id);
    }

    public function test_category_creation_rejects_duplicate_names_and_invalid_return_targets(): void
    {
        $this->setUpAdmin();
        Category::create(['nombre' => 'Tortas', 'slug' => 'tortas']);

        $this->actingAs($this->admin)
            ->from(route('admin.productos.index'))
            ->post(route('admin.categorias.store'), [
                'nombre' => 'Tortas',
                'return_to' => 'productos',
            ])
            ->assertRedirect(route('admin.productos.index'))
            ->assertSessionHasErrors('nombre');

        $this->actingAs($this->admin)
            ->post(route('admin.categorias.store'), [
                'nombre' => 'Bebidas',
                'return_to' => 'https://example.test',
            ])
            ->assertSessionHasErrors('return_to');

        $this->assertDatabaseCount('categories', 1);
    }

    public function test_product_forms_and_category_manager_use_only_categories_saved_in_database(): void
    {
        $this->setUpAdmin();
        $category = Category::create(['nombre' => 'Tortas de prueba', 'slug' => 'tortas-de-prueba']);
        $product = $this->product([
            'nombre' => 'Producto categorizado',
            'category_id' => $category->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.productos.create'))
            ->assertOk()
            ->assertSee('<option value="'.$category->id.'"', false)
            ->assertSee('Tortas de prueba')
            ->assertDontSee('Postres')
            ->assertDontSee('Bebidas');

        $editResponse = $this->actingAs($this->admin)
            ->get(route('admin.productos.edit', $product))
            ->assertOk()
            ->assertSee('Tortas de prueba')
            ->assertDontSee('Postres')
            ->assertDontSee('Bebidas');
        $this->assertMatchesRegularExpression(
            '/<option value="'.$category->id.'"[^>]*selected/',
            $editResponse->getContent()
        );

        $this->actingAs($this->admin)
            ->get(route('admin.productos.index'))
            ->assertOk()
            ->assertSee('Categorías existentes')
            ->assertSee('Tortas de prueba')
            ->assertSee('value="1"', false)
            ->assertSee('class="btn btn-sm btn-outline-secondary" disabled', false)
            ->assertSee(route('admin.categorias.update', $category), false)
            ->assertSee(route('admin.categorias.destroy', $category), false);
    }

    public function test_category_can_be_renamed_and_only_empty_categories_can_be_deleted(): void
    {
        $this->setUpAdmin();
        $usedCategory = Category::create(['nombre' => 'Postres de prueba', 'slug' => 'postres-de-prueba']);
        $emptyCategory = Category::create(['nombre' => 'Categoría vacía', 'slug' => 'categoria-vacia']);
        $product = $this->product(['category_id' => $usedCategory->id]);

        $this->actingAs($this->admin)
            ->put(route('admin.categorias.update', $usedCategory), [
                'categoria_id' => $usedCategory->id,
                'nombre_categoria' => 'Postres especiales',
            ])
            ->assertRedirect(route('admin.productos.index'))
            ->assertSessionHas('success');

        $this->assertSame('Postres especiales', $usedCategory->fresh()->nombre);
        $this->assertSame('postres-especiales', $usedCategory->fresh()->slug);
        $this->assertSame($usedCategory->id, $product->fresh()->category_id);

        $this->actingAs($this->admin)
            ->delete(route('admin.categorias.destroy', $usedCategory))
            ->assertRedirect(route('admin.productos.index'))
            ->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $usedCategory->id]);
        $this->assertSame($usedCategory->id, $product->fresh()->category_id);

        $this->actingAs($this->admin)
            ->delete(route('admin.categorias.destroy', $emptyCategory))
            ->assertRedirect(route('admin.productos.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('categories', ['id' => $emptyCategory->id]);
    }

    public function test_products_require_a_category_that_exists_in_the_system(): void
    {
        $this->setUpAdmin();
        $category = Category::create(['nombre' => 'Categoría válida', 'slug' => 'categoria-valida']);
        $product = $this->product(['category_id' => $category->id]);

        $this->actingAs($this->admin)
            ->from(route('admin.productos.create'))
            ->post(route('admin.productos.store'), [
                'nombre' => 'Producto sin categoría',
                'precio' => 12,
                'stock' => 4,
            ])
            ->assertRedirect(route('admin.productos.create'))
            ->assertSessionHasErrors('category_id');

        $this->actingAs($this->admin)
            ->from(route('admin.productos.edit', $product))
            ->put(route('admin.productos.update', $product), [
                'nombre' => $product->nombre,
                'precio' => 12,
                'stock' => 4,
                'category_id' => 99999,
            ])
            ->assertRedirect(route('admin.productos.edit', $product))
            ->assertSessionHasErrors('category_id');

        $this->assertDatabaseCount('products', 1);
        $this->assertSame($category->id, $product->fresh()->category_id);
    }

    public function test_database_seeder_does_not_create_demo_categories_or_products(): void
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $this->assertDatabaseCount('categories', 0);
        $this->assertDatabaseCount('products', 0);
    }

    public function test_empty_categories_are_hidden_from_public_catalog_filters(): void
    {
        $this->product(['nombre' => 'Pastel visible']);
        Category::create(['nombre' => 'Galletas vacías', 'slug' => 'galletas-vacias']);
        $this->product([
            'nombre' => 'Destacado visible',
            'destacado' => true,
        ]);
        $this->product([
            'nombre' => 'Destacado oculto',
            'estado' => false,
            'destacado' => true,
        ]);

        $this->get(route('catalogo'))
            ->assertOk()
            ->assertSee('Tortas')
            ->assertDontSee('Galletas vacías');

        $this->get('/')
            ->assertOk()
            ->assertSee('Destacado visible')
            ->assertDontSee('Destacado oculto');
    }

    public function test_employee_without_digital_menu_permission_cannot_manage_menu(): void
    {
        $this->setUpAdmin();
        $role = Role::create(['name' => 'empleado-sin-menu']);
        $employee = User::factory()->create();
        $employee->assignRole($role);
        $product = $this->product();

        $this->actingAs($employee)
            ->get(route('admin.menu-digital.index'))
            ->assertForbidden();

        $this->actingAs($employee)
            ->patch(route('admin.menu-digital.toggle', $product))
            ->assertForbidden();

        $this->actingAs($employee)
            ->put(route('admin.menu-digital.selection'), ['product_ids' => [$product->id]])
            ->assertForbidden();
    }

    public function test_public_digital_menu_cannot_register_orders(): void
    {
        $product = $this->product(['precio' => 42.50]);

        $this->get(route('menu'))
            ->assertOk()
            ->assertDontSee('Añadir al carrito')
            ->assertDontSee('Finalizar pedido')
            ->assertDontSee('menu.pedido');

        $this->post('/menu/pedido', [
            'cliente_nombre' => 'Daniela',
            'cliente_telefono' => '70123456',
            'items' => [['id' => $product->id, 'cantidad' => 2]],
        ])->assertNotFound();

        $this->assertDatabaseCount('pedidos', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_caja_rejects_a_product_that_is_not_selected_for_the_digital_menu(): void
    {
        $this->setUpAdmin();
        $product = $this->product(['en_menu_digital' => false]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.caja.store'), [
                'cliente_nombre' => 'Daniela',
                'items' => [[
                    'id' => $product->id,
                    'nombre' => $product->nombre,
                    'precio' => $product->precio,
                    'cantidad' => 1,
                ]],
                'pago_tipo' => 'efectivo',
                'pago_recibido' => 30,
            ])
            ->assertUnprocessable()
            ->assertJson([
                'success' => false,
                'message' => 'El producto "Torta de frutilla" no está disponible en Caja.',
            ]);

        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_caja_can_sell_a_selected_product_even_when_catalog_visibility_is_off(): void
    {
        $this->setUpAdmin();
        $product = $this->product([
            'estado' => false,
            'en_menu_digital' => true,
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.caja.store'), [
                'cliente_nombre' => 'Daniela',
                'items' => [[
                    'id' => $product->id,
                    'nombre' => $product->nombre,
                    'precio' => $product->precio,
                    'cantidad' => 1,
                ]],
                'pago_tipo' => 'efectivo',
                'pago_recibido' => 30,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseCount('ventas', 1);
        $this->assertSame(4, $product->fresh()->stock);
    }

    public function test_custom_caja_sale_does_not_decrement_registered_product_stock(): void
    {
        $this->setUpAdmin();
        $product = $this->product(['stock' => 5]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.caja.store'), [
                'cliente_nombre' => 'Daniela',
                'items' => [[
                    'nombre' => 'Torta personalizada sin leche',
                    'precio' => 45,
                    'cantidad' => 1,
                    'note' => 'Preparación especial',
                ]],
                'pago_tipo' => 'efectivo',
                'pago_recibido' => 45,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('ventas', 1);
        $this->assertDatabaseHas('venta_items', [
            'product_id' => null,
            'nombre_producto' => 'Torta personalizada sin leche',
        ]);
    }

    public function test_catalog_and_featured_browsing_does_not_create_sales_or_change_stock(): void
    {
        $product = $this->product([
            'estado' => true,
            'destacado' => true,
            'stock' => 5,
        ]);

        $this->get(route('catalogo'))
            ->assertOk()
            ->assertSee($product->nombre);
        $this->get('/')
            ->assertOk()
            ->assertSee($product->nombre);

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('ventas', 0);
    }

    public function test_product_visibility_switch_controls_menu_and_caja_without_editing_the_product(): void
    {
        $this->setUpAdmin();
        $product = $this->product(['en_menu_digital' => false]);

        $this->actingAs($this->admin)
            ->patch(route('admin.productos.visibility', $product), [
                'campo' => 'en_menu_digital',
                'activo' => '1',
            ])
            ->assertRedirect(route('admin.productos.index'));

        $this->get(route('menu'))->assertSee($product->nombre);
        $this->get(route('admin.caja.index'))->assertSee($product->nombre);

        $this->actingAs($this->admin)
            ->patch(route('admin.productos.visibility', $product), [
                'campo' => 'en_menu_digital',
                'activo' => '0',
            ])
            ->assertRedirect(route('admin.productos.index'));

        $this->get(route('menu'))->assertDontSee($product->nombre);
        $this->get(route('admin.caja.index'))->assertDontSee($product->nombre);
        $this->assertSame(5, $product->fresh()->stock);
    }

    public function test_product_table_has_independent_accessible_visibility_switches(): void
    {
        $this->setUpAdmin();
        $product = $this->product([
            'nombre' => 'Producto independiente',
            'estado' => false,
            'destacado' => false,
            'en_menu_digital' => false,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.productos.index'))
            ->assertOk()
            ->assertSee('Catálogo')
            ->assertSee('Destacado')
            ->assertSee('Menú digital')
            ->assertSee('role="switch"', false)
            ->assertSee('Mostrar Producto independiente en Catálogo')
            ->assertDontSee('name="estado"', false)
            ->assertDontSee('name="destacado"', false)
            ->assertDontSee('name="en_menu_digital"', false);

        $this->actingAs($this->admin)
            ->patch(route('admin.productos.visibility', $product), [
                'campo' => 'estado',
                'activo' => '1',
            ])
            ->assertRedirect(route('admin.productos.index'));

        $this->actingAs($this->admin)
            ->patch(route('admin.productos.visibility', $product), [
                'campo' => 'destacado',
                'activo' => '1',
            ])
            ->assertRedirect(route('admin.productos.index'));

        $this->actingAs($this->admin)
            ->patch(route('admin.productos.visibility', $product), [
                'campo' => 'en_menu_digital',
                'activo' => '1',
            ])
            ->assertRedirect(route('admin.productos.index'));

        $this->assertTrue($product->fresh()->estado);
        $this->assertTrue($product->fresh()->destacado);
        $this->assertTrue($product->fresh()->en_menu_digital);
        $this->get(route('catalogo'))->assertSee($product->nombre);
        $this->get('/')->assertSee($product->nombre);
    }

    public function test_new_products_start_hidden_and_visibility_is_not_set_through_create_form(): void
    {
        $this->setUpAdmin();
        $category = Category::create(['nombre' => 'Tortas', 'slug' => 'tortas']);

        $this->actingAs($this->admin)
            ->get(route('admin.productos.create'))
            ->assertOk()
            ->assertDontSee('name="estado"', false)
            ->assertDontSee('name="destacado"', false)
            ->assertDontSee('name="en_menu_digital"', false);

        $this->actingAs($this->admin)
            ->post(route('admin.productos.store'), [
                'nombre' => 'Solo menú digital',
                'precio' => 20,
                'stock' => 3,
                'category_id' => $category->id,
                'estado' => '1',
                'destacado' => '1',
                'en_menu_digital' => '1',
            ])
            ->assertRedirect(route('admin.productos.index'));

        $product = Product::query()->where('nombre', 'Solo menú digital')->sole();
        $this->assertFalse($product->estado);
        $this->assertFalse($product->destacado);
        $this->assertFalse($product->en_menu_digital);
        $this->get(route('menu'))->assertDontSee($product->nombre);
        $this->get(route('admin.caja.index'))->assertDontSee($product->nombre);
        $this->get(route('catalogo'))->assertDontSee($product->nombre);
        $this->get('/')->assertDontSee($product->nombre);
    }

    public function test_edit_form_hides_visibility_options_and_updates_preserve_their_values(): void
    {
        $this->setUpAdmin();
        $product = $this->product([
            'estado' => true,
            'destacado' => true,
            'en_menu_digital' => true,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.productos.edit', $product))
            ->assertOk()
            ->assertDontSee('name="estado"', false)
            ->assertDontSee('name="destacado"', false)
            ->assertDontSee('name="en_menu_digital"', false);

        $this->actingAs($this->admin)
            ->put(route('admin.productos.update', $product), [
                'nombre' => $product->nombre,
                'descripcion' => 'Descripción editada',
                'precio' => $product->precio,
                'stock' => $product->stock,
                'category_id' => $product->category_id,
            ])
            ->assertRedirect(route('admin.productos.index'));

        $this->assertTrue($product->fresh()->estado);
        $this->assertTrue($product->fresh()->destacado);
        $this->assertTrue($product->fresh()->en_menu_digital);
        $this->assertSame('Descripción editada', $product->fresh()->descripcion);
    }

    public function test_visibility_endpoint_rejects_unknown_fields_and_invalid_values(): void
    {
        $this->setUpAdmin();
        $product = $this->product();

        $this->actingAs($this->admin)
            ->patch(route('admin.productos.visibility', $product), [
                'campo' => 'stock',
                'activo' => '0',
            ])
            ->assertSessionHasErrors('campo');

        $this->actingAs($this->admin)
            ->patch(route('admin.productos.visibility', $product), [
                'campo' => 'destacado',
                'activo' => 'maybe',
            ])
            ->assertSessionHasErrors('activo');

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertFalse($product->fresh()->destacado);
    }

    public function test_stock_can_be_added_from_the_product_table_without_replacing_existing_units(): void
    {
        $this->setUpAdmin();
        $product = $this->product(['stock' => 10]);

        $this->actingAs($this->admin)
            ->get(route('admin.productos.index'))
            ->assertOk()
            ->assertSee('Añadir stock')
            ->assertSee('data-stock-current="10"', false);

        $this->actingAs($this->admin)
            ->post(route('admin.productos.stock', $product), ['cantidad' => 8])
            ->assertRedirect(route('admin.productos.index'))
            ->assertSessionHas('success', 'Se añadieron 8 unidades a “Torta de frutilla”. Stock actual: 18.');

        $this->assertSame(18, $product->fresh()->stock);
    }

    public function test_stock_addition_rejects_invalid_quantity_without_changing_stock(): void
    {
        $this->setUpAdmin();
        $product = $this->product(['stock' => 10]);

        foreach ([0, -2, '1.5', 'not-a-number'] as $quantity) {
            $this->actingAs($this->admin)
                ->post(route('admin.productos.stock', $product), ['cantidad' => $quantity])
                ->assertRedirect(route('admin.productos.index'))
                ->assertSessionHasErrors('cantidad')
                ->assertSessionHas('stock_product_id', $product->id);
        }

        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_stock_addition_is_authorized_and_cannot_overflow_the_inventory_column(): void
    {
        $this->setUpAdmin();
        $product = $this->product(['stock' => 2147483640]);

        $this->actingAs(User::factory()->create())
            ->post(route('admin.productos.stock', $product), ['cantidad' => 1])
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->post(route('admin.productos.stock', $product), ['cantidad' => 8])
            ->assertRedirect(route('admin.productos.index'))
            ->assertSessionHasErrors('cantidad');

        $this->assertSame(2147483640, $product->fresh()->stock);
    }

    public function test_sales_table_migration_preserves_sales_and_removes_recipe_data(): void
    {
        $this->setUpAdmin();
        $sale = Order::create([
            'user_id' => $this->admin->id,
            'cliente_nombre' => 'Venta existente',
            'total' => 12,
            'total_final' => 12,
            'pago_tipo' => 'efectivo',
            'estado' => Order::ESTADO_COMPLETADO,
        ]);
        OrderItem::create([
            'order_id' => $sale->id,
            'nombre_producto' => 'Producto existente',
            'precio' => 12,
            'cantidad' => 1,
        ]);

        $migration = require database_path('migrations/2026_11_11_120000_rename_sales_tables_and_drop_saved_recipes.php');
        $migration->down();
        \Illuminate\Support\Facades\DB::table('recetas_guardadas')->insert([
            'meal_id' => 'test-meal',
            'nombre' => 'Receta que se elimina',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $migration->up();

        $this->assertDatabaseHas('ventas', ['id' => $sale->id, 'cliente_nombre' => 'Venta existente']);
        $this->assertDatabaseHas('venta_items', ['order_id' => $sale->id, 'nombre_producto' => 'Producto existente']);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('recetas_guardadas'));
    }

    public function test_sales_schema_uses_spanish_tables_and_saved_recipes_table_is_removed(): void
    {
        $this->assertDatabaseCount('ventas', 0);
        $this->assertDatabaseCount('venta_items', 0);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('orders'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('order_items'));
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasTable('recetas_guardadas'));
    }

    public function test_admin_layout_shows_global_toast_and_confirmation_dialog(): void
    {
        $this->setUpAdmin();
        $this->product();

        $this->actingAs($this->admin)
            ->withSession(['success' => 'Producto guardado correctamente.'])
            ->get(route('admin.productos.index'))
            ->assertOk()
            ->assertSee('Producto guardado correctamente.')
            ->assertSee('app-feedback-toast')
            ->assertSee('app-confirm-backdrop')
            ->assertSee('data-confirm-message="¿Estás seguro de que deseas eliminar este producto?"', false)
            ->assertDontSee('alert-success');
    }

    public function test_guest_layout_shows_flash_notifications(): void
    {
        $this->withSession(['status' => 'verification-link-sent'])
            ->get(route('login'))
            ->assertOk()
            ->assertSee('Se envió un nuevo enlace de verificación a tu correo.')
            ->assertSee('app-feedback-toast');
    }

    public function test_product_editor_updates_the_price_shown_on_the_public_menu(): void
    {
        $this->setUpAdmin();
        $product = $this->product();

        $this->actingAs($this->admin)
            ->put(route('admin.productos.update', $product), [
                'nombre' => $product->nombre,
                'descripcion' => $product->descripcion,
                'precio' => '57.90',
                'stock' => $product->stock,
                'category_id' => $product->category_id,
                'estado' => '1',
                'en_menu_digital' => '1',
            ])
            ->assertRedirect(route('admin.productos.index'));

        $this->assertSame('57.90', $product->fresh()->precio);
        $this->get(route('menu'))
            ->assertOk()
            ->assertSee('57,90');
    }
}
