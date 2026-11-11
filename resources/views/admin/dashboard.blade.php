@extends('adminlte::page')

@section('title', 'Panel principal')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">Panel principal</h1>
            <p class="text-muted mb-0">Resumen del negocio · {{ $periodLabel }}</p>
        </div>
        <span class="badge badge-light px-3 py-2"><i class="far fa-calendar-alt mr-1"></i> Últimos 7 días</span>
    </div>
@stop

@section('content')
    <div class="container-fluid px-0">
        <div class="row">
            @if ($canSeeSales && $sales)
                <div class="col-lg-3 col-6">
                    <div class="small-box dashboard-box dashboard-box--sales">
                        <div class="inner">
                            <h3>Bs. {{ number_format((float) $sales->amount, 2, ',', '.') }}</h3>
                            <p>Ventas completadas</p>
                            <small>{{ number_format((int) $sales->count) }} ventas esta semana</small>
                        </div>
                        <div class="icon"><i class="fas fa-cash-register"></i></div>
                        <a href="{{ route('admin.ventas.index') }}" class="small-box-footer">Reporte de ventas <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            @endif
            @if ($canSeeOrders && $orders)
                <div class="col-lg-3 col-6">
                    <div class="small-box dashboard-box dashboard-box--orders">
                        <div class="inner">
                            <h3>{{ number_format($orders['active']) }}</h3>
                            <p>Pedidos activos</p>
                            <small>{{ number_format($orders['today']) }} para hoy</small>
                        </div>
                        <div class="icon"><i class="fas fa-clipboard-list"></i></div>
                        <a href="{{ route('admin.pedidos.index') }}" class="small-box-footer">Gestión de pedidos <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box dashboard-box dashboard-box--late">
                        <div class="inner">
                            <h3>{{ number_format($orders['late']) }}</h3>
                            <p>Pedidos atrasados</p>
                            <small>Requieren seguimiento</small>
                        </div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                        <a href="{{ route('admin.pedidos.index') }}" class="small-box-footer">Revisar pedidos <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            @endif
            @if ($canSeeProducts)
                <div class="col-lg-3 col-6">
                    <div class="small-box dashboard-box dashboard-box--stock">
                        <div class="inner">
                            <h3>{{ number_format($lowStock->count()) }}</h3>
                            <p>Productos con poco stock</p>
                            <small>Existencias de 5 unidades o menos</small>
                        </div>
                        <div class="icon"><i class="fas fa-box-open"></i></div>
                        <a href="{{ route('admin.productos.index') }}" class="small-box-footer">Revisar inventario <i class="fas fa-arrow-circle-right"></i></a>
                    </div>
                </div>
            @endif
        </div>

        <div class="row">
            @if ($canSeeSales)
                <div class="col-lg-7">
                    <div class="card dashboard-card">
                        <div class="card-header border-0">
                            <h3 class="card-title font-weight-bold"><i class="fas fa-chart-bar text-primary mr-2"></i>Ventas por día</h3>
                            <div class="card-tools text-muted small">Importe en Bs.</div>
                        </div>
                        <div class="card-body pt-1">
                            @php($maxDailySales = max(1, collect($salesByDay)->max('amount') ?? 0))
                            @forelse ($salesByDay as $day)
                                <div class="dashboard-bar-row">
                                    <span class="dashboard-bar-label">{{ $day['label'] }}</span>
                                    <div class="progress flex-grow-1">
                                        <div class="progress-bar bg-primary" role="progressbar"
                                            style="width: {{ min(100, $day['amount'] / $maxDailySales * 100) }}%"
                                            aria-valuenow="{{ $day['amount'] }}" aria-valuemin="0" aria-valuemax="{{ $maxDailySales }}"></div>
                                    </div>
                                    <span class="dashboard-bar-value">Bs. {{ number_format($day['amount'], 2, ',', '.') }}</span>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No hay ventas registradas en este periodo.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card dashboard-card">
                        <div class="card-header border-0">
                            <h3 class="card-title font-weight-bold"><i class="fas fa-star text-warning mr-2"></i>Productos más vendidos</h3>
                        </div>
                        <div class="card-body p-0">
                            @forelse ($topProducts as $index => $product)
                                <div class="dashboard-list-item">
                                    <span class="dashboard-rank">{{ $index + 1 }}</span>
                                    <span class="flex-grow-1 text-truncate">{{ $product->nombre_producto }}</span>
                                    <span class="badge badge-light">{{ number_format((int) $product->units) }} un.</span>
                                </div>
                            @empty
                                <div class="p-3 text-muted">Aún no hay productos vendidos en este periodo.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            @if ($canSeeOrders)
                <div class="col-lg-7">
                    <div class="card dashboard-card">
                        <div class="card-header border-0">
                            <h3 class="card-title font-weight-bold"><i class="far fa-calendar-check text-success mr-2"></i>Próximos pedidos</h3>
                            <div class="card-tools"><a href="{{ route('admin.calendar.index') }}" class="btn btn-sm btn-outline-secondary">Abrir calendario</a></div>
                        </div>
                        <div class="card-body p-0">
                            @forelse ($upcomingOrders as $pedido)
                                <div class="dashboard-list-item">
                                    <div class="dashboard-date-tile">
                                        <strong>{{ $pedido->fecha_entrega->format('d') }}</strong>
                                        <small>{{ $pedido->fecha_entrega->translatedFormat('M') }}</small>
                                    </div>
                                    <div class="flex-grow-1 min-width-0">
                                        <div class="font-weight-bold text-truncate">{{ $pedido->cliente_nombre_completo }}</div>
                                        <small class="text-muted">{{ $pedido->codigo }} · {{ $pedido->hora_entrega ?: 'Hora por confirmar' }}</small>
                                    </div>
                                    <span class="badge badge-{{ $pedido->estado_color }}">{{ $pedido->estado_label }}</span>
                                </div>
                            @empty
                                <div class="p-3 text-muted">No hay pedidos próximos pendientes.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            @if ($canSeeProducts)
                <div class="col-lg-5">
                    <div class="card dashboard-card">
                        <div class="card-header border-0">
                            <h3 class="card-title font-weight-bold"><i class="fas fa-exclamation-triangle text-danger mr-2"></i>Alertas de inventario</h3>
                        </div>
                        <div class="card-body p-0">
                            @forelse ($lowStock as $product)
                                <div class="dashboard-list-item">
                                    <span class="flex-grow-1 text-truncate">{{ $product->nombre }}</span>
                                    <span class="badge badge-{{ $product->stock <= 0 ? 'danger' : 'warning' }}">{{ $product->stock }} en stock</span>
                                </div>
                            @empty
                                <div class="p-3 text-muted">Todo bien: no hay productos con stock bajo.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@stop

