<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reporte de {{ $type }} · Azúcar &amp; Yo</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 24px; color: #28313a; font: 12px/1.45 Arial, sans-serif; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #6c3461; padding-bottom: 16px; margin-bottom: 20px; }
        h1 { color: #6c3461; margin: 0 0 4px; font-size: 24px; }
        .muted { color: #66717c; }
        .summary { display: flex; gap: 12px; margin-bottom: 20px; }
        .summary-card { flex: 1; padding: 12px; border: 1px solid #e2e5e8; border-radius: 6px; }
        .summary-card strong { display: block; margin-top: 5px; font-size: 17px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px 7px; border-bottom: 1px solid #e4e7ea; text-align: left; vertical-align: top; }
        th { background: #f4eff3; color: #57304f; }
        td.amount, th.amount { text-align: right; white-space: nowrap; }
        .toolbar { margin-bottom: 16px; }
        .toolbar button { border: 0; background: #6c3461; color: white; padding: 10px 16px; border-radius: 4px; cursor: pointer; }
        @media print { body { padding: 0; } .toolbar { display: none; } .summary-card { break-inside: avoid; } tr { break-inside: avoid; } }
        @media screen and (max-width: 700px) { body { padding: 12px; } .summary { flex-direction: column; } .table-wrap { overflow-x: auto; } }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button></div>
    <header class="header">
        <div><h1>Azúcar &amp; Yo</h1><div class="muted">Reporte de {{ $type === 'ventas' ? 'ventas' : 'pedidos' }}</div></div>
        <div class="muted">Periodo: {{ $from->format('d/m/Y') }} — {{ $to->format('d/m/Y') }}<br>Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </header>
    <section class="summary">
        <div class="summary-card">Registros encontrados<strong>{{ number_format($summary['count']) }}</strong></div>
        <div class="summary-card">{{ $type === 'ventas' ? 'Importe de ventas completadas' : 'Importe estimado de pedidos' }}<strong>Bs. {{ number_format($summary['amount'], 2, ',', '.') }}</strong></div>
    </section>
    @if ($summary['count'] > $rows->count())
        <p class="muted">Se imprimen los primeros {{ number_format($rows->count()) }} registros por límite de impresión. Use Excel para exportar el conjunto completo.</p>
    @endif
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>{{ $type === 'ventas' ? 'Ficha' : 'Código' }}</th>
                    <th>Cliente</th>
                    <th>CI / NIT</th>
                    <th>{{ $type === 'ventas' ? 'Fecha de venta' : 'Fecha de entrega' }}</th>
                    <th>Estado</th>
                    <th>Responsable</th>
                    <th class="amount">Total (Bs.)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                    <tr>
                        <td>{{ $type === 'ventas' ? ($row->numero_ficha ?: '—') : $row->codigo }}</td>
                        <td>{{ $row->cliente_nombre_completo }}</td>
                        <td>{{ $row->cliente_nit ?: '—' }}</td>
                        <td>{{ $type === 'ventas' ? $row->created_at->format('d/m/Y H:i') : $row->fecha_entrega->format('d/m/Y').' '.($row->hora_entrega ?: '') }}</td>
                        <td>{{ $type === 'ventas' ? ucfirst($row->estado) : $row->estado_label }}</td>
                        <td>{{ $row->user->name ?? 'Sin responsable' }}</td>
                        <td class="amount">{{ number_format((float) ($type === 'ventas' ? $row->total_final : $row->total_estimado), 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No hay registros en este periodo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
