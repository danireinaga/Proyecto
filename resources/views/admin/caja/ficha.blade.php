{{-- Ficha de venta para impresora térmica de 58 mm (5 cm x 7 cm).
     El contenido se ajusta a una sola hoja: si no cabe se compacta y se escala. --}}
@php
    $negocio = config('whatsapp.negocio');
    $ancho = config('whatsapp.ficha.ancho_cm').'cm';
    $alto = config('whatsapp.ficha.alto_cm').'cm';
    $pagoTexto = \App\Models\Order::PAGOS_LABELS[$orden->pago_tipo] ?? ucfirst((string) $orden->pago_tipo);
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width={{ $ancho }}, initial-scale=1.0">
    <title>Ficha {{ $orden->numero_ficha }}</title>
    <style>
        @page {
            size: {{ $ancho }} {{ $alto }};
            /* Sin margen de página: el borde lo aporta el padding interno de
               .ficha. Con margen, la hoja se salía a una segunda página. */
            margin: 0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            width: {{ $ancho }};
            height: {{ $alto }};
            font-family: "Consolas", "Courier New", monospace;
            font-size: 8.4pt;
            line-height: 1.2;
            color: #000;
            background: #fff;
        }

        /* La hoja es el recorte de una sola página: nada puede desbordarla.
           Se le quita 1px al alto para que el redondeo del navegador no
           empuje el contenido a una segunda hoja en blanco. */
        .hoja {
            width: calc({{ $ancho }} - 1px);
            height: calc({{ $alto }} - 1px);
            overflow: hidden;
            background: #fff;
        }

        .ficha {
            width: 100%;
            padding: 0.2cm;
            /* Escalar desde el centro superior deja la ficha centrada en la hoja
               cuando hay que reducirla. */
            transform-origin: top center;
        }

        .centro {
            text-align: center;
        }

        .negrita {
            font-weight: bold;
        }

        .dobled {
            font-weight: bold;
            letter-spacing: 0.5px;
        }

        h1 {
            font-size: 10pt;
            letter-spacing: 0.5px;
        }

        .sub {
            font-size: 7pt;
        }

        .sep {
            border-top: 1px dashed #000;
            margin: 1.5px 0;
        }

        .sep-solido {
            border-top: 1.5px solid #000;
            margin: 1.5px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.8pt;
            table-layout: fixed;
        }

        th {
            text-align: left;
            font-weight: bold;
            padding-bottom: 1px;
        }

        td {
            vertical-align: top;
            padding: 0.4px 0;
            overflow-wrap: break-word;
            word-break: break-word;
        }

        .col-cant {
            width: 13%;
        }

        .col-imp {
            width: 24%;
            text-align: right;
        }

        .obs {
            font-size: 7pt;
            font-style: italic;
            padding-left: 5px;
        }

        .total-bloque {
            font-size: 8.2pt;
        }

        .total-bloque .fila {
            display: flex;
            justify-content: space-between;
        }

        .total {
            font-size: 9.5pt;
            font-weight: bold;
            border-top: 1.5px solid #000;
            border-bottom: 1.5px solid #000;
            padding: 1.5px 0;
            margin: 1.5px 0;
        }

        .pie {
            font-size: 7pt;
        }

        /* Segunda pasada: cuando la lista de productos es larga se aprieta un
           poco más antes de escalar, para no imprimir texto diminuto. */
        .ficha.compacta {
            font-size: 7.6pt;
            line-height: 1.08;
        }

        .ficha.compacta h1 {
            font-size: 9pt;
        }

        .ficha.compacta table {
            font-size: 7.1pt;
        }

        .ficha.compacta td {
            padding: 0.1px 0;
        }

        .ficha.compacta .obs {
            font-size: 6.6pt;
        }

        .ficha.compacta .sep,
        .ficha.compacta .sep-solido {
            margin: 0.5px 0;
        }

        .ficha.compacta .total-bloque {
            font-size: 7.6pt;
        }

        .ficha.compacta .total {
            font-size: 8.8pt;
            padding: 1px 0;
            margin: 1px 0;
        }

        /* Fuera de la impresión escondemos todo */
        @media screen {
            body {
                width: 100%;
                height: auto;
                background: #e9ecef;
                padding: 18px 0;
            }

            .barra-acciones {
                max-width: {{ $ancho }};
                margin: 0 auto 14px;
                display: flex;
                gap: 8px;
                justify-content: center;
            }

            .barra-acciones button {
                font-family: system-ui, sans-serif;
                font-size: 13px;
                padding: 8px 16px;
                border-radius: 8px;
                border: 1px solid #6b3461;
                background: #6b3461;
                color: #fff;
                cursor: pointer;
            }

            .barra-acciones button.sec {
                background: #fff;
                color: #6b3461;
            }

            .hoja {
                margin: 0 auto;
                box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
            }
        }

        @media print {
            .barra-acciones {
                display: none !important;
            }

            body {
                background: #fff;
                padding: 0;
            }

            .hoja {
                margin: 0;
                box-shadow: none;
            }
        }
    </style>
</head>

