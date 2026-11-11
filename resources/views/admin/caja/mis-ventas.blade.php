@extends('adminlte::page')

@section('title', 'Mis Ventas - Caja')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1><i class="fas fa-cash-register mr-2 text-purple"></i> Mis ventas de caja</h1>
        <a href="{{ route('admin.caja.index') }}" class="btn btn-primary bg-purple">
            <i class="fas fa-arrow-left mr-1"></i> Volver al POS (Caja)
        </a>
    </div>
@stop

@section('content')
<div class="row">
    <!-- Tarjeta Resumen 1 -->
    <div class="col-lg-6 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>Bs. {{ number_format($totalHoy, 2) }}</h3>
                <p>Total vendido hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
        </div>
    </div>
    <!-- Tarjeta Resumen 2 -->
    <div class="col-lg-6 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $fichasHoy }}</h3>
                <p>Mis tickets de hoy</p>
            </div>
            <div class="icon">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-purple">
    <div class="card-header">
        <h3 class="card-title font-weight-bold">Historial de mis ventas</h3>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover text-nowrap">
            <thead>
                <tr>
                    <th>Nº Ticket</th>
                    <th>Fecha y Hora</th>
                    <th>Productos / Detalle</th>
                    <th>Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td class="font-weight-bold">{{ $order->numero_ficha ?? '#'.$order->id }}</td>
                        <td class="text-nowrap small">
                            <i class="fas fa-calendar mr-1 text-muted"></i>
                            {{ $order->created_at->timezone('America/La_Paz')->format('d/m/Y') }}
                            <div class="text-muted">
                                <i class="fas fa-clock mr-1"></i>{{ $order->created_at->timezone('America/La_Paz')->format('H:i') }}
                            </div>
                        </td>
                        <td>
                            <ul class="pl-3 mb-0 small text-muted">
                                @foreach($order->items as $item)
                                    <li>{{ $item->cantidad ?? $item->quantity }}x {{ $item->nombre_producto }} (Bs. {{ number_format($item->precio, 2) }})</li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="font-weight-bold text-success">Bs. {{ number_format($order->total, 2) }}</td>
                        <td>
                            <span class="badge badge-success">{{ $order->estado }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">Aún no has registrado ventas en este turno.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer clearfix">
        {{ $orders->links() }}
    </div>
</div>
@stop