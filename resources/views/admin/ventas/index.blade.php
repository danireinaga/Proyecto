@extends('adminlte::page')

@section('title', 'Reporte de ventas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-white rounded shadow-sm border-left border-primary"
        style="border-left-color: #6c3461 !important; border-width: 4px !important;">
        <h4 class="font-weight-bold text-dark m-0 d-flex align-items-center">
            <span class="fa-stack fa-sm mr-2" style="color: #6c3461;">
                <i class="fas fa-circle fa-stack-2x" style="opacity: 0.15;"></i>
                <i class="fas fa-receipt fa-stack-1x"></i>
            </span>
            REPORTE DE VENTAS
            <span class="badge ml-3 shadow-sm text-white"
                style="background-color: #6c3461; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.5px;">
                {{ $pestanaActual['etiqueta'] }}
            </span>
        </h4>
        <span class="badge badge-success shadow-sm px-3 py-2">
            Total: Bs. {{ number_format((float) $totalVentas, 2) }}
        </span>
    </div>
@stop

@section('content')
    <style>
        /* Paleta temática lila / repostería (misma que el módulo de Pedidos) */
        .ven-card {
            border-top: 3px solid #6c3461;
        }

        .ven-btn {
            background: linear-gradient(135deg, #6c3461 0%, #512648 100%);
            color: #fff;
            border: none;
            box-shadow: 0 4px 10px rgba(108, 52, 97, 0.25);
        }

        .ven-btn:hover {
            background: linear-gradient(135deg, #512648 0%, #3a1d34 100%);
            color: #fff;
        }

        /* Barra de pestañas dinámicas: una por usuario/empleado registrado */
        .ven-tabs {
            border-bottom: 1px solid #efe3ea;
        }

        .ven-tab {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background-color: #fff;
            color: #5c3a53;
            border: 1px solid #e2cdd9;
            border-bottom: none;
            border-radius: 12px 12px 0 0;
            padding: 9px 16px;
            margin-right: 4px;
            margin-bottom: -1px;
            font-size: 0.88rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
            white-space: nowrap;
        }

        .ven-tab:hover {
            background-color: #f7eff4;
            color: #6c3461;
            transform: translateY(-1px);
        }

        .ven-tab.active {
            background: linear-gradient(135deg, #6c3461 0%, #512648 100%);
            color: #fff;
            border-color: #6c3461;
            box-shadow: 0 -3px 8px rgba(108, 52, 97, 0.25);
        }

        .ven-tab .tab-conteo {
            background-color: #f0e4ec;
            color: #512648;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 1px 8px;
            border-radius: 10px;
        }

        .ven-tab.active .tab-conteo {
            background-color: rgba(255, 255, 255, 0.22);
            color: #fff;
        }

        .bloque-icono {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 1.05rem;
        }

        .ventas-card {
            border-top: 3px solid #6c3461;
        }

        .ventas-card.empleados {
            border-top-color: #17a2b8;
        }
    </style>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>Bs. {{ number_format($metricas['ventas_semana'], 2) }}</h3>
                    <p>Ventas completadas del periodo</p>
                </div>
                <div class="icon"><i class="fas fa-cash-register"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $metricas['cantidad_semana'] }}</h3>
                    <p>Ventas completadas</p>
                </div>
                <div class="icon"><i class="fas fa-receipt"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3 class="text-truncate" title="{{ $metricas['producto_mas_vendido'] }}">
                        {{ $metricas['producto_mas_vendido'] }}
                    </h3>
                    <p>Producto más vendido ({{ $metricas['cantidad_producto'] }} unidades)</p>
                    <p class="small mb-0">Menos vendido: {{ $metricas['producto_menos_vendido'] }}
                        ({{ $metricas['cantidad_producto_menos_vendido'] }})</p>
                </div>
                <div class="icon"><i class="fas fa-cookie-bite"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3 class="text-truncate" title="{{ $metricas['mejor_empleado'] }}">
                        {{ $metricas['mejor_empleado'] }}
                    </h3>
                    <p>Mayor venta: Bs. {{ number_format($metricas['ventas_mejor_empleado'], 2) }}</p>
                </div>
                <div class="icon"><i class="fas fa-user-tie"></i></div>
            </div>
        </div>
    </div>

    <!-- Pestañas dinámicas: se generan a partir de los usuarios registrados -->
    <div class="card ven-card mb-3">
        <div class="card-body pb-2">
            <h4 class="font-weight-bold text-dark mb-2 d-flex align-items-center">
                <i class="fas fa-layer-group mr-2" style="color: #6c3461;"></i>
                Ventas por responsable
                <span class="badge badge-pill badge-light border text-muted ml-2">
                    {{ count($pestanas) }} {{ count($pestanas) == 1 ? 'pestaña' : 'pestañas' }}
                </span>
            </h4>

            <div class="ven-tabs d-flex flex-wrap">
                @foreach ($pestanas as $pestana)
                    <a href="{{ route('admin.ventas.index', array_merge($paramsFiltros, ['pestana' => $pestana['clave'], 'user_id' => $pestana['user_id']])) }}"
                        class="ven-tab {{ $pestana['clave'] === $pestanaActiva ? 'active' : '' }}"
                        title="{{ $pestana['tituloPestana'] }}">
                        <i class="fas {{ $pestana['icono'] }}"></i>
                        {{ $pestana['etiqueta'] }}
                        <span class="tab-conteo badge">{{ $pestana['conteo'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
        <div class="card-footer py-1" style="background-color: #fdfafc;">
            <small class="text-muted">
                <i class="fas fa-circle mr-1" style="font-size: 0.5rem; color: #17a2b8;"></i>
                Cada pestaña muestra únicamente las ventas de su responsable.
                Las ventas de la pestaña actual: <strong>Bs.
                    {{ number_format((float) $totalVentas, 2) }}</strong>.
            </small>
        </div>
    </div>

    <!-- Filtros de búsqueda -->
    <div class="card ven-card mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('admin.ventas.index') }}" class="form-row align-items-center">
                <input type="hidden" name="pestana" value="{{ $pestanaActiva }}">

                <div class="col-md-3 mb-2">
                    <input type="text" name="q" value="{{ $filtros['q'] ?? '' }}" class="form-control form-control-sm"
                        placeholder="Cliente, CI/NIT o ficha...">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="fecha" value="{{ $filtros['fecha'] ?? '' }}"
                        class="form-control form-control-sm" aria-label="Fecha exacta">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="periodo" class="form-control form-control-sm">
                        <option value="semana_actual" @selected(($filtros['periodo'] ?? 'semana_actual') === 'semana_actual')>Esta semana</option>
                        <option value="semana_anterior" @selected(($filtros['periodo'] ?? '') === 'semana_anterior')>Semana anterior</option>
                        <option value="mes_actual" @selected(($filtros['periodo'] ?? '') === 'mes_actual')>Este mes</option>
                        <option value="personalizado" @selected(($filtros['periodo'] ?? '') === 'personalizado')>Rango personalizado</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"
                        class="form-control form-control-sm" aria-label="Desde">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"
                        class="form-control form-control-sm" aria-label="Hasta">
                </div>
                <div class="col-md-3 mb-2">
                    <select name="producto" class="form-control form-control-sm">
                        <option value="">Todos los productos</option>
                        @foreach ($productosVendidos as $producto)
                            <option value="{{ $producto }}" @selected(($filtros['producto'] ?? '') === $producto)>
                                {{ $producto }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @if ($esSupervisor)
                    <div class="col-md-3 mb-2">
                        <select name="empleado_id" class="form-control form-control-sm">
                            <option value="">Todos los responsables</option>
                            @foreach ($empleados as $empleado)
                                <option value="{{ $empleado->id }}" @selected((string) ($filtros['empleado_id'] ?? '') === (string) $empleado->id)>
                                    {{ $empleado->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-6 mb-2 text-md-right">
                    <button type="submit" class="btn btn-sm ven-btn mr-1">
                        <i class="fas fa-filter mr-1"></i> Filtrar
                    </button>
                    @if ($paramsFiltros !== [])
                        <a href="{{ route('admin.ventas.index', ['pestana' => $pestanaActiva]) }}"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times mr-1"></i> Limpiar
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <!-- Listado de ventas de la pestaña activa -->
    @include('admin.ventas._ventas-bloque', [
        'ventas' => $ventas,
        'titulo' => $pestanaActual['ventas']['titulo'],
        'descripcion' => $pestanaActual['ventas']['descripcion'],
        'descripcionVacia' => $pestanaActual['ventas']['descripcionVacia'],
        'icono' => $pestanaActual['icono'],
        'color' => $pestanaActual['color'],
        'total' => $totalVentas,
        'clase' => $pestanaActual['ventas']['clase'],
    ])
@stop