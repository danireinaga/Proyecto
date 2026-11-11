<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;

class MenuPublicController extends Controller
{
    /** Menú digital público: el que ve el cliente desde el celular. */
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'categoria' => ['nullable', 'integer', 'exists:categories,id'],
            'pantalla' => ['nullable', 'boolean'],
        ]);
        $productos = Product::with('category')
            ->enMenuDigital()
            ->buscar($filters['q'] ?? null)
            ->when(isset($filters['categoria']), fn ($q) => $q->where('category_id', $filters['categoria']))
            ->orderBy('category_id')
            ->orderByRaw('orden_destacado IS NULL, orden_destacado')
            ->orderBy('nombre')
            ->get();

        $categorias = Category::query()
            ->whereHas('products', fn ($query) => $query->enMenuDigital())
            ->orderBy('nombre')
            ->get();

        return view('menu-digital', [
            'productos' => $productos,
            'categorias' => $categorias,
            'categoriaId' => $filters['categoria'] ?? null,
            'busqueda' => $filters['q'] ?? '',
            'modoPantalla' => filter_var($filters['pantalla'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'menuSlides' => $this->menuSlides($productos),
        ]);
    }

    /** Public data feed keeps an already-open display in sync with product edits. */
    public function datosMenu()
    {
        $products = Product::with('category')
            ->enMenuDigital()
            ->orderBy('category_id')
            ->orderByRaw('orden_destacado IS NULL, orden_destacado')
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'slides' => $this->menuSlides($products),
        ])->header('Cache-Control', 'no-store, private');
    }

    /**
     * Prepare real menu products as a featured collection and category scenes.
     *
     * @return array<int, array{key: string, label: string, icon: string, products: array<int, array<string, mixed>>}>
     */
    private function menuSlides(Collection $products): array
    {
        $slides = [];
        $toMenuItem = fn (Product $product): array => [
            'id' => $product->id,
            'name' => $product->nombre,
            'description' => $product->descripcion,
            'category' => $product->category?->nombre ?? 'Especialidades',
            'price' => (float) $product->precio,
            'image' => $product->imagen ? asset($product->imagen) : null,
            'featured' => $product->destacado,
        ];

        $featuredProducts = $products->where('destacado', true)->values();
        if ($featuredProducts->isNotEmpty()) {
            $slides[] = [
                'key' => 'featured',
                'label' => 'Selección destacada',
                'icon' => 'stars',
                'products' => $featuredProducts->map($toMenuItem)->all(),
            ];
        }

        foreach ($products->groupBy('category_id') as $categoryId => $categoryProducts) {
            $category = $categoryProducts->first()->category;
            $slides[] = [
                'key' => 'category-'.$categoryId,
                'label' => $category?->nombre ?? 'Especialidades',
                'icon' => 'bakery_dining',
                'products' => $categoryProducts->map($toMenuItem)->all(),
            ];
        }

        return $slides;
    }
}
