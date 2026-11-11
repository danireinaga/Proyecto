@extends('adminlte::page')

@section('title', 'Gestión de pedidos')

@section('adminlte_css_pre')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content_header')
    <div class="d-flex justify-content-between align-items-center px-2 py-1 bg-white rounded shadow-sm border-left border-primary"
        style="border-left-color: #6c3461 !important; border-width: 4px !important;">
        <h4 class="font-weight-bold text-dark m-0 d-flex align-items-center">
            <span class="fa-stack fa-sm mr-2" style="color: #6c3461;">
                <i class="fas fa-circle fa-stack-2x" style="opacity: 0.15;"></i>
                <i class="fas fa-clipboard-list fa-stack-1x"></i>
            </span>
            GESTIÓN DE PEDIDOS
            <span class="badge ml-3 shadow-sm text-white"
                style="background-color: #6c3461; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.5px;">
                {{ $pestanaActual['etiqueta'] }}
            </span>
        </h4>
    </div>
@stop

@section('content')
    <style>
        /* Paleta temática lila / repostería (misma que el módulo de Caja) */
        .ped-card {
            border-top: 3px solid #6c3461;
        }

        .ped-btn {
            background: linear-gradient(135deg, #6c3461 0%, #512648 100%);
            color: #fff;
            border: none;
            box-shadow: 0 4px 10px rgba(108, 52, 97, 0.25);
        }

        .ped-btn:hover {
            background: linear-gradient(135deg, #512648 0%, #3a1d34 100%);
            color: #fff;
        }

        .ped-pill {
            background-color: #fff;
            color: #512648;
            border: 1px solid #e2cdd9;
            border-radius: 25px;
            padding: 5px 14px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-block;
            text-decoration: none;
            transition: all 0.2s ease-in-out;
        }

        .ped-pill:hover {
            background-color: #f7eff4;
            color: #6c3461;
        }

        .ped-pill.active {
            background: linear-gradient(135deg, #6c3461 0%, #512648 100%);
            color: #fff;
            border-color: #6c3461;
            box-shadow: 0 2px 6px rgba(108, 52, 97, 0.3);
        }

        .ped-dato {
            border-left: 3px solid #e2cdd9;
            padding-left: 10px;
        }

        /* Barra de pestañas dinámicas: una por usuario/empleado registrado */
        .ped-tabs {
            border-bottom: 1px solid #efe3ea;
        }

        .ped-tab {
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

        .ped-tab:hover {
            background-color: #f7eff4;
            color: #6c3461;
            transform: translateY(-1px);
        }

        .ped-tab.active {
            background: linear-gradient(135deg, #6c3461 0%, #512648 100%);
            color: #fff;
            border-color: #6c3461;
            box-shadow: 0 -3px 8px rgba(108, 52, 97, 0.25);
        }

        .ped-tab .tab-conteo {
            background-color: #f0e4ec;
            color: #512648;
            font-size: 0.72rem;
            font-weight: 700;
            padding: 1px 8px;
            border-radius: 10px;
        }

        .ped-tab.active .tab-conteo {
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

        .ped-small-box .inner h3 {
            font-size: 1.65rem;
        }
    </style>

    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary ped-small-box">
                <div class="inner">
                    <h3>{{ $contadores['pendientes'] }}</h3>
                    <p>Pedidos activos (incluye atrasados)</p>
                </div>
                <div class="icon"><i class="fas fa-clipboard-list"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning ped-small-box">
                <div class="inner">
                    <h3>{{ $contadores['hoy'] }}</h3>
                    <p>Entregas pendientes para hoy</p>
                </div>
                <div class="icon"><i class="fas fa-calendar-day"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info ped-small-box">
                <div class="inner">
                    <h3>{{ $contadores['produccion'] }}</h3>
                    <p>En producción</p>
                </div>
                <div class="icon"><i class="fas fa-blender"></i></div>
            </div>
        </div>
        <div class="col-lg-3 col-6">
            <div class="small-box bg-success ped-small-box">
                <div class="inner">
                    <h3>{{ $contadores['dia_pico'] }}</h3>
                    <p>Día con más entregas ({{ $contadores['conteo_dia_pico'] }})</p>
                </div>
                <div class="icon"><i class="fas fa-chart-line"></i></div>
            </div>
        </div>
        @if ($esSupervisor)
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger ped-small-box">
                    <div class="inner">
                        <h3 class="text-truncate" title="{{ $contadores['responsable_pico'] }}">
                            {{ $contadores['responsable_pico'] }}
                        </h3>
                        <p>Más pedidos activos ({{ $contadores['conteo_responsable_pico'] }})</p>
                    </div>
                    <div class="icon"><i class="fas fa-user-tie"></i></div>
                </div>
            </div>
        @endif
    </div>

    <!-- Pestañas dinámicas: se generan a partir de los usuarios registrados -->
    <div class="card ped-card mb-3">
        <div class="card-body pb-2">
            <h4 class="font-weight-bold text-dark mb-2 d-flex align-items-center">
                <i class="fas fa-layer-group mr-2" style="color: #6c3461;"></i>
                Pedidos por responsable
                <span class="badge badge-pill badge-light border text-muted ml-2">
                    {{ count($pestanas) }} {{ count($pestanas) == 1 ? 'pestaña' : 'pestañas' }}
                </span>
            </h4>

            <div class="ped-tabs d-flex flex-wrap">
                @foreach ($pestanas as $pestana)
                    <a href="{{ route('admin.pedidos.index', array_merge($paramsFiltros, ['pestana' => $pestana['clave'], 'user_id' => $pestana['user_id']])) }}"
                        class="ped-tab {{ $pestana['clave'] === $pestanaActiva ? 'active' : '' }}"
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
                Cada pestaña muestra únicamente los pedidos de su responsable.
                @can('admin.ventas.ver')
                    Las ventas del local se listan en
                    <a href="{{ route('admin.ventas.index') }}">Reporte de ventas</a>.
                @endcan
            </small>
        </div>
    </div>
    
    <div class="card ped-card mb-3">
        <div class="card-body py-2 px-3">
            <form method="GET" action="{{ route('admin.pedidos.index') }}" class="form-row align-items-center">
                <input type="hidden" name="pestana" value="{{ $pestanaActiva }}">
                <div class="col-md-3 mb-2">
                    <input type="search" name="q" value="{{ $filtros['q'] ?? '' }}"
                        class="form-control form-control-sm" placeholder="Cliente, CI/NIT, teléfono o código...">
                </div>
                <div class="col-md-2 mb-2">
                    <select name="periodo" class="form-control form-control-sm">
                        <option value="activos" @selected(($filtros['periodo'] ?? 'activos') === 'activos')>Activos (incluye atrasados)</option>
                        <option value="proximos" @selected(($filtros['periodo'] ?? '') === 'proximos')>Próximos desde hoy</option>
                        <option value="semana_actual" @selected(($filtros['periodo'] ?? '') === 'semana_actual')>Esta semana</option>
                        <option value="semana_anterior" @selected(($filtros['periodo'] ?? '') === 'semana_anterior')>Semana anterior</option>
                        <option value="historial" @selected(($filtros['periodo'] ?? '') === 'historial')>Historial completado/cancelado</option>
                        <option value="todos" @selected(($filtros['periodo'] ?? '') === 'todos')>Todos los pedidos</option>
                        <option value="personalizado" @selected(($filtros['periodo'] ?? '') === 'personalizado')>Rango personalizado</option>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <select name="estado" class="form-control form-control-sm">
                        <option value="">Todos los estados del periodo</option>
                        @foreach ($estados as $clave => $nombre)
                            <option value="{{ $clave }}" @selected(($filtros['estado'] ?? '') === $clave)>{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="desde" value="{{ $filtros['desde'] ?? '' }}"
                        class="form-control form-control-sm" aria-label="Fecha desde">
                </div>
                <div class="col-md-2 mb-2">
                    <input type="date" name="hasta" value="{{ $filtros['hasta'] ?? '' }}"
                        class="form-control form-control-sm" aria-label="Fecha hasta">
                </div>
                <div class="col-md-1 mb-2">
                    <button type="submit" class="btn btn-sm ped-btn btn-block" title="Aplicar filtros">
                        <i class="fas fa-filter"></i>
                    </button>
                </div>
                @if ($paramsFiltros !== [])
                    <div class="col-12 text-right">
                        <a href="{{ route('admin.pedidos.index', ['pestana' => $pestanaActiva]) }}"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-times mr-1"></i> Limpiar filtros
                        </a>
                    </div>
                @endif
            </form>
        </div>
    </div>

    <!-- Listado de pedidos -->
    <div class="card ped-card">
        <div class="card-header">
            <h3 class="card-title font-weight-bold d-flex align-items-center">
                <i class="fas {{ $pestanaActual['icono'] }} mr-1" style="color: {{ $pestanaActual['color'] }};"></i>
                {{ $pestanaActual['tituloLista'] }}
            </h3>
            <div class="card-tools">
                <span class="badge badge-secondary">
                    {{ $pedidos->total() }} {{ $pedidos->total() == 1 ? 'pedido' : 'pedidos' }}
                </span>
            </div>
        </div>
        <div class="card-body table-responsive p-0">
            <table class="table table-hover table-bordered align-middle" style="margin-bottom: 0;">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Entrega</th>
                        <th>Responsable</th>
                        <th class="text-center">Ítems</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Detalle</th>
                        <th class="text-center">Estado</th>
                        <th class="text-center" style="width: 250px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $pedido)
                        <tr class="{{ $pedidoCliente && $pedidoCliente->id === $pedido->id ? 'table-active' : '' }}">
                            <td class="font-weight-bold text-nowrap" style="color: #6c3461;">
                                {{ $pedido->codigo }}
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $pedido->cliente_nombre_completo }}</div>
                                <div class="small text-muted">
                                    <i class="fas fa-phone-alt mr-1"></i>{{ $pedido->cliente_telefono ?: 'Sin teléfono' }}
                                    @if ($pedido->cliente_nit)
                                        <br><i class="fas fa-id-card mr-1"></i>CI/NIT: {{ $pedido->cliente_nit }}
                                    @endif
                                    @if ($pedido->cliente_direccion)
                                        <br><i class="fas fa-map-marker-alt mr-1"></i>{{ $pedido->cliente_direccion }}
                                    @endif
                                </div>
                            </td>
                           
<td class="text-nowrap">
                                <i class="fas fa-calendar mr-1 text-muted"></i>
                                {{ $pedido->fecha_entrega->format('d/m/Y') }}
                                <div class="small text-muted">
                                    <i class="fas fa-clock mr-1"></i>
                                    {{ \Illuminate\Support\Carbon::parse($pedido->hora_entrega ?: '09:00')->format('H:i') }}
                                </div>
                            </td>

                            <td class="small">{{ $pedido->user?->name ?? 'Sin asignar' }}</td>
                            <td class="text-center">
                                <span class="badge badge-light border text-dark">
                                    {{ $pedido->items->count() }}
                                </span>
                            </td>
                            <td class="text-end font-weight-bold text-nowrap">
                                Bs. {{ number_format($pedido->total_estimado, 2) }}
                            </td>
                            {{-- COLUMNA DETALLES ACTUALIZADA --}}
            <td class="text-left align-middle">
                @if ($pedido->items && $pedido->items->isNotEmpty())
                    <ul class="list-unstyled mb-0 small">
                        @foreach ($pedido->items as $item)
                            <li>
                                <i class="fas fa-box text-muted mr-1"></i>
                                <strong>{{ $item->cantidad }}x</strong> {{ $item->nombre_producto ?? $item->producto->nombre ?? 'Producto' }}
                                @if (!empty($item->observacion))
                                    <small class="text-muted d-block pl-3"><em>({{ $item->observacion }})</em></small>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @else
                    <span class="text-muted small">Sin detalles</span>
                @endif
            </td>
                            <td class="text-center">
                                <span class="badge badge-{{ $pedido->estado_color }}">
                                    {{ $pedido->estado_label }}
                                </span>
                            </td>
                            <td class="text-center text-nowrap">
                                <a href="{{ route('admin.pedidos.index', array_merge(request()->query(), ['pedido' => $pedido->id])) }}"
                                    class="btn btn-sm btn-outline-secondary"
                                    title="Ver este pedido en el apartado 'Pedido del cliente'">
                                    <i class="fas fa-shopping-basket"></i>
                                </a>

                                <a href="{{ route('admin.pedidos.show', $pedido) }}"
                                    class="btn btn-sm btn-outline-primary" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>

                                @if (auth()->user()->esAdministrador() || (int) $pedido->user_id === (int) auth()->id())
                                    @can('admin.pedidos.editar')
                                        <a href="{{ route('admin.pedidos.edit', $pedido) }}" class="btn btn-sm btn-warning"
                                            title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endcan
                                    @if ($pedido->estado !== \App\Models\Pedido::ESTADO_CANCELADO)
                                        @can('admin.pedidos.anular')
                                            <form action="{{ route('admin.pedidos.cancel', $pedido) }}" method="POST"
                                                style="display: inline-block;"
                                                data-confirm-title="Cancelar pedido"
                                                data-confirm-message="¿Cancelar el pedido {{ $pedido->codigo }}? Se conservará en el historial."
                                                data-confirm-accept="Cancelar pedido"
                                                data-confirm-destructive="true">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    title="Cancelar pedido">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2" style="color: #ebd7e4;"></i>
                                No hay pedidos que coincidan con los filtros seleccionados.
                            </td>
                        </tr>
                        
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ $pedidos->links() }}
        </div>
    </div>

    @if ($pedidoCliente)
        {{-- Apartado "Pedido del cliente": la ficha del pedido elegido con ?pedido=ID --}}
        <div class="card ped-card" id="pedido-del-cliente">
            <div class="card-header">
                <h3 class="card-title font-weight-bold d-flex align-items-center">
                    <i class="fas fa-shopping-basket mr-1" style="color: #6c3461;"></i>
                    Pedido del cliente
                </h3>
                <div class="card-tools">
                    <span class="badge badge-{{ $pedidoCliente->estado_color }}">
                        {{ $pedidoCliente->estado_label }}
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <div class="font-weight-bold text-dark">
                            <i class="fas fa-user mr-1 text-muted"></i>
                            {{ $pedidoCliente->cliente_nombre_completo }}
                        </div>
                        <div class="small text-muted mt-1">
                            @if ($pedidoCliente->cliente_telefono)
                                <div><i class="fas fa-phone-alt mr-1"></i>{{ $pedidoCliente->cliente_telefono }}</div>
                            @endif
                            @if ($pedidoCliente->cliente_direccion)
                                <div><i class="fas fa-map-marker-alt mr-1"></i>{{ $pedidoCliente->cliente_direccion }}</div>
                            @endif
                            @if ($pedidoCliente->cliente_nit)
                                <div><i class="fas fa-id-card mr-1"></i>NIT: {{ $pedidoCliente->cliente_nit }}</div>
                            @endif
                            <div>
                                <i class="fas fa-calendar mr-1"></i>
                                Entrega: {{ $pedidoCliente->fecha_entrega->format('d/m/Y') }}
                                {{ \Illuminate\Support\Carbon::parse($pedidoCliente->hora_entrega ?: '09:00')->format('H:i') }}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-5">
                        @if ($pedidoCliente->detalles)
                            <div class="bg-light border rounded p-2 mb-0">
                                <div class="small font-weight-bold text-dark">
                                    <i class="fas fa-sticky-note mr-1 text-muted"></i>Detalles del pedido
                                </div>
                                <div class="small text-muted">{{ $pedidoCliente->detalles }}</div>
                            </div>
                        @endif
                    </div>
                </div>

                <table class="table table-sm table-bordered mt-3 mb-0">
                    <thead>
                        <tr>
                            <th class="text-center">Cant.</th>
                            <th>Producto</th>
                            <th class="text-end">Precio</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pedidoCliente->items as $item)
                            <tr>
                                <td class="text-center">{{ $item->cantidad }}</td>
                                <td>
                                    {{ $item->nombre_producto ?? $item->producto->nombre ?? 'Producto' }}
                                    @if (!empty($item->observacion))
                                        <small class="text-muted d-block"><em>({{ $item->observacion }})</em></small>
                                    @endif
                                </td>
                                <td class="text-end">Bs. {{ number_format($item->precio, 2) }}</td>
                                <td class="text-end font-weight-bold">Bs. {{ number_format($item->subtotal, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted">Sin ítems en este pedido.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end">Bs. {{ number_format($pedidoCliente->total_estimado, 2) }}</th>
                        </tr>
                        <tr>
                            <th colspan="3" class="text-end">Abono</th>
                            <th class="text-end">Bs. {{ number_format($pedidoCliente->abono, 2) }}</th>
                        </tr>
                        <tr class="table-active">
                            <th colspan="3" class="text-end">Saldo pendiente</th>
                            <th class="text-end">Bs. {{ number_format($pedidoCliente->saldo_pendiente, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif
@stop
