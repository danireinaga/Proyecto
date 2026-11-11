@extends('adminlte::page')

@section('title', 'Reportes')

@section('content_header')
    <div class="d-flex flex-wrap align-items-center justify-content-between">
        <div>
            <h1 class="mb-1">Reportes</h1>
            <p class="text-muted mb-0">Consulta y exporta la actividad del negocio.</p>
        </div>
        <div class="report-actions">
            <a href="{{ $excelUrl }}" class="btn btn-success"><i class="far fa-file-excel mr-1"></i> Exportar Excel</a>
            <a href="{{ $printUrl }}" target="_blank" rel="noopener" class="btn btn-outline-secondary"><i class="fas fa-print mr-1"></i> Imprimir / PDF</a>
        </div>
    </div>
@stop

@section('content')
    <div class="container-fluid px-0">
        <div class="card report-card mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('admin.reportes.index') }}" class="row align-items-end">
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="tipo">Tipo de reporte</label>
                        <select class="form-control" id="tipo" name="tipo">
                            @can('admin.ventas.ver')
                                <option value="ventas" @selected($type === 'ventas')>Reporte de ventas</option>
                            @endcan
                            @can('admin.pedidos.ver')
                                <option value="pedidos" @selected($type === 'pedidos')>Reporte de pedidos</option>
                            @endcan
                        </select>
                    </div>
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="desde">Desde</label>
                        <input class="form-control" type="date" id="desde" name="desde" value="{{ $filters['desde'] }}" required>
                    </div>
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="hasta">Hasta</label>
                        <input class="form-control" type="date" id="hasta" name="hasta" value="{{ $filters['hasta'] }}" required>
                    </div>
                    @if ($supervisor)
                        <div class="form-group col-lg-3 col-md-6">
                            <label for="empleado_id">Responsable</label>
                            <select class="form-control" id="empleado_id" name="empleado_id">
                                <option value="">Todo el personal</option>
                                @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}" @selected((string) $filters['empleado_id'] === (string) $employee->id)>{{ $employee->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="form-group col-lg-2 col-md-4">
                        <label for="estado">Estado</label>
                        <select class="form-control" id="estado" name="estado">
                            <option value="">Todos los estados</option>
                            @if ($type === 'ventas')
                                <option value="{{ \App\Models\Order::ESTADO_COMPLETADO }}" @selected($filters['estado'] === \App\Models\Order::ESTADO_COMPLETADO)>Completada</option>
                                <option value="{{ \App\Models\Order::ESTADO_CANCELADO }}" @selected($filters['estado'] === \App\Models\Order::ESTADO_CANCELADO)>Cancelada</option>
                            @else
                                @foreach (\App\Models\Pedido::ESTADOS as $status => $label)
                                    <option value="{{ $status }}" @selected($filters['estado'] === $status)>{{ $label }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="form-group col-lg-1 col-md-4">
                        <button class="btn btn-primary btn-block" type="submit"><i class="fas fa-filter mr-1"></i> Filtrar</button>
                    </div>
                </form>
            </div>
        </div>

        @if ($type === 'ventas')
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="small-box report-kpi report-kpi--sales"><div class="inner"><h3>Bs. {{ number_format($sales['total'], 2, ',', '.') }}</h3><p>Venta completada</p></div><div class="icon"><i class="fas fa-coins"></i></div></div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box report-kpi report-kpi--count"><div class="inner"><h3>{{ number_format($sales['count']) }}</h3><p>Ventas registradas</p></div><div class="icon"><i class="fas fa-receipt"></i></div></div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box report-kpi report-kpi--average"><div class="inner"><h3>Bs. {{ number_format($sales['average'], 2, ',', '.') }}</h3><p>Promedio por venta</p></div><div class="icon"><i class="fas fa-chart-line"></i></div></div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="small-box report-kpi report-kpi--cancelled"><div class="inner"><h3>{{ number_format($sales['cancelled']) }}</h3><p>Ventas anuladas</p></div><div class="icon"><i class="fas fa-ban"></i></div></div>
                </div>
            </div>
            <div class="row">
                <div class="col-lg-7">
                    <div class="card report-card h-100">
                        <div class="card-header border-0"><h3 class="card-title font-weight-bold">Ventas por día</h3></div>
                        <div class="card-body">
                            @php($maxDailySales = max(1, (float) $dailySales->max('amount')))
                            @forelse ($dailySales as $day)
                                <div class="report-bar-row">
                                    <span>{{ \Illuminate\Support\Carbon::parse($day->day)->format('d/m') }}</span>
                                    <div class="progress flex-grow-1"><div class="progress-bar bg-primary" style="width: {{ min(100, $day->amount / $maxDailySales * 100) }}%"></div></div>
                                    <strong>Bs. {{ number_format((float) $day->amount, 2, ',', '.') }}</strong>
                                </div>
                            @empty
                                <p class="text-muted mb-0">No hay ventas completadas en este periodo.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card report-card h-100">
                        <div class="card-header border-0"><h3 class="card-title font-weight-bold">Productos más vendidos</h3></div>
                        <div class="card-body p-0">
                            @forelse ($products as $product)
                                <div class="report-product-row">
                                    <span class="flex-grow-1">{{ $product->name }}</span>
                                    <span class="badge badge-light">{{ number_format((int) $product->units) }} unidades</span>
                                    <strong>Bs. {{ number_format((float) $product->amount, 2, ',', '.') }}</strong>
                                </div>
                            @empty
                                <div class="p-3 text-muted">No hay productos vendidos.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="row">
                <div class="col-lg-3 col-6"><div class="small-box report-kpi report-kpi--count"><div class="inner"><h3>{{ number_format($orders['count']) }}</h3><p>Pedidos encontrados</p></div><div class="icon"><i class="fas fa-clipboard-list"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box report-kpi report-kpi--sales"><div class="inner"><h3>{{ number_format($orders['active']) }}</h3><p>Activos</p></div><div class="icon"><i class="fas fa-hourglass-half"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box report-kpi report-kpi--average"><div class="inner"><h3>{{ number_format($orders['delivered']) }}</h3><p>Entregados</p></div><div class="icon"><i class="fas fa-check-circle"></i></div></div></div>
                <div class="col-lg-3 col-6"><div class="small-box report-kpi report-kpi--cancelled"><div class="inner"><h3>{{ number_format($orders['cancelled']) }}</h3><p>Cancelados</p></div><div class="icon"><i class="fas fa-ban"></i></div></div></div>
            </div>
        @endif

        <div class="card report-card">
            <div class="card-header border-0 d-flex align-items-center justify-content-between">
                <h3 class="card-title font-weight-bold">{{ $type === 'ventas' ? 'Detalle de ventas' : 'Detalle de pedidos' }}</h3>
                <span class="text-muted small">{{ $from->format('d/m/Y') }} al {{ $to->format('d/m/Y') }}</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover mb-0">
                    <thead><tr>
                        <th>{{ $type === 'ventas' ? 'Ficha' : 'Código' }}</th>
                        <th>Cliente</th>
                        <th>CI / NIT</th>
                        <th>{{ $type === 'ventas' ? 'Fecha de venta' : 'Entrega' }}</th>
                        @if ($type === 'ventas')<th>Pago</th>@endif
                        <th>Estado</th>
                        <th>Responsable</th>
                        <th class="text-right">Total</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="font-weight-bold">{{ $type === 'ventas' ? ($row->numero_ficha ?: '—') : $row->codigo }}</td>
                                <td>{{ $row->cliente_nombre_completo }}</td>
                                <td>{{ $row->cliente_nit ?: '—' }}</td>
                                <td>
                                    @if ($type === 'ventas')
                                        {{ $row->created_at->format('d/m/Y H:i') }}
                                    @else
                                        {{ $row->fecha_entrega->format('d/m/Y') }} {{ $row->hora_entrega ? '· '.$row->hora_entrega : '' }}
                                    @endif
                                </td>
                                @if ($type === 'ventas')<td>{{ \App\Models\Order::PAGOS_LABELS[$row->pago_tipo] ?? '—' }}</td>@endif
                                <td><span class="badge badge-{{ $type === 'pedidos' ? $row->estado_color : ($row->estado === \App\Models\Order::ESTADO_COMPLETADO ? 'success' : 'danger') }}">{{ $type === 'pedidos' ? $row->estado_label : ucfirst($row->estado) }}</span></td>
                                <td>{{ $row->user->name ?? 'Sin responsable' }}</td>
                                <td class="text-right font-weight-bold">Bs. {{ number_format((float) ($type === 'ventas' ? $row->total_final : $row->total_estimado), 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="{{ $type === 'ventas' ? 8 : 7 }}" class="text-center text-muted py-4">No se encontraron registros con esos filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows->hasPages())<div class="card-footer">{{ $rows->links() }}</div>@endif
        </div>
    </div>
@stop

@push('css')
    <style>
        .report-card { border: 0; border-radius: .75rem; box-shadow: 0 5px 18px rgba(33,37,41,.07); }
        .report-actions { display: flex; gap: .5rem; }
        .report-kpi { border-radius: .7rem; box-shadow: 0 4px 14px rgba(33,37,41,.07); overflow: hidden; }
        .report-kpi .inner { position: relative; z-index: 1; }
        .report-kpi .inner h3 { font-size: 1.55rem; }
        .report-kpi .icon { color: rgba(255,255,255,.2); }
        .report-kpi--sales { background: linear-gradient(135deg,#6c3461,#9b5688); color: white; }
        .report-kpi--count { background: linear-gradient(135deg,#247b83,#42a7a0); color: white; }
        .report-kpi--average { background: linear-gradient(135deg,#48618f,#718fbd); color: white; }
        .report-kpi--cancelled { background: linear-gradient(135deg,#bd623d,#df9161); color: white; }
        .report-bar-row { display: flex; align-items: center; gap: .75rem; margin: .85rem 0; }
        .report-bar-row > span { width: 3rem; color: #6c757d; }
        .report-bar-row .progress { height: .55rem; }
        .report-bar-row strong { width: 8rem; text-align: right; font-size: .8rem; }
        .report-product-row { display: flex; align-items: center; gap: .65rem; padding: .8rem 1rem; border-top: 1px solid #f0f1f3; }
        @media(max-width:767.98px) { .report-actions { width: 100%; margin-top: 1rem; } .report-actions .btn { flex: 1; } .report-product-row { flex-wrap: wrap; } }
    </style>
@endpush
