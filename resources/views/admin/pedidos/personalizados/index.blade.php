@extends('adminlte::page')

@section('title', 'Gestión de pedidos personalizados')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-white rounded shadow-sm border-left"
        style="border-left-color: #6c3461 !important; border-width: 4px !important;">
        <h4 class="font-weight-bold text-dark m-0">
            <i class="fas fa-birthday-cake mr-2" style="color:#6c3461;"></i>
            GESTIÓN DE PEDIDOS PERSONALIZADOS
        </h4>
        @can('admin.pedidos.crear')
            <a href="{{ route('admin.pedidos.personalizados.create') }}" class="btn text-white"
                style="background:#6c3461;">
                <i class="fas fa-plus-circle mr-1"></i> Nuevo pedido personalizado
            </a>
        @endcan
    </div>
@stop

@section('content')
    <div class="alert alert-light border-left mb-3" style="border-left:4px solid #6c3461 !important;">
        Aquí se registran encargos hechos a medida, con los requisitos y el precio acordado con cada cliente.
    </div>

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.pedidos.personalizados.index') }}" class="form-row align-items-end mb-3">
                <div class="form-group col-md-5">
                    <label for="q">Buscar</label>
                    <input type="search" name="q" id="q" value="{{ request('q') }}" class="form-control"
                        placeholder="Código, cliente o teléfono">
                </div>
                <div class="form-group col-md-4">
                    <label for="estado">Estado</label>
                    <select name="estado" id="estado" class="form-control">
                        <option value="">Todos los estados</option>
                        @foreach ($estados as $clave => $label)
                            <option value="{{ $clave }}" @selected(request('estado') === $clave)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <button type="submit" class="btn btn-outline-secondary mr-1">
                        <i class="fas fa-search mr-1"></i> Buscar
                    </button>
                    <a href="{{ route('admin.pedidos.personalizados.index') }}" class="btn btn-link">Limpiar</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Cliente</th>
                            <th>Entrega</th>
                            <th>Personalización</th>
                            <th>Estado</th>
                            <th class="text-right">Total estimado</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pedidos as $pedido)
                            <tr>
                                <td class="font-weight-bold">{{ $pedido->codigo }}</td>
                                <td>
                                    {{ $pedido->cliente_nombre_completo }}
                                    @if ($pedido->cliente_telefono)
                                        <small class="d-block text-muted">{{ $pedido->cliente_telefono }}</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $pedido->fecha_entrega->format('d/m/Y') }}
                                    <small class="d-block text-muted">{{ $pedido->hora_entrega ?: 'Hora por confirmar' }}</small>
                                </td>
                                <td>
                                    @if ($pedido->tipo_encargo)
                                        <strong>{{ $pedido->tipo_encargo }}</strong>
                                    @endif
                                    @if ($pedido->ocasion)
                                        <div>{{ $pedido->ocasion }}</div>
                                    @endif
                                    @if ($pedido->porciones)
                                        <div>{{ $pedido->porciones }} porciones</div>
                                    @endif
                                    @if ($pedido->sabor)
                                        <small class="d-block text-muted">Sabor: {{ $pedido->sabor }}</small>
                                    @endif
                                    @if ($pedido->relleno)
                                        <small class="d-block text-muted">Relleno: {{ $pedido->relleno }}</small>
                                    @endif
                                    @if ($pedido->decoracion)
                                        <small class="d-block text-muted">{{ \Illuminate\Support\Str::limit($pedido->decoracion, 100) }}</small>
                                    @endif
                                    @foreach ($pedido->items as $item)
                                        <div>{{ $item->cantidad }} × {{ $item->nombre_producto }}</div>
                                        @if ($item->observacion)
                                            <small class="text-muted">{{ $item->observacion }}</small>
                                        @endif
                                    @endforeach
                                    @if ($pedido->detalles)
                                        <small class="d-block text-muted mt-1">{{ $pedido->detalles }}</small>
                                    @endif
                                </td>
                                <td><span class="badge badge-{{ $pedido->estado_color }}">{{ $pedido->estado_label }}</span></td>
                                <td class="text-right">Bs. {{ number_format((float) $pedido->total_estimado, 2) }}</td>
                                <td class="text-right text-nowrap">
                                    <a href="{{ route('admin.pedidos.show', $pedido) }}" class="btn btn-sm btn-outline-primary"
                                        title="Ver pedido">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @can('update', $pedido)
                                        <a href="{{ route('admin.pedidos.edit', $pedido) }}" class="btn btn-sm btn-outline-warning"
                                            title="Editar pedido">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x d-block mb-2"></i>
                                    No hay pedidos personalizados que coincidan con la búsqueda.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer clearfix">
            {{ $pedidos->links() }}
        </div>
    </div>
@stop
