<?php

namespace App\Http\Controllers\Admin;

use App\BusinessRules\Pedidos\PedidoBusinessRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pedidos\StorePedidoRequest;
use App\Http\Requests\Admin\Pedidos\UpdatePedidoRequest;
use App\Http\Requests\Admin\Pedidos\UpdatePedidoStatusRequest;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class PedidoController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, \Closure $next) {
            app(PedidoBusinessRules::class)->authorizeModule($request->user());

            return $next($request);
        });
    }

    /* ---------------------------------------------------------------------
     | Listado / tablero
     |
     | Este módulo es exclusivamente de pedidos con fecha de entrega. Las ventas
     | que el administrador y los empleados realizan se listan en el módulo
     | Ventas (App\Http\Controllers\Admin\VentasController).
     |
     | El tablero se organiza en pestañas dinámicas:
     |   - "Todos los pedidos"  -> todo el local
     |   - "Administrador"       -> pedidos de los usuarios con rol admin
     |   - una pestaña por cada usuario/empleado registrado
     |
     | Cada pestaña muestra únicamente los pedidos de su responsable.
     |--------------------------------------------------------------------*/

    public function index(Request $request, PedidoBusinessRules $businessRules): \Illuminate\Contracts\View\View
    {
        $request->validate([
            'periodo' => ['nullable', 'in:activos,semana_actual,semana_anterior,proximos,historial,todos,personalizado'],
            'fecha' => ['nullable', 'date_format:Y-m-d'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
            'estado' => ['nullable', 'in:'.implode(',', array_keys(Pedido::ESTADOS))],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $usuarioActual = Auth::user();
        $businessRules->authorizeModule($usuarioActual);
        $esSupervisor = $usuarioActual->esAdministrador();

        $idsAdmin = User::whereHas('roles', fn ($query) => $query->where('name', User::ROL_ADMIN))
            ->pluck('id')
            ->all();

        $pestanas = $this->pestanas($esSupervisor, $usuarioActual, $idsAdmin);
        $pestanaActiva = $this->pestanaActiva($request, $pestanas);

        $pedidos = $this->pedidosDeLaPestana($request, $pestanaActiva, $esSupervisor, $usuarioActual, $idsAdmin)
            ->paginate(12)
            ->withQueryString();

        $pedidoCliente = $this->pedidoDelCliente($request, $pedidos, $pestanaActiva, $esSupervisor, $usuarioActual, $idsAdmin);

        $cajeros = $esSupervisor ? $businessRules->assignableUsers() : collect();

        return view('admin.pedidos.index', [
            'pedidos' => $pedidos,
            'contadores' => $this->contadores($request, $pestanaActiva, $esSupervisor, $usuarioActual, $idsAdmin),
            'cajeros' => $cajeros,
            'pestanas' => $pestanas,
            'pestanaActiva' => $pestanaActiva,
            'pestanaActual' => collect($pestanas)->firstWhere('clave', $pestanaActiva) ?? $pestanas[0],
            'pedidoCliente' => $pedidoCliente,
            'esSupervisor' => $esSupervisor,
            'filtros' => $request->only(['estado', 'q', 'fecha', 'desde', 'hasta', 'periodo', 'user_id', 'todos']),
            // Se arrastran al cambiar de pestaña para no perder la búsqueda en curso
            'paramsFiltros' => $this->paramsFiltros($request),
            'estados' => Pedido::ESTADOS,
        ]);
    }

    public function personalizados(Request $request): \Illuminate\Contracts\View\View
    {
        $request->validate([
            'estado' => ['nullable', 'in:'.implode(',', array_keys(Pedido::ESTADOS))],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $query = Pedido::query()
            ->where('tipo', Pedido::TIPO_PERSONALIZADO)
            ->with(['items', 'user'])
            ->when($request->filled('estado'), fn (Builder $query) => $query->where('estado', $request->input('estado')))
            ->when($request->filled('q'), function (Builder $query) use ($request): void {
                $termino = '%'.$request->string('q')->trim().'%';
                $query->where(function (Builder $query) use ($termino): void {
                    $query->where('codigo', 'like', $termino)
                        ->orWhere('cliente_nombre', 'like', $termino)
                        ->orWhere('cliente_apellido', 'like', $termino)
                        ->orWhere('cliente_telefono', 'like', $termino);
                });
            })
            ->orderByDesc('fecha_entrega')
            ->orderByDesc('id');

        return view('admin.pedidos.personalizados.index', [
            'pedidos' => $query->paginate(15)->withQueryString(),
            'estados' => Pedido::ESTADOS,
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
            Pedido::selectRaw('user_id, COUNT(*) as total'),
            $esSupervisor,
            $usuarioActual
        );

        $pestanas = [[
            'clave' => 'todos',
            'user_id' => null,
            'etiqueta' => 'Todos los pedidos',
            'tituloPestana' => 'Ver todos los pedidos del local',
            'tituloLista' => 'Todos los pedidos',
            'icono' => 'fa-layer-group',
            'color' => '#6c3461',
            'esAdmin' => false,
            'conteo' => (int) $conteos->sum(),
        ]];

        if ($idsAdmin !== []) {
            $pestanas[] = [
                'clave' => 'admin',
                'user_id' => null,
                'etiqueta' => 'Administrador',
                'tituloPestana' => 'Ver sólo los pedidos del administrador',
                'tituloLista' => 'Pedidos del Administrador',
                'icono' => 'fa-user-shield',
                'color' => '#6c3461',
                'esAdmin' => true,
                'conteo' => (int) $conteos->only($idsAdmin)->sum(),
            ];
        }

        foreach (
            User::query()
                ->when($idsAdmin !== [], fn ($query) => $query->whereNotIn('id', $idsAdmin))
                ->orderBy('name')
                ->get() as $empleado
        ) {
            $pestanas[] = $this->pestanaDeUsuario(
                'user:'.$empleado->id,
                $empleado->id,
                $empleado->name,
                $conteos,
                false
            );
        }

        return $pestanas;
    }

    /**
     * Pestaña individual de un usuario (empleado o el propio cajero).
     *
     * @param  Collection<int, int>  $conteos
     * @return array<string, mixed>
     */
    private function pestanaDeUsuario(
        string $clave,
        int $userId,
        string $etiqueta,
        Collection $conteos,
        bool $esAdmin
    ): array {
        return [
            'clave' => $clave,
            'user_id' => $userId,
            'etiqueta' => $etiqueta,
            'tituloPestana' => 'Ver sólo los pedidos de '.$etiqueta,
            'tituloLista' => 'Pedidos de '.$etiqueta,
            'icono' => $esAdmin ? 'fa-user-shield' : 'fa-user',
            'color' => $esAdmin ? '#6c3461' : '#17a2b8',
            'esAdmin' => $esAdmin,
            'conteo' => (int) ($conteos[$userId] ?? 0),
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
        User $usuarioActual
    ): Collection {
        return $query
            ->groupBy('user_id')
            ->pluck('total', 'user_id')
            ->mapWithKeys(fn ($total, $userId) => [(int) $userId => (int) $total]);
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
        return match (true) {
            $pestana === 'admin' => $query->whereIn('user_id', $idsAdmin),
            str_starts_with($pestana, 'user:') => $query->where('user_id', (int) substr($pestana, 5)),
            default => $query,
        };
    }

    /**
     * Pedidos de la pestaña activa, respetando los filtros del tablero.
     *
     * @param  list<int>  $idsAdmin
     */
    private function pedidosDeLaPestana(
        Request $request,
        string $pestana,
        bool $esSupervisor,
        User $usuarioActual,
        array $idsAdmin
    ): Builder {
        $query = $this->acotarPestana(
            Pedido::with(['items', 'user']),
            $pestana,
            $esSupervisor,
            $usuarioActual,
            $idsAdmin
        );

        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        } elseif ($request->input('periodo') === 'historial') {
            $query->whereIn('estado', [Pedido::ESTADO_ENTREGADO, Pedido::ESTADO_CANCELADO]);
        } elseif (! in_array($request->input('periodo'), ['todos'], true) && ! $request->filled('todos')) {
            $query->whereIn('estado', Pedido::ESTADOS_ACTIVOS);
        }

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($q) {
                $sub->where('codigo', 'like', "%$q%")
                    ->orWhere('cliente_nombre', 'like', "%$q%")
                    ->orWhere('cliente_apellido', 'like', "%$q%")
                    ->orWhere('cliente_telefono', 'like', "%$q%")
                    ->orWhere('cliente_nit', 'like', "%$q%")
                    ->orWhere('detalles', 'like', "%$q%");
            });
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('fecha')) {
            $query->whereDate('fecha_entrega', $request->input('fecha'));
        } else {
            $this->aplicarPeriodo($query, $request);
        }

        return $query->orderBy('fecha_entrega')->orderBy('hora_entrega')->orderBy('id');
    }

    /**
     * Pedido que se muestra en el apartado "Pedido del cliente": el elegido con
     * ?pedido=ID o, en su defecto, el primero de la página actual.
     *
     * @param  list<int>  $idsAdmin
     */
    private function pedidoDelCliente(
        Request $request,
        LengthAwarePaginator $pagina,
        string $pestana,
        bool $esSupervisor,
        User $usuarioActual,
        array $idsAdmin
    ): ?Pedido {
        if ($request->filled('pedido')) {
            $elegido = $this->acotarPestana(
                Pedido::with(['items.product', 'user']),
                $pestana,
                $esSupervisor,
                $usuarioActual,
                $idsAdmin
            )
                ->whereKey((int) $request->input('pedido'))
                ->first();

            if ($elegido) {
                return $elegido;
            }
        }

        return $pagina->getCollection()->first();
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
        $params = array_filter([
            'q' => $request->input('q'),
            'estado' => $request->input('estado'),
            'fecha' => $request->input('fecha'),
            'desde' => $request->input('desde'),
            'hasta' => $request->input('hasta'),
            'periodo' => $request->input('periodo'),
            'todos' => $request->input('todos'),
        ], fn ($valor) => $valor !== null && $valor !== '');

        return $params;
    }

    private function aplicarPeriodo(Builder $query, Request $request): void
    {
        $periodo = $request->input('periodo');

        if ($periodo === 'proximos') {
            $query->whereDate('fecha_entrega', '>=', today());
        } elseif (in_array($periodo, ['semana_actual', 'semana_anterior'], true)) {
            $desde = $periodo === 'semana_anterior' ? today()->subWeek()->startOfWeek() : today()->startOfWeek();
            $hasta = $periodo === 'semana_anterior' ? today()->subWeek()->endOfWeek() : today()->endOfWeek();
            $query->whereBetween('fecha_entrega', [$desde->toDateString(), $hasta->toDateString()]);
        } elseif ($periodo === 'personalizado') {
            if ($request->filled('desde')) {
                $query->whereDate('fecha_entrega', '>=', $request->input('desde'));
            }
            if ($request->filled('hasta')) {
                $query->whereDate('fecha_entrega', '<=', $request->input('hasta'));
            }
        }
    }

    /**
     * Dashboard totals stay within the selected responsible's scope.
     *
     * @param  list<int>  $idsAdmin
     * @return array<string, int|string>
     */
    private function contadores(
        Request $request,
        string $pestana,
        bool $esSupervisor,
        User $usuarioActual,
        array $idsAdmin
    ): array {
        $base = $this->acotarPestana(
            Pedido::query(),
            $pestana,
            $esSupervisor,
            $usuarioActual,
            $idsAdmin
        );
        $activos = (clone $base)->whereIn('estado', Pedido::ESTADOS_ACTIVOS);
        $pico = (clone $activos);
        if (! $request->filled('periodo') || $request->input('periodo') === 'activos') {
            $pico->whereBetween('fecha_entrega', [
                today()->startOfWeek()->toDateString(),
                today()->endOfWeek()->toDateString(),
            ]);
        } else {
            $this->aplicarPeriodo($pico, $request);
        }
        $diaPico = $pico->selectRaw('fecha_entrega, COUNT(*) as total')
            ->groupBy('fecha_entrega')
            ->orderByDesc('total')
            ->orderBy('fecha_entrega')
            ->first();
        $responsablePico = (clone $activos)
            ->selectRaw('user_id, COUNT(*) as total')
            ->with('user')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->orderBy('user_id')
            ->first();

        return [
            'pendientes' => (clone $activos)->count(),
            'produccion' => (clone $activos)->where('estado', Pedido::ESTADO_EN_PRODUCCION)->count(),
            'listos' => (clone $activos)->where('estado', Pedido::ESTADO_LISTO)->count(),
            'hoy' => (clone $activos)->whereDate('fecha_entrega', today())->count(),
            'dia_pico' => $diaPico?->fecha_entrega
                ? \Illuminate\Support\Carbon::parse($diaPico->fecha_entrega)->format('d/m/Y')
                : 'Sin pedidos',
            'conteo_dia_pico' => (int) ($diaPico->total ?? 0),
            'responsable_pico' => $responsablePico?->user?->name ?? 'Sin pedidos',
            'conteo_responsable_pico' => (int) ($responsablePico->total ?? 0),
        ];
    }

    public function show(Pedido $pedido, PedidoBusinessRules $businessRules): \Illuminate\Contracts\View\View
    {
        $businessRules->authorizeView(Auth::user(), $pedido);

        $pedido->load('items.product', 'user', 'creator', 'lastEditor');

        return view('admin.pedidos.show', compact('pedido') + [
            'estados' => Pedido::ESTADOS,
        ]);
    }

    public function imagenReferencia(Pedido $pedido, PedidoBusinessRules $businessRules): BinaryFileResponse
    {
        $businessRules->authorizeView(Auth::user(), $pedido);

        $imagePath = $pedido->imagen_referencia;
        abort_unless(
            is_string($imagePath)
                && preg_match('/\Apedidos\/referencias\/[A-Za-z0-9][A-Za-z0-9_-]*\.(?:jpg|jpeg|png|webp)\z/i', $imagePath) === 1,
            404
        );

        $absolutePath = Storage::disk('public')->path($imagePath);
        abort_unless(is_file($absolutePath), 404);

        return response()->file($absolutePath, [
            'Cache-Control' => 'private, max-age=3600',
            'Content-Type' => match (strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'webp' => 'image/webp',
            },
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /* ---------------------------------------------------------------------
     | Alta y edición
     |--------------------------------------------------------------------*/

    public function create(Request $request, PedidoBusinessRules $businessRules): \Illuminate\Contracts\View\View
    {
        $businessRules->authorizeCreate(Auth::user());
        $request->validate([
            'fecha_entrega' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
        ]);

        $fechaPedido = today()->toDateString();
        $pedido = new Pedido([
            'fecha_pedido' => $fechaPedido,
            'fecha_entrega' => $request->input('fecha_entrega', today()->addDay()->toDateString()),
            'hora_entrega' => $request->input('hora_entrega', '10:00'),
            'estado' => Pedido::ESTADO_PENDIENTE,
        ]);

        $viewData = [
            'pedido' => $pedido,
            'estados' => Pedido::ESTADOS,
        ];

        return $request->boolean('modal')
            ? view('admin.pedidos._modal-form', $viewData + ['desdeCalendario' => true])
            : view('admin.pedidos.create', $viewData);
    }

    public function store(StorePedidoRequest $request, PedidoBusinessRules $businessRules)
    {
        $data = $request->validated();
        $referenceImage = $data['imagen_referencia'] ?? null;
        unset($data['imagen_referencia'], $data['quitar_imagen_referencia']);

        $storedImagePath = $referenceImage?->store('pedidos/referencias', 'public');
        if ($storedImagePath) {
            $data['imagen_referencia'] = $storedImagePath;
        }

        try {
            $pedido = $businessRules->create(Auth::user(), $data);
        } catch (Throwable $exception) {
            if ($storedImagePath) {
                Storage::disk('public')->delete($storedImagePath);
            }

            throw $exception;
        }

        $this->sincronizarConGoogle($pedido, notificar: true);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'pedido_id' => $pedido->id,
                'codigo' => $pedido->codigo,
                'message' => 'Pedido '.$pedido->codigo.' registrado correctamente.',
            ]);
        }

        return redirect()->route('admin.pedidos.show', $pedido)
            ->with('success', 'Pedido '.$pedido->codigo.' registrado correctamente.');
    }

    public function edit(Request $request, Pedido $pedido, PedidoBusinessRules $businessRules): \Illuminate\Contracts\View\View
    {
        $businessRules->authorizeUpdate(Auth::user(), $pedido);

        $viewData = [
            'pedido' => $pedido->load('items', 'user'),
            'estados' => Pedido::ESTADOS,
        ];

        return $request->boolean('modal')
            ? view('admin.pedidos._modal-form', $viewData + ['desdeCalendario' => true])
            : view('admin.pedidos.edit', $viewData);
    }

    public function update(
        UpdatePedidoRequest $request,
        Pedido $pedido,
        PedidoBusinessRules $businessRules
    )
    {
        $data = $request->validated();
        $referenceImage = $data['imagen_referencia'] ?? null;
        $removeReferenceImage = (bool) ($data['quitar_imagen_referencia'] ?? false);
        $previousImagePath = $pedido->imagen_referencia;
        unset($data['imagen_referencia'], $data['quitar_imagen_referencia']);

        $storedImagePath = $referenceImage?->store('pedidos/referencias', 'public');
        if ($storedImagePath) {
            $data['imagen_referencia'] = $storedImagePath;
        } elseif ($removeReferenceImage) {
            $data['imagen_referencia'] = null;
        }

        try {
            $pedido = $businessRules->update(Auth::user(), $pedido, $data);
        } catch (Throwable $exception) {
            if ($storedImagePath) {
                Storage::disk('public')->delete($storedImagePath);
            }

            throw $exception;
        }

        if ($previousImagePath && ($storedImagePath || $removeReferenceImage)) {
            Storage::disk('public')->delete($previousImagePath);
        }

        $pedido->refresh();
        $this->sincronizarConGoogle($pedido, notificar: true);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'pedido_id' => $pedido->id,
                'codigo' => $pedido->codigo,
                'message' => 'Pedido '.$pedido->codigo.' actualizado correctamente.',
            ]);
        }

        return redirect()->route('admin.pedidos.show', $pedido)
            ->with('success', 'Pedido '.$pedido->codigo.' actualizado.');
    }

    /* ---------------------------------------------------------------------
     | Cambio de estado, anulación y sincronización
     |--------------------------------------------------------------------*/

    public function cambiarEstado(
        UpdatePedidoStatusRequest $request,
        Pedido $pedido,
        PedidoBusinessRules $businessRules
    ): RedirectResponse
    {
        $pedido = $businessRules->updateStatus(Auth::user(), $pedido, $request->validated('estado'));

        $this->sincronizarConGoogle($pedido, notificar: false);

        return back()->with('success', 'Estado actualizado a "'.$pedido->estado_label.'".');
    }

    public function cancel(Request $request, Pedido $pedido, PedidoBusinessRules $businessRules)
    {
        $codigo = $pedido->codigo;
        $pedido = $businessRules->cancel(Auth::user(), $pedido);
        $this->sincronizarConGoogle($pedido, notificar: true);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'pedido_id' => $pedido->id,
                'message' => 'Pedido '.$codigo.' cancelado y conservado en el historial.',
            ]);
        }

        return redirect()->route('admin.pedidos.index')
            ->with('success', 'Pedido '.$codigo.' cancelado; se conservó en el historial.');
    }

    /* ---------------------------------------------------------------------
     | Google Calendar (opcional: si falla, el pedido ya está guardado)
     |--------------------------------------------------------------------*/

    private function sincronizarConGoogle(Pedido $pedido, bool $notificar = false): void
    {
        if (! $notificar && ! $pedido->google_event_id) {
            return;
        }

        app(CalendarController::class)->sincronizarPedido($pedido);
    }

}
