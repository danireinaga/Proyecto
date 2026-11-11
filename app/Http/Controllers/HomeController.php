<?php

namespace App\Http\Controllers;

use App\Models\Product; // Importa tu modelo de productos

class HomeController extends Controller
{
    /**
     * Show the main public website.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Trae los productos destacados para que funcionen en tu index.blade.php
        $destacados = Product::where('destacado', 1)
            ->where('estado', true)
            ->get();

        return view('index', compact('destacados'));
    }
}