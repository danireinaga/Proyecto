<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSaleRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class CajaController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:admin.caja.ver')->except('ficha');
    }

    public function index()
    {
        $productos = Product::with('category')
            ->disponibleEnMenuDigital()
            ->orderBy('category_id')
            ->orderBy('nombre')
            ->get();

        $categorias = Category::query()
            ->whereHas('products', fn ($query) => $query->disponibleEnMenuDigital())
            ->orderBy('nombre')
            ->get();

        return view('admin.caja.index', [
            'productos' => $productos,
            'categorias' => $categorias,
            'formasPago' => Order::PAGOS,
            'formasPagoLabels' => Order::PAGOS_LABELS,
        ]);
    }

    /* ---------------------------------------------------------------------
     | Cobro de una venta
     |--------------------------------------------------------------------*/

    public function store(StoreSaleRequest $request)
    {
        try {
            $orden = DB::transaction(function () use ($request) {
                $cajero = User::query()
                    ->lockForUpdate()
                    ->findOrFail(Auth::id());

                // 1. Bloqueamos los productos del catálogo para que dos cajeros
                //    no vendan el mismo stock al mismo tiempo.
                $ids = collect($request->input('items'))
                    ->pluck('id')
                    ->filter()
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $productos = Product::whereIn('id', $ids)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($request->input('items') as $item) {
                    if (blank($item['id'] ?? null)) {
                        continue; // ítem personalizado, no descuenta stock
                    }

                    $producto = $productos->get((int) $item['id']);

                    if (! $producto) {
                        throw new \DomainException(
                            'El producto "'.$item['nombre'].'" ya no existe en el catálogo.'
                        );
                    }

                    if (! $producto->en_menu_digital) {
                        throw new \DomainException(
                            'El producto "'.$producto->nombre.'" no está disponible en Caja.'
                        );
                    }

                    if ((int) $producto->stock < (int) $item['cantidad']) {
                        throw new \DomainException(
                            'Stock insuficiente de "'.$producto->nombre.'". '
                            .'Disponible: '.$producto->stock.' | Solicitado: '.$item['cantidad'].'.'
                        );
                    }
                }

                // 2. Totales calculados en el servidor
                $subtotal = $request->subtotal();
                $descuento = min((float) $request->input('descuento', 0), $subtotal);
                $propina = (float) $request->input('propina', 0);
                $totalFinal = max(0, round($subtotal - $descuento + $propina, 2));

                $pagoTipo = $request->input('pago_tipo');
                $recibido = (float) $request->input('pago_recibido', 0);

                // En efectivo el vuelto solo aplica si el cajero entregó efectivo
                $vuelto = $pagoTipo === 'efectivo' ? max(0, round($recibido - $totalFinal, 2)) : 0.0;

                if ($pagoTipo === 'efectivo' && $recibido > 0 && $recibido < $totalFinal) {
                    throw new \DomainException(
                        'El efectivo recibido (Bs. '.number_format($recibido, 2)
                        .') es menor al total (Bs. '.number_format($totalFinal, 2).').'
                    );
                }

                // 3. Cabecera de la venta
                $orden = Order::create([
                    'user_id' => $cajero->id,
                    'cliente_nombre' => $request->input('cliente_nombre'),
                    'cliente_apellido' => $request->input('cliente_apellido'),
                    'cliente_telefono' => $request->input('cliente_telefono'),
                    'cliente_nit' => $request->input('cliente_nit'),
                    'cliente_direccion' => $request->input('cliente_direccion'),
                    'total' => $subtotal,
                    'descuento' => $descuento,
                    'propina' => $propina,
                    'total_final' => $totalFinal,
                    'pago_tipo' => $pagoTipo,
                    'pago_recibido' => $recibido,
                    'pago_vuelto' => $vuelto,
                    'estado' => Order::ESTADO_COMPLETADO,
                    'origen' => 'caja',
                    'numero_ficha' => Order::siguienteNumeroFicha($cajero->id),
                    'notas' => $request->input('notas'),
                ]);

                // 4. Detalle + descuento de stock
                foreach ($request->input('items') as $item) {
                    OrderItem::create([
                        'order_id' => $orden->id,
                        'product_id' => ! empty($item['id']) ? (int) $item['id'] : null,
                        'nombre_producto' => $item['nombre'],
                        'precio' => (float) $item['precio'],
                        'cantidad' => (int) $item['cantidad'],
                        'observacion' => $item['note'] ?? null,
                    ]);

                    if (! empty($item['id'])) {
                        $producto = $productos->get((int) $item['id']);
                        $producto->stock = max(0, (int) $producto->stock - (int) $item['cantidad']);
                        $producto->save();
                    }
                }

                return $orden;
            });
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo registrar la venta. Revisa la consola del servidor.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => '¡Venta registrada! Ficha N.º '.$orden->numero_ficha,
            'order_id' => $orden->id,
            'numero_ficha' => $orden->numero_ficha,
            'ficha_url' => route('admin.caja.ficha', $orden),
            'total_final' => (float) $orden->total_final,
            'pago_vuelto' => (float) $orden->pago_vuelto,
        ]);
    }

    /* ---------------------------------------------------------------------
     | Ficha imprimible 5 x 7 cm
     |--------------------------------------------------------------------*/

    public function ficha(Order $orden)
    {
        $usuario = Auth::user();
        abort_unless(
            $usuario->can('admin.caja.ver') || $usuario->can('admin.ventas.ver'),
            403,
            'No tiene permiso para ver esta ficha.'
        );

        $this->autorizarLectura($orden);

        $orden->load('items', 'user');

        return view('admin.caja.ficha', compact('orden'));
    }

    /* ---------------------------------------------------------------------
     | Listados
     |--------------------------------------------------------------------*/

    public function misVentas(Request $request)
    {
        $query = Order::with(['items', 'user'])
            ->where('user_id', Auth::id())
            ->orderBy('id');

        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->input('fecha'));
        }

        $orders = $query->paginate(10)->withQueryString();

        // Mismo filtro para los indicadores de cabecera
        $base = fn () => Order::query()
            ->where('user_id', Auth::id())
            ->when($request->filled('fecha'), fn ($q) => $q->whereDate('created_at', $request->input('fecha')));

        $hoyEfectivo = (clone $base())->deHoy()->completados()->where('pago_tipo', 'efectivo');

        $resumen = [
            'totalHoy' => (clone $base())->deHoy()->completados()->sum('total_final'),
            'fichasHoy' => (clone $base())->deHoy()->completados()->count(),
            'cajaEfectivo' => round(
                (clone $hoyEfectivo)->sum('pago_recibido') - (clone $hoyEfectivo)->sum('pago_vuelto'),
                2
            ),
        ];

        return view('admin.caja.mis-ventas', compact('orders') + $resumen + [
            'filtroFecha' => $request->input('fecha'),
        ]);
    }

    /* ---------------------------------------------------------------------
     | Anulación de ventas (solo supervisor/admin)
     |--------------------------------------------------------------------*/

    public function anular(Order $orden)
    {
        $this->checkPermission('admin.caja.anular');
        $this->autorizarLectura($orden);

        if ($orden->estado === Order::ESTADO_CANCELADO) {
            return back()->with('info', 'Esa venta ya estaba anulada.');
        }

        DB::transaction(function () use ($orden) {
            // Devolvemos el stock de los ítems que tengan producto de catálogo
            foreach ($orden->items()->with('product')->get() as $item) {
                if ($item->product_id) {
                    Product::where('id', $item->product_id)
                        ->increment('stock', (int) $item->cantidad);
                }
            }

            $orden->update([
                'estado' => Order::ESTADO_CANCELADO,
                'notas' => trim(($orden->notas ?? '')."\n[ANULADA] ".now()->format('d/m/Y H:i').' por '.Auth::user()->name),
            ]);
        });

        return back()->with('success', 'Venta #'.$orden->id.' anulada y stock devuelto.');
    }

    /* ---------------------------------------------------------------------
     | Utilidades internas
     |--------------------------------------------------------------------*/

    private function autorizarLectura(Order $orden): void
    {
        abort_unless(Auth::user()->puedeVerVentasDe($orden->user_id), 403, 'No tiene permiso para ver esta venta.');
    }

    private function checkPermission(string $permiso): void
    {
        abort_unless(Auth::user()->can($permiso), 403, 'No tiene permiso para esta acción.');
    }
}