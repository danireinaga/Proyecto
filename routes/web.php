<?php

use App\Http\Controllers\Admin\AccountSettingsController;
use App\Http\Controllers\Admin\CajaController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MenuDigitalController;
use App\Http\Controllers\Admin\PedidoController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ReportesController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VentasController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuPublicController;
use App\Http\Controllers\ProductImageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ZONA PÚBLICA (Páginas principales de la tienda)
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index']);
Route::get('/catalogo', [CatalogController::class, 'index'])->name('catalogo');
Route::get('/menu', [MenuPublicController::class, 'index'])->name('menu');
Route::get('/menu/datos', [MenuPublicController::class, 'datosMenu'])->name('menu.datos');
Route::get('/storage/productos/{filename}', ProductImageController::class)
    ->where('filename', '[A-Za-z0-9][A-Za-z0-9_-]*\.(?:jpg|jpeg|png|webp)')
    ->name('product-images.show');

/*
|--------------------------------------------------------------------------
| AUTENTICACIÓN (Laravel Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';

/*
|--------------------------------------------------------------------------
| ZONA PRIVADA (Panel Administrativo AdminLTE y Módulos de Gestión)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // --- Reportes --------------------------------------------------------
    Route::prefix('admin/reportes')->name('admin.reportes.')->group(function () {
        Route::get('/', [ReportesController::class, 'index'])->name('index');
        Route::get('/excel', [ReportesController::class, 'excel'])->name('excel');
        Route::get('/imprimir', [ReportesController::class, 'imprimir'])->name('imprimir');
    });

    Route::get('/admin/configuracion', [AccountSettingsController::class, 'edit'])
        ->name('admin.configuracion.edit');
    Route::put('/admin/configuracion/contrasena', [AccountSettingsController::class, 'updatePassword'])
        ->name('admin.configuracion.password');
    // --- Catálogo y productos -------------------------------------------
    Route::post('/admin/categorias', [CategoryController::class, 'store'])->name('admin.categorias.store');
    Route::put('/admin/categorias/{categoria}', [CategoryController::class, 'update'])->name('admin.categorias.update');
    Route::delete('/admin/categorias/{categoria}', [CategoryController::class, 'destroy'])->name('admin.categorias.destroy');
    Route::get('/admin/catalogo', [ProductController::class, 'catalogoAdmin'])->name('admin.catalogo.index');
    Route::put('/admin/catalogo', [ProductController::class, 'updateCatalogSelection'])
        ->name('admin.catalogo.selection');
    Route::resource('admin/productos', ProductController::class)
        ->except(['show'])
        ->names('admin.productos');
    Route::patch('admin/productos/{producto}/visibilidad', [ProductController::class, 'updateVisibility'])
        ->name('admin.productos.visibility');
    Route::post('admin/productos/{producto}/stock', [ProductController::class, 'addStock'])
        ->name('admin.productos.stock');

    // --- Usuarios y roles ------------------------------------------------
    Route::resource('admin/usuarios', UserController::class)
        ->except(['show'])
        ->names('admin.usuarios');
    Route::patch('admin/usuarios/{usuario}/restablecer-contrasena', [UserController::class, 'resetPassword'])
        ->name('admin.usuarios.reset-password');
    Route::get('admin/usuarios/{usuario}/restablecer-contrasena', [UserController::class, 'editPasswordReset'])
        ->name('admin.usuarios.reset-password.edit');
    Route::resource('admin/roles', RoleController::class)
        ->except(['show', 'destroy'])
        ->names('admin.roles');
    Route::patch('admin/roles/{role}/estado', [RoleController::class, 'toggleStatus'])
        ->name('admin.roles.status');

    // --- Menú digital (gestión del administrador) -----------------------
    Route::get('admin/menu-digital', [MenuDigitalController::class, 'index'])->name('admin.menu-digital.index');
    Route::put('admin/menu-digital', [MenuDigitalController::class, 'updateSelection'])
        ->name('admin.menu-digital.selection');
    Route::patch('admin/menu-digital/toggle/{producto}', [MenuDigitalController::class, 'toggle'])
        ->name('admin.menu-digital.toggle');
    Route::patch('admin/menu-digital/orden/{producto}', [MenuDigitalController::class, 'reorder'])
        ->name('admin.menu-digital.reorder');

    // --- Caja / POS (rutas coherentes bajo /admin/caja) -----------------
    Route::prefix('admin/caja')->name('admin.caja.')->group(function () {
        Route::get('/', [CajaController::class, 'index'])->name('index');
        Route::post('/store', [CajaController::class, 'store'])->name('store');
        Route::get('/ficha/{orden}', [CajaController::class, 'ficha'])->name('ficha');
        Route::get('/mis-ventas', [CajaController::class, 'misVentas'])->name('mis-ventas');
        Route::post('/anular/{orden}', [CajaController::class, 'anular'])->name('anular');
    });

    // Redirecciones de las URLs antiguas (para no romper enlaces guardados)
    Route::redirect('/caja/mis-ventas', '/admin/caja/mis-ventas')->name('caja.mis-ventas.legacy');

    // --- Pedidos (clientes con fecha de entrega) -------------------------
    Route::prefix('admin/pedidos')->name('admin.pedidos.')->group(function () {
        Route::get('/personalizados', [PedidoController::class, 'personalizados'])
            ->name('personalizados.index');
        Route::get('/personalizados/crear', [PedidoController::class, 'create'])
            ->name('personalizados.create');
        Route::post('/personalizados', [PedidoController::class, 'store'])
            ->name('personalizados.store');
        Route::get('/', [PedidoController::class, 'index'])->name('index');
        Route::get('/crear', [PedidoController::class, 'create'])->name('create');
        Route::post('/', [PedidoController::class, 'store'])->name('store');
        Route::get('/{pedido}/imagen-referencia', [PedidoController::class, 'imagenReferencia'])
            ->name('imagen-referencia');
        Route::get('/{pedido}', [PedidoController::class, 'show'])->name('show');
        Route::get('/{pedido}/editar', [PedidoController::class, 'edit'])->name('edit');
        Route::put('/{pedido}', [PedidoController::class, 'update'])->name('update');
        Route::patch('/{pedido}/estado', [PedidoController::class, 'cambiarEstado'])->name('estado');
        Route::patch('/{pedido}/cancelar', [PedidoController::class, 'cancel'])->name('cancel');
    });

    // --- Ventas (todo lo vendido por el administrador y los empleados) ----
    Route::prefix('admin/ventas')->name('admin.ventas.')->group(function () {
        Route::get('/', [VentasController::class, 'index'])->name('index');
    });

    // --- Calendario ------------------------------------------------------
    Route::prefix('admin/calendar')->name('admin.calendar.')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::get('/events', [CalendarController::class, 'getEvents'])->name('events');
        Route::get('/pedidos/{fecha}', [CalendarController::class, 'pedidosDelDia'])->name('pedidos-dia');
        Route::put('/eventos/{pedido}', [CalendarController::class, 'actualizarEvento'])->name('update');
        Route::delete('/eventos/{pedido}', [CalendarController::class, 'eliminarEvento'])->name('destroy');
    });

    
});
