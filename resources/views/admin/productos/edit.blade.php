@extends('adminlte::page')

@section('title', 'Editar Producto')

@section('content_header')
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Editar Producto</h1>
            </div>
            <div class="col-sm-6 text-end">
                <a href="{{ route('admin.productos.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="card card-warning card-outline">
            <form action="{{ route('admin.productos.update', $producto->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre del Producto</label>
                        <input type="text" name="nombre" class="form-control @error('nombre') is-invalid @enderror"
                            value="{{ old('nombre', $producto->nombre) }}" required>
                        @error('nombre')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="category_id" class="form-label">Categoría</label>
                        <select name="category_id" class="form-control @error('category_id') is-invalid @enderror" required>
                            <option value="" disabled {{ old('category_id', $producto->category_id) ? '' : 'selected' }}>Seleccione una categoría</option>
                            @foreach ($categorias as $cat)
                                <option value="{{ $cat->id }}"
                                    {{ old('category_id', $producto->category_id) == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                        @error('category_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="precio" class="form-label">Precio (Bs.)</label>
                        <input type="number" step="0.01" name="precio"
                            class="form-control @error('precio') is-invalid @enderror"
                            value="{{ old('precio', $producto->precio) }}" required>
                        @error('precio')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="stock" class="form-label">Stock / Cantidad Disponible</label>
                        <input type="number" name="stock" class="form-control @error('stock') is-invalid @enderror"
                            value="{{ old('stock', $producto->stock) }}" min="0" required>
                        @error('stock')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3">{{ old('descripcion', $producto->descripcion) }}</textarea>
                    </div>

                    <div class="mb-3">
                        <label for="imagen" class="form-label">Imagen Actual</label>
                        @if ($producto->imagen)
                            <div class="mb-2">
                                <img src="{{ asset($producto->imagen) }}" alt="{{ $producto->nombre }}" width="100"
                                    class="img-thumbnail">
                            </div>
                        @else
                            <p class="text-muted">Sin imagen registrada.</p>
                        @endif
                        <input type="file" name="imagen" class="form-control mt-2">
                        <small class="text-muted">Sube una nueva solo si deseas reemplazar la anterior.</small>
                    </div>

                </div>
                <div class="card-footer">
                    <button type="submit" class="btn btn-warning text-white"><i class="fas fa-save"></i> Actualizar
                        Producto</button>
                </div>
            </form>
        </div>
    </div>
@endsection
