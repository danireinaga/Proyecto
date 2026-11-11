<div class="modal-content">
    <div class="modal-header">
        <h5 class="modal-title font-weight-bold">
            <i class="fas {{ $pedido->exists ? 'fa-edit' : 'fa-plus-circle' }} mr-2 text-primary"></i>
            {{ $pedido->exists ? 'Editar pedido '.$pedido->codigo : 'Nuevo pedido personalizado' }}
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        @include('admin.pedidos._form', [
            'accionRuta' => $pedido->exists
                ? route('admin.pedidos.update', $pedido)
                : route('admin.pedidos.store'),
            'metodo' => $pedido->exists ? 'PUT' : 'POST',
        ])
    </div>
    @if ($pedido->exists && $pedido->estado !== \App\Models\Pedido::ESTADO_CANCELADO)
        @can('delete', $pedido)
            <div class="modal-footer justify-content-between">
                <form action="{{ route('admin.pedidos.cancel', $pedido) }}" method="POST" class="js-cancel-pedido">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="fas fa-ban mr-1"></i> Cancelar pedido
                    </button>
                </form>
                <small class="text-muted">Se conservará en el historial.</small>
            </div>
        @endcan
    @endif
</div>
