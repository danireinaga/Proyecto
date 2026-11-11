<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Pedido;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): \Illuminate\Contracts\View\View
    {
        $user = Auth::user();
        $canSeeOrders = $user->can('admin.pedidos.ver');
        $canSeeSales = $user->can('admin.ventas.ver');
        $canSeeProducts = $user->can('admin.productos.ver') || $user->can('admin.catalogo.ver');
        $supervisor = $user->esSupervisor();
        $start = today()->subDays(6)->startOfDay();
        $end = today()->endOfDay();

        $salesQuery = Order::query()
            ->where('estado', Order::ESTADO_COMPLETADO)
            ->whereBetween('created_at', [$start, $end]);
        if (! $supervisor) {
            $salesQuery->where('user_id', $user->id);
        }

        $sales = $canSeeSales
            ? (clone $salesQuery)->selectRaw('COUNT(*) as count, COALESCE(SUM(total_final), 0) as amount')->first()
            : null;

        $salesByDay = [];
        if ($canSeeSales) {
            $daily = (clone $salesQuery)
                ->selectRaw('DATE(created_at) as day, COALESCE(SUM(total_final), 0) as amount')
                ->groupBy('day')
                ->pluck('amount', 'day');

            foreach (CarbonPeriod::create($start->toDateString(), $end->toDateString()) as $day) {
                $salesByDay[] = [
                    'label' => $day->format('d/m'),
                    'amount' => (float) ($daily[$day->toDateString()] ?? 0),
                ];
            }
        }

        $topProducts = collect();
        if ($canSeeSales) {
            $productsQuery = DB::table('venta_items as items')
                ->join('ventas', 'ventas.id', '=', 'items.order_id')
                ->where('ventas.estado', Order::ESTADO_COMPLETADO)
                ->whereBetween('ventas.created_at', [$start, $end]);
            $this->scopeSalesQuery($productsQuery, $supervisor ? null : $user->id);
            $topProducts = $productsQuery
                ->selectRaw('items.nombre_producto, SUM(items.cantidad) as units')
                ->groupBy('items.nombre_producto')
                ->orderByDesc('units')
                ->orderBy('items.nombre_producto')
                ->limit(5)
                ->get();
        }

        $orders = null;
        $upcomingOrders = collect();
        if ($canSeeOrders) {
            $orderQuery = Pedido::query()
                ->whereIn('estado', Pedido::ESTADOS_ACTIVOS);
            $this->scopeOrdersQuery($orderQuery, $user, $supervisor);

            $orders = [
                'active' => (clone $orderQuery)->count(),
                'today' => (clone $orderQuery)->whereDate('fecha_entrega', today())->count(),
                'late' => (clone $orderQuery)->whereDate('fecha_entrega', '<', today())->count(),
            ];

            $upcomingOrders = (clone $orderQuery)
                ->with('user')
                ->whereDate('fecha_entrega', '>=', today())
                ->orderBy('fecha_entrega')
                ->orderBy('hora_entrega')
                ->limit(6)
                ->get();
        }

        $lowStock = $canSeeProducts
            ? Product::query()->activos()->where('stock', '<=', 5)->orderBy('stock')->orderBy('nombre')->limit(5)->get()
            : collect();

        return view('admin.dashboard', [
            'canSeeSales' => $canSeeSales,
            'canSeeOrders' => $canSeeOrders,
            'canSeeProducts' => $canSeeProducts,
            'sales' => $sales,
            'salesByDay' => $salesByDay,
            'topProducts' => $topProducts,
            'orders' => $orders,
            'upcomingOrders' => $upcomingOrders,
            'lowStock' => $lowStock,
            'periodLabel' => $start->format('d/m').' — '.$end->format('d/m/Y'),
        ]);
    }

    private function scopeOrdersQuery(Builder $query, User $user, bool $supervisor): void
    {
        if (! $supervisor) {
            $query->where('user_id', $user->id);
        }
    }

    private function scopeSalesQuery(Builder|\Illuminate\Database\Query\Builder $query, ?int $userId): void
    {
        if ($userId !== null) {
            $query->where('ventas.user_id', $userId);
        }

    }
}
