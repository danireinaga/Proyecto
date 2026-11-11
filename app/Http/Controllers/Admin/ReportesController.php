<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Pedido;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:admin.reportes.ver');
    }

    public function index(Request $request): \Illuminate\Contracts\View\View
    {
        $user = Auth::user();
        $filters = $this->validatedFilters($request, $user);
        $this->authorizeReportType($user, $filters['tipo']);
        [$from, $to] = $this->dateRange($filters);
        $supervisor = $user->esSupervisor();
        $salesQuery = $this->salesQuery($from, $to, $filters, $user, $supervisor);
        $ordersQuery = $this->ordersQuery($from, $to, $filters, $user, $supervisor);

        $sales = [
            'count' => (clone $salesQuery)->count(),
            'total' => (float) (clone $salesQuery)->where('estado', Order::ESTADO_COMPLETADO)->sum('total_final'),
            'cancelled' => (clone $salesQuery)->where('estado', Order::ESTADO_CANCELADO)->count(),
            'average' => (float) (clone $salesQuery)->where('estado', Order::ESTADO_COMPLETADO)->avg('total_final'),
        ];
        $orders = [
            'count' => (clone $ordersQuery)->count(),
            'active' => (clone $ordersQuery)->whereIn('estado', Pedido::ESTADOS_ACTIVOS)->count(),
            'delivered' => (clone $ordersQuery)->where('estado', Pedido::ESTADO_ENTREGADO)->count(),
            'cancelled' => (clone $ordersQuery)->where('estado', Pedido::ESTADO_CANCELADO)->count(),
        ];

        $productQuery = DB::table('venta_items as items')
            ->join('ventas', 'ventas.id', '=', 'items.order_id')
            ->where('ventas.estado', Order::ESTADO_COMPLETADO)
            ->whereBetween('ventas.created_at', [$from, $to]);
        $this->applySalesScope($productQuery, $filters, $user, $supervisor);
        $products = $productQuery
            ->selectRaw('items.nombre_producto as name, SUM(items.cantidad) as units, SUM(items.precio * items.cantidad) as amount')
            ->groupBy('items.nombre_producto')
            ->orderByDesc('units')
            ->orderBy('items.nombre_producto')
            ->limit(10)
            ->get();

        $dailySalesQuery = $this->salesQuery($from, $to, $filters, $user, $supervisor)
            ->where('estado', Order::ESTADO_COMPLETADO);
        $dailySales = $dailySalesQuery
            ->selectRaw('DATE(created_at) as day, SUM(total_final) as amount')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $type = $filters['tipo'];
        $rowsQuery = $type === 'pedidos'
            ? $ordersQuery->with('user')->orderByDesc('fecha_entrega')->orderByDesc('id')
            : $salesQuery->with('user')->orderByDesc('created_at')->orderByDesc('id');
        $rows = $rowsQuery->paginate(25)->withQueryString();
        $employees = $supervisor
            ? User::query()
                ->orderBy('name')
                ->get(['id', 'name'])
            : collect();

        return view('admin.reportes.index', [
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'orders' => $orders,
            'products' => $products,
            'dailySales' => $dailySales,
            'rows' => $rows,
            'employees' => $employees,
            'type' => $type,
            'supervisor' => $supervisor,
            'printUrl' => route('admin.reportes.imprimir', $filters),
            'excelUrl' => route('admin.reportes.excel', $filters),
        ]);
    }

    public function excel(Request $request): StreamedResponse
    {
        $user = Auth::user();
        $filters = $this->validatedFilters($request, $user);
        $this->authorizeReportType($user, $filters['tipo']);
        [$from, $to] = $this->dateRange($filters);
        $query = $filters['tipo'] === 'pedidos'
            ? $this->ordersQuery($from, $to, $filters, $user, $user->esSupervisor())
                ->leftJoin('users as responsible', 'responsible.id', '=', 'pedidos.user_id')
                ->select([
                    'pedidos.codigo as reference',
                    'pedidos.cliente_nombre as first_name',
                    'pedidos.cliente_apellido as last_name',
                    'pedidos.cliente_nit as document',
                    'pedidos.fecha_entrega as date',
                    'pedidos.hora_entrega as time',
                    'pedidos.estado as status',
                    'pedidos.total_estimado as amount',
                    'responsible.name as responsible',
                ])
                ->orderByDesc('pedidos.fecha_entrega')
                ->orderByDesc('pedidos.id')
            : $this->salesQuery($from, $to, $filters, $user, $user->esSupervisor())
                ->leftJoin('users as responsible', 'responsible.id', '=', 'ventas.user_id')
                ->select([
                    'ventas.numero_ficha as reference',
                    'ventas.cliente_nombre as first_name',
                    'ventas.cliente_apellido as last_name',
                    'ventas.cliente_nit as document',
                    'ventas.created_at as date',
                    'ventas.pago_tipo as time',
                    'ventas.estado as status',
                    'ventas.total_final as amount',
                    'responsible.name as responsible',
                ])
                ->orderByDesc('ventas.created_at')
                ->orderByDesc('ventas.id');

        $filename = 'azucar-y-yo-'.$filters['tipo'].'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.csv';

        return response()->streamDownload(function () use ($query, $filters): void {
            $output = fopen('php://output', 'wb');
            if ($output === false) {
                throw new \RuntimeException('No se pudo abrir la salida de exportación.');
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'Código / ficha',
                'Cliente',
                'CI / NIT',
                $filters['tipo'] === 'pedidos' ? 'Fecha de entrega' : 'Fecha de venta',
                $filters['tipo'] === 'pedidos' ? 'Hora' : 'Forma de pago',
                'Estado',
                'Total (Bs.)',
                'Responsable',
            ], ';', '"', '\\');

            foreach ($query->cursor() as $row) {
                fputcsv($output, [
                    $this->safeSpreadsheetCell($row->reference),
                    $this->safeSpreadsheetCell(trim(($row->first_name ?? '').' '.($row->last_name ?? ''))),
                    $this->safeSpreadsheetCell($row->document),
                    $this->safeSpreadsheetCell($row->date),
                    $this->safeSpreadsheetCell($row->time),
                    $this->safeSpreadsheetCell($row->status),
                    number_format((float) $row->amount, 2, '.', ''),
                    $this->safeSpreadsheetCell($row->responsible),
                ], ';', '"', '\\');
            }

            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function imprimir(Request $request): \Illuminate\Contracts\View\View
    {
        $user = Auth::user();
        $filters = $this->validatedFilters($request, $user);
        $this->authorizeReportType($user, $filters['tipo']);
        [$from, $to] = $this->dateRange($filters);
        $type = $filters['tipo'];
        $query = $type === 'pedidos'
            ? $this->ordersQuery($from, $to, $filters, $user, $user->esSupervisor())
                ->with('user')->orderByDesc('fecha_entrega')->orderByDesc('id')
            : $this->salesQuery($from, $to, $filters, $user, $user->esSupervisor())
                ->with('user')->orderByDesc('created_at')->orderByDesc('id');

        return view('admin.reportes.print', [
            'filters' => $filters,
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'rows' => $query->limit(1000)->get(),
            'summary' => $type === 'pedidos'
                ? [
                    'count' => (clone $query)->count(),
                    'amount' => (float) (clone $query)->sum('total_estimado'),
                ]
                : [
                    'count' => (clone $query)->count(),
                    'amount' => (float) (clone $query)->where('estado', Order::ESTADO_COMPLETADO)->sum('total_final'),
                ],
        ]);
    }

    private function validatedFilters(Request $request, User $user): array
    {
        $defaultType = $user->can('admin.ventas.ver') ? 'ventas' : 'pedidos';
        $requestedType = $request->input('tipo', $defaultType);
        $allowedStatuses = $requestedType === 'pedidos'
            ? array_keys(Pedido::ESTADOS)
            : [Order::ESTADO_COMPLETADO, Order::ESTADO_CANCELADO];
        $validated = $request->validate([
            'tipo' => ['nullable', 'in:ventas,pedidos'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'empleado_id' => ['nullable', 'integer', 'exists:users,id'],
            'estado' => ['nullable', Rule::in($allowedStatuses)],
        ]);

        return [
            'tipo' => $validated['tipo'] ?? ($user->can('admin.ventas.ver') ? 'ventas' : 'pedidos'),
            'desde' => $validated['desde'] ?? today()->startOfMonth()->toDateString(),
            'hasta' => $validated['hasta'] ?? today()->toDateString(),
            'empleado_id' => $validated['empleado_id'] ?? null,
            'estado' => $validated['estado'] ?? null,
        ];
    }

    private function authorizeReportType(User $user, string $type): void
    {
        abort_unless(
            $user->can($type === 'pedidos' ? 'admin.pedidos.ver' : 'admin.ventas.ver'),
            403
        );
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dateRange(array $filters): array
    {
        return [
            Carbon::createFromFormat('Y-m-d', $filters['desde'])->startOfDay(),
            Carbon::createFromFormat('Y-m-d', $filters['hasta'])->endOfDay(),
        ];
    }

    private function salesQuery(Carbon $from, Carbon $to, array $filters, User $user, bool $supervisor): EloquentBuilder
    {
        $query = Order::query()
            ->whereBetween('ventas.created_at', [$from, $to]);
        $this->applySalesScope($query, $filters, $user, $supervisor);

        if ($filters['estado']) {
            $query->where('estado', $filters['estado']);
        }

        return $query;
    }

    private function ordersQuery(Carbon $from, Carbon $to, array $filters, User $user, bool $supervisor): EloquentBuilder
    {
        $query = Pedido::query()
            ->whereDate('fecha_entrega', '>=', $from->toDateString())
            ->whereDate('fecha_entrega', '<=', $to->toDateString());

        if (! $supervisor) {
            $query->where('user_id', $user->id);
        } elseif ($filters['empleado_id']) {
            $query->where('user_id', $filters['empleado_id']);
        }

        if ($filters['estado']) {
            $query->where('estado', $filters['estado']);
        }

        return $query;
    }

    private function applySalesScope(EloquentBuilder|QueryBuilder $query, array $filters, User $user, bool $supervisor): void
    {
        if (! $supervisor) {
            $query->where('user_id', $user->id);
        } elseif ($filters['empleado_id']) {
            $query->where('user_id', $filters['empleado_id']);
        }
    }

    private function safeSpreadsheetCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');

        return preg_match('/^[\s]*[=+\-@]/u', $cell) ? "'".$cell : $cell;
    }
}
