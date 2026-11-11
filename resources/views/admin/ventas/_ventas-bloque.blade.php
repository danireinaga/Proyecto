{{-- Tabla de ventas de la pestaña activa del módulo Ventas.
     Espera: $ventas, $titulo, $descripcion, $descripcionVacia, $icono, $color,
             $total, $clase, $formasPago --}}
<div class="card ventas-card {{ $clase }} mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 8px;">
            <h3 class="card-title font-weight-bold d-flex align-items-center mb-0">
                <span class="bloque-icono mr-2" style="background: {{ $color }};">
                    <i class="fas {{ $icono }}"></i>
                </span>
                {{ $titulo }}
            </h3>
            <div class="d-flex align-items-center" style="gap: 8px;">
                <span class="badge badge-light border text-dark">
                    {{ $ventas->total() }} {{ $ventas->total() == 1 ? 'venta' : 'ventas' }}
                </span>
                <span class="badge text-white" style="background: {{ $color }};">
                    Bs. {{ number_format($total, 2) }}
                </span>
            </div>
        </div>
        <small class="text-muted d-block mt-1">{{ $descripcion }}</small>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-bordered align-middle" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th>Ficha</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Cobró</th>
                    <th>Forma de pago</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Ver ficha</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($ventas as $venta)
                    <tr class="{{ $venta->estado === \App\Models\Order::ESTADO_CANCELADO ? 'table-danger' : '' }}">
                        <td class="font-weight-bold text-nowrap" style="color: #6c3461;">
                            {{ $venta->numero_ficha }}
                            <div class="small text-muted">#{{ $venta->id }}</div>
                        </td>
                        <td>
                            <div class="font-weight-bold">{{ $venta->cliente_nombre_completo }}</div>
                            @if ($venta->cliente_telefono)
                                <div class="small text-muted">
                                    <i class="fas fa-phone-alt mr-1"></i>{{ $venta->cliente_telefono }}
                                </div>
                            @endif
                            @if ($venta->cliente_nit)
                                <div class="small text-muted">
                                    <i class="fas fa-id-card mr-1"></i>CI/NIT: {{ $venta->cliente_nit }}
                                </div>
                            @endif
                        </td>
                        <td class="text-nowrap small">
                            <i class="fas fa-calendar mr-1 text-muted"></i>
                            {{ $venta->created_at->format('d/m/Y') }}
                            <div class="text-muted">
                                <i class="fas fa-clock mr-1"></i>{{ $venta->created_at->format('H:i') }}
                            </div>
                        </td>
                        <td class="small">{{ $venta->user?->name ?? 'Sin asignar' }}</td>
                        <td class="text-nowrap small">
                            {{ $formasPago[$venta->pago_tipo] ?? ucfirst((string) $venta->pago_tipo) }}
                            @if ((float) $venta->pago_vuelto > 0)
                                <div class="text-muted">Vuelto Bs. {{ number_format((float) $venta->pago_vuelto, 2) }}</div>
                            @endif
                        </td>
                        <td class="text-end font-weight-bold text-nowrap">
                            Bs. {{ number_format((float) $venta->total_final, 2) }}
                            @if ($venta->estado === \App\Models\Order::ESTADO_CANCELADO)
                                <span class="badge badge-danger">Anulada</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @can('admin.ventas.ver')
                                <a href="{{ route('admin.caja.ficha', $venta) }}" target="_blank" rel="noopener"
                                    class="btn btn-sm btn-outline-primary text-nowrap" title="Ver e imprimir ficha">
                                    <i class="fas fa-print mr-1"></i>{{ $venta->numero_ficha ?: 'Ficha #'.$venta->id }}
                                </a>
                            @else
                                <span class="text-nowrap" title="No tienes acceso a imprimir la ficha">
                                    {{ $venta->numero_ficha ?: 'Ficha #'.$venta->id }}
                                </span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-receipt fa-2x d-block mb-2" style="color: #ebd7e4;"></i>
                            {{ $descripcionVacia ?? 'Sin ventas registradas.' }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($ventas->hasPages())
        <div class="card-footer clearfix">
            {{ $ventas->links() }}
        </div>
    @endif
</div>