<body>
    <div class="barra-acciones">
        <button type="button" onclick="window.print()">🖨 Imprimir ficha</button>
        <button type="button" class="sec" onclick="window.close()">Cerrar</button>
    </div>

    <div class="hoja">
        <div class="ficha">
            {{-- Encabezado --}}
            <div class="centro">
                <h1>{{ $negocio['nombre'] }}</h1>
                @if ($negocio['direccion'])
                    <div class="sub">{{ $negocio['direccion'] }}</div>
                @endif
                @if ($negocio['telefono'])
                    <div class="sub">Tel. {{ $negocio['telefono'] }}</div>
                @endif
            </div>

            <div class="sep-solido"></div>

            {{-- Cliente --}}
            <div class="fila">
                <span class="negrita">CLIENTE:</span>
                <span>{{ $orden->cliente_nombre_completo }}</span>
            </div>
            @if ($orden->cliente_telefono)
                <div class="fila">
                    <span class="negrita">TELEFONO:</span>
                    <span>{{ $orden->cliente_telefono }}</span>
                </div>
            @endif
            @if ($orden->cliente_nit)
                <div class="fila">
                    <span class="negrita">NIT/CI:</span>
                    <span>{{ $orden->cliente_nit }}</span>
                </div>
            @endif
            @if ($orden->cliente_direccion)
                <div>
                    <span class="negrita">DIR:</span> {{ $orden->cliente_direccion }}
                </div>
            @endif

            <div class="sep"></div>

            {{-- Datos de la venta --}}
           <div class="fila">
    <span><span class="negrita">FICHA:</span> {{ $orden->numero_ficha }}</span>
    <span>{{ $orden->created_at->timezone('America/La_Paz')->format('d/m/Y H:i') }}</span>
          </div>
            <div class="fila">
                <span><span class="negrita">CAJERO/A:</span> {{ $orden->user?->name ?? '—' }}</span>
                <span>#{{ $orden->id }}</span>
            </div>

            <div class="sep"></div>

            {{-- Detalle de productos --}}
            <table>
                <thead>
                    <tr>
                        <th class="col-cant">CANT</th>
                        <th>DESCRIPCION</th>
                        <th class="col-imp">IMPORTE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($orden->items as $item)
                        <tr>
                            <td>{{ $item->cantidad }}</td>
                            <td>
                                {{ $item->nombre_producto }}
                                @if ($item->observacion)
                                    <div class="obs">* {{ $item->observacion }}</div>
                                @endif
                            </td>
                            <td class="col-imp">{{ number_format($item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="sep"></div>

            {{-- Totales --}}
            <div class="total-bloque">
                <div class="fila">
                    <span>Subtotal</span>
                    <span>{{ number_format((float) $orden->total, 2) }}</span>
                </div>
                @if ((float) $orden->descuento > 0)
                    <div class="fila">
                        <span>Descuento</span>
                        <span>- {{ number_format((float) $orden->descuento, 2) }}</span>
                    </div>
                @endif
                @if ((float) $orden->propina > 0)
                    <div class="fila">
                        <span>Propina</span>
                        <span>+ {{ number_format((float) $orden->propina, 2) }}</span>
                    </div>
                @endif
            </div>

            <div class="total fila">
                <span>TOTAL</span>
                <span>Bs. {{ number_format((float) $orden->total_final, 2) }}</span>
            </div>

            {{-- Cobro --}}
            <div class="total-bloque">
                <div class="fila">
                    <span>PAGO</span>
                    <span class="negrita">{{ strtoupper($pagoTexto) }}</span>
                </div>
                @if ($orden->pago_tipo === 'efectivo')
                    <div class="fila">
                        <span>Recibido</span>
                        <span>Bs. {{ number_format((float) $orden->pago_recibido, 2) }}</span>
                    </div>
                

            @if ($orden->notas)
                <div class="sep"></div>
                <div class="pie"><span class="negrita">NOTA:</span> {{ $orden->notas }}</div>
            @endif

            <div class="sep"></div>

            {{-- Pie --}}
            <div class="centro pie">
                <div class="dobled">¡GRACIAS POR SU COMPRA!</div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            var hoja = document.querySelector('.hoja');
            var ficha = document.querySelector('.ficha');

            if (!hoja || !ficha) {
                return;
            }

            /* Ajusta el contenido a una sola hoja: primero se compacta y, si
               todavía no cabe, se escala hasta llenar el alto disponible. */
            function ajustar() {
                ficha.classList.remove('compacta');
                ficha.style.transform = 'scale(1)';

                // La hoja tiene alto fijo, así que su alto útil es el mismo en
                // pantalla y en impresión.
                var disponible = hoja.clientHeight;
                var natural = ficha.scrollHeight;

                if (!disponible || !natural) {
                    return;
                }

                if (natural > disponible) {
                    ficha.classList.add('compacta');
                    natural = ficha.scrollHeight;
                }

                var escala = Math.min(1, disponible / natural);

                if (escala > 0 && escala < 1) {
                    ficha.style.transform = 'scale(' + escala + ')';
                } else {
                    ficha.style.transform = 'scale(1)';
                }
            }

            window.addEventListener('load', function () {
                ajustar();

                // Se imprime sola al abrir la ficha desde el botón "Cobrar e imprimir"
                if (new URLSearchParams(window.location.search).get('auto') === '1') {
                    setTimeout(function () {
                        window.print();
                    }, 250);
                }
            });

            // Al reimprimir a mano el ajuste se recalcula con la hoja ya armada.
            window.addEventListener('beforeprint', ajustar);
        })();
    </script>
</body>

</html>
