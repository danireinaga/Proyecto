@extends('adminlte::page')

@section('title', 'Detalle del Pedido')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 text-dark">Pedido: {{ $pedido->codigo }}</h1>
        <a href="{{ $pedido->tipo === \App\Models\Pedido::TIPO_PERSONALIZADO ? route('admin.pedidos.personalizados.index') : route('admin.pedidos.index') }}"
            class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver a la lista
        </a>
    </div>
@stop

@section('content')
    <div class="card card-outline card-primary">
        <div class="card-header">
            <h3 class="card-title">Información General</h3>
        </div>
        <div class="card-body">
            <p><strong>Cliente:</strong> {{ $pedido->cliente_nombre_completo }}</p>
            <p><strong>Teléfono:</strong> {{ $pedido->cliente_telefono ?: 'Sin teléfono' }}</p>
            <p><strong>Dirección:</strong> {{ $pedido->cliente_direccion ?: 'Sin dirección' }}</p>
            <p><strong>Responsable:</strong> {{ $pedido->user?->name ?? 'Sin asignar' }}</p>
            <p><strong>Registrado por:</strong> {{ $pedido->creator?->name ?? 'No disponible' }}</p>
            @if ($pedido->lastEditor)
                <p><strong>Última modificación por:</strong> {{ $pedido->lastEditor->name }}</p>
            @endif
            <p><strong>Fecha del pedido:</strong> {{ $pedido->fecha_pedido->format('d/m/Y') }}</p>
            <p><strong>Fecha de Entrega:</strong> {{ $pedido->fecha_entrega->format('d/m/Y') }}</p>
            <p><strong>Hora de entrega:</strong>
                {{ \Illuminate\Support\Carbon::parse($pedido->hora_entrega ?: '10:00')->format('H:i') }}</p>
            <p><strong>Estado:</strong> {{ $pedido->estado_label }}</p>
            <p><strong>Monto acordado:</strong> Bs. {{ number_format((float) $pedido->total_estimado, 2) }}</p>
            <p><strong>Abono:</strong> Bs. {{ number_format((float) $pedido->abono, 2) }}</p>
            <p><strong>Saldo pendiente:</strong> Bs. {{ number_format($pedido->saldo_pendiente, 2) }}</p>

            @if ($pedido->tipo === \App\Models\Pedido::TIPO_PERSONALIZADO)
                <h5 class="mt-4">Detalles del encargo</h5>
                <dl class="row">
                    @foreach ([
                        'Tipo de encargo' => $pedido->tipo_encargo,
                        'Ocasión' => $pedido->ocasion,
                        'Porciones' => $pedido->porciones,
                        'Sabor' => $pedido->sabor,
                        'Relleno' => $pedido->relleno,
                        'Cobertura' => $pedido->cobertura,
                        'Colores' => $pedido->colores,
                        'Texto o dedicatoria' => $pedido->texto_torta,
                        'Alergias o restricciones' => $pedido->alergias,
                        'Diseño y decoración' => $pedido->decoracion,
                        'Otros detalles' => $pedido->detalles,
                    ] as $etiqueta => $valor)
                        @if (filled($valor))
                            <dt class="col-sm-4">{{ $etiqueta }}</dt>
                            <dd class="col-sm-8">{{ $valor }}</dd>
                        @endif
                    @endforeach
                </dl>
                @if ($pedido->imagen_referencia)
                    <p>
                        <strong>Imagen de referencia:</strong>
                        <a href="{{ route('admin.pedidos.imagen-referencia', $pedido) }}"
                            target="_blank" rel="noopener">Ver imagen</a>
                    </p>
                @endif
                @if ($pedido->notas)
                    <p><strong>Notas internas:</strong> {{ $pedido->notas }}</p>
                @endif
                @if ($pedido->items->isNotEmpty())
                    <h6 class="mt-4">Artículos conservados del registro anterior</h6>
                    <ul>
                        @foreach ($pedido->items as $item)
                            <li>{{ $item->cantidad }} × {{ $item->nombre_producto }}</li>
                        @endforeach
                    </ul>
                @endif
            @elseif ($pedido->items->isNotEmpty())
                <h5 class="mt-4">Productos solicitados</h5>
                <table class="table table-bordered">
                    <thead>
                        <tr><th>Cantidad</th><th>Producto</th><th>Observación</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($pedido->items as $item)
                            <tr>
                                <td>{{ $item->cantidad }}</td>
                                <td>{{ $item->nombre_producto ?? $item->producto->nombre ?? 'Producto' }}</td>
                                <td>{{ $item->observacion ?: '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
@stop