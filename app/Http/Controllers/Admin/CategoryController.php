<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{
    public function store(Request $request)
    {
        abort_unless(
            $request->user()?->can('admin.productos.ver')
                || $request->user()?->can('admin.menu-digital.ver'),
            403,
            'No tienes permiso para gestionar categorías.'
        );

        if (is_string($request->input('nombre'))) {
            $request->merge(['nombre' => trim($request->input('nombre'))]);
        }

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('categories', 'nombre')],
            'return_to' => ['required', Rule::in(['productos', 'menu-digital'])],
        ]);

        $slug = Str::slug($data['nombre']);
        if ($slug === '') {
            throw ValidationException::withMessages([
                'nombre' => 'Escribe un nombre que permita generar la dirección de la categoría.',
            ]);
        }

        $baseSlug = $slug;
        $suffix = 2;

        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        Category::create([
            'nombre' => trim($data['nombre']),
            'slug' => $slug,
        ]);

        $route = $data['return_to'] === 'menu-digital'
            ? 'admin.menu-digital.index'
            : 'admin.productos.index';

        return redirect()->route($route)->with('success', 'La categoría se creó correctamente.');
    }

    public function update(Request $request, Category $categoria)
    {
        $this->authorizeCategoryManagement($request);

        if (is_string($request->input('nombre_categoria'))) {
            $request->merge(['nombre_categoria' => trim($request->input('nombre_categoria'))]);
        }

        $data = $request->validate([
            'categoria_id' => ['required', 'integer', Rule::in([$categoria->id])],
            'nombre_categoria' => [
                'required',
                'string',
                'max:120',
                Rule::unique('categories', 'nombre')->ignore($categoria->id),
            ],
        ]);

        $slug = $this->uniqueSlug($data['nombre_categoria'], $categoria);
        $categoria->update([
            'nombre' => $data['nombre_categoria'],
            'slug' => $slug,
        ]);

        return redirect()->route('admin.productos.index')->with('success', 'La categoría se actualizó correctamente.');
    }

    public function destroy(Request $request, Category $categoria)
    {
        $this->authorizeCategoryManagement($request);

        if ($categoria->products()->exists()) {
            return redirect()->route('admin.productos.index')->with(
                'error',
                'No se puede eliminar “'.$categoria->nombre.'” porque tiene productos asociados. Cambia primero esos productos a otra categoría.'
            );
        }

        $categoria->delete();

        return redirect()->route('admin.productos.index')->with('success', 'La categoría se eliminó correctamente.');
    }

    private function authorizeCategoryManagement(Request $request): void
    {
        abort_unless(
            $request->user()?->can('admin.productos.ver')
                || $request->user()?->can('admin.menu-digital.ver'),
            403,
            'No tienes permiso para gestionar categorías.'
        );
    }

    private function uniqueSlug(string $name, ?Category $ignore = null): string
    {
        $slug = Str::slug($name);
        if ($slug === '') {
            throw ValidationException::withMessages([
                'nombre_categoria' => 'Escribe un nombre que permita generar la dirección de la categoría.',
            ]);
        }

        $baseSlug = $slug;
        $suffix = 2;
        while (
            Category::query()
                ->where('slug', $slug)
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
