<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    // Método para la página principal (Index) que muestra los 3 destacados
    public function home()
    {
        $destacados = Product::where('destacado', true)
                             ->where('estado', true)
                             ->orderBy('orden_destacado', 'asc')
                             ->take(3)
                             ->get();

        return view('index', compact('destacados'));
    }

    // Método para el catálogo completo con filtros y buscador
    public function index(Request $request)
    {
        $busqueda = $request->input('q');
        $categoriaId = $request->input('categoria');

        $query = Product::with('category')->where('estado', true);

        if (!empty($busqueda)) {
            $query->where(function($q) use ($busqueda) {
                $q->where('nombre', 'LIKE', "%{$busqueda}%")
                  ->orWhere('descripcion', 'LIKE', "%{$busqueda}%");
            });
        }

        if (!empty($categoriaId) && $categoriaId !== 'todos') {
            $query->where('category_id', $categoriaId);
        }

        $productos = $query->get();
        $categorias = Category::query()
            ->whereHas('products', fn ($query) => $query->where('estado', true))
            ->orderBy('nombre')
            ->get();

        return view('catalogo', compact('productos', 'categorias', 'busqueda', 'categoriaId'));
    }
}