<div class="modal fade" id="createCategoryModal" tabindex="-1" role="dialog" aria-labelledby="createCategoryModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title font-weight-bold" id="createCategoryModalLabel">Añadir categoría</h5>
                    <p class="text-muted mb-0">Revisa las categorías guardadas y administra sus nombres.</p>
                </div>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('admin.categorias.store') }}" class="category-create-form">
                    @csrf
                    <input type="hidden" name="return_to" value="{{ $returnTo }}">
                    <div class="form-group">
                        <label for="category-name">Crear una categoría</label>
                        <div class="input-group">
                            <input type="text" name="nombre" id="category-name"
                                class="form-control @error('nombre') is-invalid @enderror"
                                value="{{ old('nombre') }}" maxlength="120" placeholder="Ej.: Galletas" required>
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus mr-1"></i> Añadir
                                </button>
                            </div>
                        </div>
                        @error('nombre')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </form>

                @error('nombre_categoria')
                    <div class="alert alert-danger">{{ $message }}</div>
                @enderror
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <h6 class="font-weight-bold mt-4 mb-2">Categorías existentes</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-bordered category-management-table">
                        <thead class="thead-light">
                            <tr>
                                <th>Categoría</th>
                                <th class="text-center">Productos</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($categorias as $categoria)
                                <tr>
                                    <td>
                                        <form method="POST" action="{{ route('admin.categorias.update', $categoria) }}"
                                            class="category-edit-form">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="categoria_id" value="{{ $categoria->id }}">
                                            <div class="input-group input-group-sm">
                                                <input type="text" name="nombre_categoria"
                                                        class="form-control {{ $errors->has('nombre_categoria') && old('categoria_id') == $categoria->id ? 'is-invalid' : '' }}"
                                                    value="{{ old('categoria_id') == $categoria->id ? old('nombre_categoria', $categoria->nombre) : $categoria->nombre }}"
                                                    maxlength="120" aria-label="Nombre de la categoría {{ $categoria->nombre }}" required>
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-outline-primary" title="Guardar nombre">
                                                        <i class="fas fa-save"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            @if ($errors->has('nombre_categoria') && old('categoria_id') == $categoria->id)
                                                <div class="invalid-feedback d-block">{{ $errors->first('nombre_categoria') }}</div>
                                            @endif
                                        </form>
                                    </td>
                                    <td class="text-center align-middle">{{ $categoria->products_count }}</td>
                                    <td class="text-right align-middle">
                                        @if ($categoria->products_count === 0)
                                            <form method="POST" action="{{ route('admin.categorias.destroy', $categoria) }}"
                                                class="d-inline" data-confirm-title="Eliminar categoría"
                                                data-confirm-message="¿Eliminar la categoría “{{ $categoria->nombre }}”?"
                                                data-confirm-accept="Eliminar" data-confirm-destructive="true">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                                    <i class="fas fa-trash-alt mr-1"></i> Eliminar
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                                                title="Cambia primero los productos asociados a otra categoría">
                                                <i class="fas fa-lock mr-1"></i> En uso
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">Todavía no hay categorías guardadas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="small text-muted mb-0">Las categorías con productos asociados no se pueden eliminar. Cambia esos productos a otra categoría primero.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@if ($errors->has('nombre') || $errors->has('nombre_categoria') || session('error'))
    @push('js')
        <script>
            $(function() {
                $('#createCategoryModal').modal('show');
            });
        </script>
    @endpush
@endif