@push('css')
    <style>
        .dashboard-box { border-radius: .75rem; box-shadow: 0 5px 18px rgba(33, 37, 41, .08); color: #fff; overflow: hidden; }
        .dashboard-box .inner { position: relative; z-index: 1; }
        .dashboard-box .inner h3 { font-size: 1.8rem; }
        .dashboard-box .icon { color: rgba(255,255,255,.22); }
        .dashboard-box .small-box-footer { background: rgba(0,0,0,.12); }
        .dashboard-box--sales { background: linear-gradient(135deg, #6c3461, #9b5688); }
        .dashboard-box--orders { background: linear-gradient(135deg, #247b83, #42a7a0); }
        .dashboard-box--late { background: linear-gradient(135deg, #bd623d, #df9161); }
        .dashboard-box--stock { background: linear-gradient(135deg, #48618f, #718fbd); }
        .dashboard-card { border: 0; border-radius: .75rem; box-shadow: 0 5px 18px rgba(33, 37, 41, .07); }
        .dashboard-bar-row { display: flex; align-items: center; gap: .75rem; margin: .9rem 0; }
        .dashboard-bar-label { width: 3.5rem; color: #6c757d; font-size: .85rem; }
        .dashboard-bar-row .progress { height: .55rem; background: #edf0f4; }
        .dashboard-bar-value { width: 8rem; text-align: right; font-size: .8rem; white-space: nowrap; }
        .dashboard-list-item { display: flex; align-items: center; gap: .8rem; padding: .85rem 1rem; border-top: 1px solid #f0f1f3; }
        .dashboard-rank { display: inline-flex; width: 1.8rem; height: 1.8rem; align-items: center; justify-content: center; border-radius: 50%; background: #f1eaf0; color: #6c3461; font-weight: 700; }
        .dashboard-date-tile { min-width: 2.8rem; padding: .3rem; text-align: center; border-radius: .5rem; background: #e8f5f1; color: #247b69; line-height: 1.15; }
        .dashboard-date-tile strong, .dashboard-date-tile small { display: block; }
        .dashboard-date-tile small { font-size: .7rem; text-transform: uppercase; }
        .min-width-0 { min-width: 0; }
        @media (max-width: 575.98px) { .dashboard-bar-row { gap: .4rem; } .dashboard-bar-label { width: 2.5rem; } .dashboard-bar-value { width: 6.4rem; font-size: .72rem; } }
    </style>
@endpush
