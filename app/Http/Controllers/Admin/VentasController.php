<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VentasController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:admin.ventas.ver');
    }

    /* ---------------------------------------------------------------------
     | Listado de ventas por responsable
     |
     | El tablero se organiza en pestañas dinámicas:
     |   - "Todas las ventas"  -> todo el local
     |   - "Administrador"      -> ventas de los usuarios con rol admin
     |   - una pestaña por cada usuario/empleado registrado
     |
     | Cada pestaña muestra únicamente las ventas de su responsable. El
     | módulo de Pedidos queda dedicado exclusivamente a los pedidos con
     | fecha de entrega.
     |--------------------------------------------------------------------*/

    public function index(Request $request)
    {
        $request->validate([
            'periodo' => ['nullable', 'in:semana_actual,semana_anterior,mes_actual,personalizado'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'empleado_id' => ['nullable', 'integer', 'exists:users,id'],
            'producto' => ['nullable', 'string', 'max:255'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $usuarioActual = Auth::user();
        $esSupervisor = $usuarioActual->esSupervisor();

        // Se consulta la relación en vez de usar User::role() porque este último
        // lanza una excepción si el rol "admin" todavía no fue creado.
        $idsAdmin = $esSupervisor
            ? User::whereHas('roles', fn ($q) => $q->where('name', User::ROL_ADMIN))
                ->pluck('id')
                ->all()
            : [];

        $pestanas = $this->pestanas($esSupervisor, $usuarioActual, $idsAdmin);
        $pestanaActiva = $this->pestanaActiva($request, $pestanas);

        $ventas = $this->ventasDeLaPestana($request, $pestanaActiva, $esSupervisor, $usuarioActual, $idsAdmin);

        return view('admin.ventas.index', [
            'ventas' => $ventas['ventasPestana'],
            'totalVentas' => $ventas['totalVentasPestana'],
            'pestanas' => $pestanas,
            'pestanaActiva' => $pestanaActiva,
            'pestanaActual' => collect($pestanas)->firstWhere('clave', $pestanaActiva) ?? $pestanas[0],
            'esSupervisor' => $esSupervisor,
            'filtros' => $request->only(['q', 'fecha', 'periodo', 'desde', 'hasta', 'empleado_id', 'producto']),
            // Se arrastran al cambiar de pestaña para no perder la búsqueda en curso
            'paramsFiltros' => $this->paramsFiltros($request),
            'formasPago' => Order::PAGOS_LABELS,
            'metricas' => $this->metricas($request, $pestanaActiva, $esSupervisor, $usuarioActual, $idsAdmin),
            'productosVendidos' => $this->productosVendidos($request, $pestanaActiva, $esSupervisor, $usuarioActual, $idsAdmin),
            'empleados' => User::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Construye la lista de pestañas. Al generarse a partir de los usuarios
     * registrados, cada usuario nuevo obtiene su pestaña automáticamente.
     *
     * @param  list<int>  $idsAdmin
     * @return list<array<string, mixed>>
     */
    private function pestanas(bool $esSupervisor, User $usuarioActual, array $idsAdmin): array
    {
        $conteos = $this->totalesPorUsuario(
            Order::selectRaw('user_id, COUNT(*) as total'),
            $esSupervisor,
            $usuarioActual
        );

        $importes = $this->totalesPorUsuario(
            Order::selectRaw('user_id, SUM(total_final) as total')
                ->where('estado', Order::ESTADO_COMPLETADO),
            $esSupervisor,
            $usuarioActual,
            fn ($total) => (float) $total
        );

// Un empleado sólo tiene su propia pestaña: nunca ve el tablero completo.
if (! $esSupervisor) {
            return [$this->pestanaDeUsuario(
                'user:'.$usuarioActual->id,
                $usuarioActual->id,
                'Mis ventas',
                'Ventas de '.$usuarioActual->name,
                'Ventas que registraste en el punto de venta.',
                'Todavía no registraste ventas.',
                $conteos,
                $importes,
                false
            )];
        }

        $pestanas = [[
            'clave' => 'todos',
            'user_id' => null,
            'etiqueta' => 'Todas las ventas',
            'tituloPestana' => 'Ver todas las ventas del local',
            'icono' => 'fa-layer-group',
            'color' => '#6c3461',
            'esAdmin' => false,
            'conteo' => (int) $conteos->sum(),
            'totalVentas' => round((float) $importes->sum(), 2),
            'ventas' => [
                'titulo' => 'Ventas de todo el personal',
                'descripcion' => 'Ventas realizadas por el administrador y por los empleados.',
                'descripcionVacia' => 'Todavía no hay ventas registradas.',
                'clase' => 'todos',
            ],
        ]];

        if ($idsAdmin !== []) {
            $pestanas[] = [
                'clave' => 'admin',
                'user_id' => null,
                'etiqueta' => 'Administrador',
                'tituloPestana' => 'Ver sólo las ventas del administrador',
                'icono' => 'fa-user-shield',
                'color' => '#6c3461',
                'esAdmin' => true,
                'conteo' => (int) $conteos->only($idsAdmin)->sum(),
                'totalVentas' => round((float) $importes->only($idsAdmin)->sum(), 2),
                'ventas' => [
                    'titulo' => 'Ventas del Administrador',
                    'descripcion' => 'Ventas realizadas por el administrador del local.',
                    'descripcionVacia' => 'El administrador todavía no registró ventas.',
                    'clase' => 'admin',
                ],
            ];
        }

        foreach (User::query()
            ->whereNotIn('id', $idsAdmin)
            ->orderBy('name')
            ->get() as $empleado) {
            $pestanas[] = $this->pestanaDeUsuario(
                'user:'.$empleado->id,
                $empleado->id,
                $empleado->name,
                'Ventas de '.$empleado->name,
                'Ventas realizadas por '.$empleado->name.'.',
                $empleado->name.' todavía no registró ventas.',
                $conteos,
                $importes,
                false
            );
        }

        return $pestanas;
    }

    /**
     * Pestaña individual de un usuario (empleado o el propio usuario logueado).
     *
     * @param  Collection<int, int>  $conteos
     * @param  Collection<int, float>  $importes
     * @return array<string, mixed>
     */
    private function pestanaDeUsuario(
        string $clave,
        int $userId,
        string $etiqueta,
        string $tituloVentas,
        string $descripcionVentas,
        string $descripcionVacia,
        Collection $conteos,
        Collection $importes,
        bool $esAdmin
    ): array {
        return [
            'clave' => $clave,
            'user_id' => $userId,
            'etiqueta' => $etiqueta,
            'tituloPestana' => 'Ver sólo las ventas de '.$etiqueta,
            'icono' => $esAdmin ? 'fa-user-shield' : 'fa-user',
            'color' => $esAdmin ? '#6c3461' : '#17a2b8',
            'esAdmin' => $esAdmin,
            'conteo' => (int) ($conteos[$userId] ?? 0),
            'totalVentas' => round((float) ($importes[$userId] ?? 0), 2),
            'ventas' => [
                'titulo' => $tituloVentas,
                'descripcion' => $descripcionVentas,
                'descripcionVacia' => $descripcionVacia,
                'clase' => $esAdmin ? 'admin' : 'empleados',
            ],
        ];
    }

    /**
     * Ejecuta un SELECT agrupado por user_id y devuelve [user_id => valor].
     *
     * @return Collection<int, int|float>
     */
    private function totalesPorUsuario(
        Builder $query,
        bool $esSupervisor,
        User $usuarioActual,
        ?callable $mapeo = null
    ): Collection {
        return $query
            ->when(! $esSupervisor, fn ($q) => $q->where('user_id', $usuarioActual->id))
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->mapWithKeys(fn ($total, $userId) => [
                (int) $userId => $mapeo ? $mapeo($total) : (int) $total,
            ]);
    }

    /**
     * Resuelve la pestaña activa. Se acepta tanto el parámetro `pestana` de las
     * pestañas como el filtro `user_id` del formulario (pestaña heredada).
     *
     * @param  list<array<string, mixed>>  $pestanas
     */
    private function pestanaActiva(Request $request, array $pestanas): string
    {
        $validas = array_column($pestanas, 'clave');

        $candidatas = array_filter([
            $request->input('pestana'),
            $request->filled('user_id') ? 'user:'.$request->input('user_id') : null,
        ]);

        foreach ($candidatas as $candidata) {
            if (in_array((string) $candidata, $validas, true)) {
                return (string) $candidata;
            }
        }

        return $validas[0] ?? 'todos';
    }

    /**
     * Acota una consulta al responsable de la pestaña activa.
     *
     * @param  list<int>  $idsAdmin
     */
    private function acotarPestana(
        Builder $query,
        string $pestana,
        bool $esSupervisor,
        User $usuarioActual,
        array $idsAdmin
    ): Builder {
        // Un empleado jamás sale de su propia caja, aunque manipule la URL.
        if (! $esSupervisor) {
            return $query->where('user_id', $usuarioActual->id);
        }

        return match (true) {
            $pestana === 'admin' => $query->whereIn('user_id', $idsAdmin),
            str_starts_with($pestana, 'user:') => $query->where('user_id', (int) substr($pestana, 5)),
            default => $query,
        };
    }

    /**
     * Ventas de la pestaña activa, respetando la búsqueda y la fecha.
     * Un empleado sólo ve las suyas.
     *
     * @param  list<int>  $idsAdmin
     * @return array<string, mixed>
     */
    private function ventasDeLaPestana(
        Request $request,
        string $pestana,
        bool $esSupervisor,
        User $usuarioActual,
        array $idsAdmin
    ): array {
        $query = $this->acotarPestana(
            Order::with(['user', 'items']),
            $pestana,
            $esSupervisor,
            $usuarioActual,
            $idsAdmin
        );

        $this->aplicarRango($query, $request);

        if ($request->filled('empleado_id')) {
            $query->where('user_id', $request->integer('empleado_id'));
        }

        if ($request->filled('producto')) {
            $query->whereHas('items', fn ($items) => $items->where('nombre_producto', $request->input('producto')));
        }

        if ($request->filled('q')) {
            $texto = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($texto) {
                $sub->where('numero_ficha', 'like', "%$texto%")
                    ->orWhere('cliente_nombre', 'like', "%$texto%")
                    ->orWhere('cliente_apellido', 'like', "%$texto%")
                    ->orWhere('cliente_nit', 'like', "%$texto%");
            });
        }

        $query->orderBy('id');

        // El total se calcula antes de paginar: paginate() agrega limit y
        // offset al builder, y el clon posterior sumaría sólo la página visible.
        $total = round($this->totalVendido(clone $query), 2);

        return [
            'ventasPestana' => (clone $query)->paginate(10)->appends($this->paramsFiltros($request)),
            'totalVentasPestana' => $total,
        ];
    }

    /** Suma de las ventas completadas de un bloque (ignora anuladas). */
    private function totalVendido(Builder $query): float
    {
        return (float) (clone $query)
            ->where('estado', Order::ESTADO_COMPLETADO)
            ->reorder()
            ->sum('total_final');
    }

    /**
     * Filtros vigentes que se arrastran al cambiar de pestaña para no perder
     * la búsqueda en curso. Sólo refleja lo que viene en la petición, así una
     * carga limpia sigue mostrando el comportamiento por defecto.
     *
     * @return array<string, mixed>
     */
    private function paramsFiltros(Request $request): array
    {
        return array_filter([
            'q' => $request->input('q'),
            'fecha' => $request->input('fecha'),
            'periodo' => $request->input('periodo'),
            'desde' => $request->input('desde'),
            'hasta' => $request->input('hasta'),
            'empleado_id' => $request->input('empleado_id'),
            'producto' => $request->input('producto'),
        ], fn ($valor) => $valor !== null && $valor !== '');
    }

    /**
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function rangoFechas(Request $request): array
    {
        if ($request->filled('fecha')) {
            $fecha = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $request->input('fecha'));

            return [$fecha->copy()->startOfDay(), $fecha->copy()->endOfDay()];
        }

        $hoy = today();

        return match ($request->input('periodo', 'semana_actual')) {
            'semana_anterior' => [$hoy->copy()->subWeek()->startOfWeek(), $hoy->copy()->subWeek()->endOfWeek()],
            'mes_actual' => [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfMonth()],
            'personalizado' => [
                $request->filled('desde') ? \Illuminate\Support\Carbon::parse($request->input('desde'))->startOfDay() : $hoy->copy()->startOfWeek(),
                $request->filled('hasta') ? \Illuminate\Support\Carbon::parse($request->input('hasta'))->endOfDay() : $hoy->copy()->endOfWeek(),
            ],
            default => [$hoy->copy()->startOfWeek(), $hoy->copy()->endOfWeek()],
        };
    }

    private function aplicarRango(Builder $query, Request $request): void
    {
        if ($request->filled('fecha')) {
            $query->whereDate('created_at', $request->input('fecha'));

            return;
        }

        [$desde, $hasta] = $this->rangoFechas($request);
        $query->whereBetween('created_at', [$desde, $hasta]);
    }

    /**
     * @param  list<int>  $idsAdmin
     * @return array{ventas_semana: float, cantidad_semana: int, producto_mas_vendido: string, cantidad_producto: int, mejor_empleado: string, ventas_mejor_empleado: float}
     */
    private function metricas(
        Request $request,
        string $pestana,
        bool $esSupervisor,
        User $usuarioActual,
        array $idsAdmin
    ): array {
        [$desde, $hasta] = $this->rangoFechas($request);
        $base = Order::query()
            ->where('estado', Order::ESTADO_COMPLETADO)
            ->whereBetween('created_at', [$desde, $hasta]);

        if (! $esSupervisor) {
            $base->where('user_id', $usuarioActual->id);
        } elseif ($pestana === 'admin') {
            $base->whereIn('user_id', $idsAdmin);
        } elseif (str_starts_with($pestana, 'user:')) {
            $base->where('user_id', (int) substr($pestana, 5));
        }

        $sales = (clone $base)->selectRaw('COALESCE(SUM(total_final), 0) as total, COUNT(*) as cantidad')->first();
        $productQuery = DB::table('venta_items as items')
            ->join('ventas', 'ventas.id', '=', 'items.order_id')
            ->where('ventas.estado', Order::ESTADO_COMPLETADO)
            ->whereBetween('ventas.created_at', [$desde, $hasta]);
        $employeeQuery = Order::query()
            ->selectRaw('user_id, SUM(total_final) as total')
            ->with('user')
            ->where('estado', Order::ESTADO_COMPLETADO)
            ->whereBetween('created_at', [$desde, $hasta]);

        if (! $esSupervisor) {
            $productQuery->where('ventas.user_id', $usuarioActual->id);
            $employeeQuery->where('user_id', $usuarioActual->id);
        } elseif ($pestana === 'admin') {
            $productQuery->whereIn('ventas.user_id', $idsAdmin);
            $employeeQuery->whereIn('user_id', $idsAdmin);
        } elseif (str_starts_with($pestana, 'user:')) {
            $userId = (int) substr($pestana, 5);
            $productQuery->where('ventas.user_id', $userId);
            $employeeQuery->where('user_id', $userId);
        }

        $productRanks = (clone $productQuery)
            ->selectRaw('items.nombre_producto, SUM(items.cantidad) as unidades')
            ->groupBy('items.nombre_producto');
        $producto = (clone $productRanks)
            ->orderByDesc('unidades')
            ->orderBy('items.nombre_producto')
            ->first();
        $menosVendido = (clone $productRanks)
            ->orderBy('unidades')
            ->orderBy('items.nombre_producto')
            ->first();
        $empleado = $employeeQuery
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->orderBy('user_id')
            ->first();

        return [
            'ventas_semana' => (float) ($sales->total ?? 0),
            'cantidad_semana' => (int) ($sales->cantidad ?? 0),
            'producto_mas_vendido' => $producto->nombre_producto ?? 'Sin ventas',
            'cantidad_producto' => (int) ($producto->unidades ?? 0),
            'producto_menos_vendido' => $menosVendido->nombre_producto ?? 'Sin ventas',
            'cantidad_producto_menos_vendido' => (int) ($menosVendido->unidades ?? 0),
            'mejor_empleado' => $empleado?->user?->name ?? 'Sin ventas',
            'ventas_mejor_empleado' => (float) ($empleado->total ?? 0),
        ];
    }

    /**
     * @param  list<int>  $idsAdmin
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function productosVendidos(
        Request $request,
        string $pestana,
        bool $esSupervisor,
        User $usuarioActual,
        array $idsAdmin
    ) {
        [$desde, $hasta] = $this->rangoFechas($request);
        $query = DB::table('venta_items as items')
            ->join('ventas', 'ventas.id', '=', 'items.order_id')
            ->where('ventas.estado', Order::ESTADO_COMPLETADO)
            ->whereBetween('ventas.created_at', [$desde, $hasta]);

        if (! $esSupervisor) {
            $query->where('ventas.user_id', $usuarioActual->id);
        } elseif ($pestana === 'admin') {
            $query->whereIn('ventas.user_id', $idsAdmin);
        } elseif (str_starts_with($pestana, 'user:')) {
            $query->where('ventas.user_id', (int) substr($pestana, 5));
        }

        return $query->distinct()->orderBy('items.nombre_producto')->pluck('items.nombre_producto');
    }
}