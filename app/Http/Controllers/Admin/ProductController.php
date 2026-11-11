<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function catalogoAdmin()
    {
        $this->checkPermission('admin.catalogo.ver');
        $todosProductos = Product::with('category')
            ->orderBy('category_id')
            ->orderBy('nombre')
            ->get();
        $productos = $todosProductos->where('estado', true);

        return view('admin.catalogo.index', [
            'productos' => $productos,
            'todosProductos' => $todosProductos,
        ]);
    }

    public function updateCatalogSelection(Request $request)
    {
        $this->checkPermission('admin.catalogo.ver');

        $data = $request->validate([
            'product_ids' => ['sometimes', 'array'],
            'product_ids.*' => ['required', 'integer', 'distinct', 'exists:products,id'],
        ]);
        $productIds = array_map('intval', $data['product_ids'] ?? []);

        DB::transaction(function () use ($productIds): void {
            Product::query()
                ->where('estado', true)
                ->whereNotIn('id', $productIds)
                ->update(['estado' => false]);

            if ($productIds !== []) {
                Product::whereIn('id', $productIds)->update(['estado' => true]);
            }
        });

        return redirect()->route('admin.catalogo.index')
            ->with('success', 'Catálogo actualizado. Las existencias no fueron modificadas.');
    }

    public function index()
    {
        $this->checkPermission('admin.productos.ver');
        $productos = Product::with('category')->orderBy('id')->get();
        $categorias = Category::withCount('products')->orderBy('nombre')->get();

        return view('admin.productos.index', compact('productos', 'categorias'));
    }

    public function menuDigital()
    {
        // Filtramos solo los productos que están marcados para el menú digital
        $productos = Product::with('category')->where('en_menu_digital', true)->get();

        return view('admin.menu-digital.index', compact('productos'));
    }

    public function create()
    {
        $this->checkPermission('admin.productos.ver');
        $categorias = Category::orderBy('nombre')->get();

        return view('admin.productos.create', compact('categorias'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('admin.productos.ver');
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        $data['estado'] = false;
        $data['destacado'] = false;
        $data['en_menu_digital'] = false;

        if ($request->hasFile('imagen')) {
            $path = $request->file('imagen')->store('productos', 'public');
            $data['imagen'] = 'storage/'.$path;
        }

        Product::create($data);

        return redirect()->route('admin.productos.index')->with('success', 'Producto creado exitosamente.');
    }

    public function edit(Product $producto)
    {
        $this->checkPermission('admin.productos.ver');
        $categorias = Category::orderBy('nombre')->get();

        return view('admin.productos.edit', compact('producto', 'categorias'));
    }

    public function update(Request $request, Product $producto)
    {
        $this->checkPermission('admin.productos.ver');
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:5000'],
            'precio' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'stock' => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'imagen' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ]);

        if ($request->hasFile('imagen')) {
            // Eliminar imagen anterior si existe localmente
            if ($producto->imagen && file_exists(public_path($producto->imagen))) {
                @unlink(public_path($producto->imagen));
            }
            $path = $request->file('imagen')->store('productos', 'public');
            $data['imagen'] = 'storage/'.$path;
        }

        $producto->update($data);

        return redirect()->route('admin.productos.index')->with('success', 'Producto actualizado correctamente.');
    }

    public function updateVisibility(Request $request, Product $producto)
    {
        $this->checkPermission('admin.productos.ver');

        $data = $request->validate([
            'campo' => ['required', 'in:estado,destacado,en_menu_digital'],
            'activo' => ['required', 'boolean'],
        ]);

        $field = $data['campo'];
        $producto->update([$field => $data['activo']]);

        $label = match ($field) {
            'estado' => 'catálogo',
            'destacado' => 'destacados',
            'en_menu_digital' => 'menú digital',
        };

        return redirect()->route('admin.productos.index')->with(
            'success',
            '“'.$producto->nombre.'” '.($data['activo'] ? 'se muestra en '.$label.'.' : 'se ocultó de '.$label.'.')
        );
    }

    public function addStock(Request $request, Product $producto)
    {
        $this->checkPermission('admin.productos.ver');

        $validator = Validator::make($request->all(), [
            'cantidad' => ['required', 'integer', 'min:1', 'max:2147483647'],
        ], [
            'cantidad.required' => 'Indica cuántas unidades deseas añadir.',
            'cantidad.integer' => 'La cantidad debe ser un número entero.',
            'cantidad.min' => 'Debes añadir al menos una unidad.',
            'cantidad.max' => 'La cantidad ingresada supera el límite permitido.',
        ]);

        if ($validator->fails()) {
            return redirect()->route('admin.productos.index')
                ->withErrors($validator)
                ->withInput()
                ->with('stock_product_id', $producto->id);
        }

        $cantidad = (int) $validator->validated()['cantidad'];
        $stockActualizado = DB::transaction(function () use ($producto, $cantidad): ?int {
            $productoBloqueado = Product::query()
                ->lockForUpdate()
                ->findOrFail($producto->id);

            if ($cantidad > 2147483647 - (int) $productoBloqueado->stock) {
                return null;
            }

            $productoBloqueado->increment('stock', $cantidad);

            return (int) $productoBloqueado->stock;
        });

        if ($stockActualizado === null) {
            return redirect()->route('admin.productos.index')
                ->withErrors(['cantidad' => 'La suma supera el máximo de stock permitido.'])
                ->withInput()
                ->with('stock_product_id', $producto->id);
        }

        return redirect()->route('admin.productos.index')->with(
            'success',
            'Se añadieron '.$cantidad.' unidades a “'.$producto->nombre.'”. Stock actual: '.$stockActualizado.'.'
        );
    }

    public function destroy(Product $producto)
    {
        $this->checkPermission('admin.productos.ver');
        // Eliminar imagen física si existe
        if ($producto->imagen && file_exists(public_path($producto->imagen))) {
            @unlink(public_path($producto->imagen));
        }

        $producto->delete();

        return redirect()->route('admin.productos.index')->with('success', 'Producto eliminado correctamente.');
    }

    private function checkPermission(string $permission): void
    {
        abort_unless(auth()->user()?->can($permission) ?? false, 403, 'No tienes permiso para gestionar productos.');
    }
}
