<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuDigitalController extends Controller
{
    public function index()
    {
        $this->checkPermission('admin.menu-digital.ver');

        $ordenarProductos = static fn (Builder $query): Builder => $query
            ->orderBy('category_id')
            ->orderByRaw('orden_destacado IS NULL, orden_destacado')
            ->orderBy('nombre');

        $productos = $ordenarProductos(
            Product::with('category')->enMenuDigital()
        )->get()->groupBy('category_id');

        $todosProductos = $ordenarProductos(
            Product::with('category')
        )->get();

        return view('admin.menu-digital.index', [
            'productos' => $productos,
            'todosProductos' => $todosProductos,
            'publicMenuUrl' => route('menu', ['pantalla' => 1]),
            'customerMenuUrl' => 'http://192.168.137.1:8000/menu',
        ]);
    }

    /** Activa o desactiva un producto del menú digital desde la tabla. */
    public function toggle(Product $producto)
    {
        $this->checkPermission('admin.menu-digital.ver');

        $publicar = ! $producto->en_menu_digital;
        $producto->update(['en_menu_digital' => $publicar]);

        return back()->with(
            'success',
            $producto->en_menu_digital
                ? '“'.$producto->nombre.'” ya está disponible en el menú digital y en Caja.'
                : '“'.$producto->nombre.'” se quitó del menú digital y de Caja.'
        );
    }

    /** Guarda la selección completa de productos visibles en el menú y en Caja. */
    public function updateSelection(Request $request)
    {
        $this->checkPermission('admin.menu-digital.ver');

        $data = $request->validate([
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
        ]);
        $productIds = array_map('intval', $data['product_ids'] ?? []);

        DB::transaction(function () use ($productIds): void {
            Product::query()
                ->where('en_menu_digital', true)
                ->whereNotIn('id', $productIds)
                ->update(['en_menu_digital' => false]);

            if ($productIds !== []) {
                Product::whereIn('id', $productIds)->update(['en_menu_digital' => true]);
            }
        });

        return back()->with('success', 'Menú actualizado. Las existencias no fueron modificadas.');
    }

    /** Sube o baja el producto dentro del orden del menú. */
    public function reorder(Request $request, Product $producto)
    {
        $this->checkPermission('admin.menu-digital.ver');

        $data = $request->validate([
            'direccion' => ['required', 'in:arriba,abajo'],
        ]);

        $base = Product::enMenuDigital()
            ->where('category_id', $producto->category_id)
            ->orderByRaw('orden_destacado IS NULL, orden_destacado')
            ->orderBy('nombre');

        $ordenados = $base->pluck('id')->all();
        $posicion = array_search($producto->id, $ordenados, true);

        if ($posicion === false) {
            return back()->with('info', 'Activa el producto en el menú antes de ordenarlo.');
        }

        $destino = $data['direccion'] === 'arriba' ? $posicion - 1 : $posicion + 1;

        if ($destino < 0 || $destino >= count($ordenados)) {
            return back()->with('info', 'El producto ya está en el extremo de la lista.');
        }

        // Intercambio de posiciones en el array y se reescribe el orden
        [$ordenados[$posicion], $ordenados[$destino]] = [$ordenados[$destino], $ordenados[$posicion]];

        DB::transaction(function () use ($ordenados): void {
            foreach ($ordenados as $indice => $id) {
                Product::whereKey($id)->update(['orden_destacado' => $indice + 1]);
            }
        });

        return back()->with('success', 'Orden del menú actualizado.');
    }

    private function checkPermission(string $permiso): void
    {
        abort_unless(auth()->user()?->can($permiso) ?? false, 403, 'No tiene permiso para esta acción.');
    }
}
