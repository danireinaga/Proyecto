<?php

namespace App\Http\Controllers\Admin;

use App\BusinessRules\Pedidos\PedidoBusinessRules;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Pedidos\UpdatePedidoScheduleRequest;
use App\Models\Pedido;
use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function __construct()
    {
        $this->middleware(function (Request $request, \Closure $next) {
            app(PedidoBusinessRules::class)->authorizeModule($request->user());

            return $next($request);
        });
    }

    /* ---------------------------------------------------------------------
     | Vistas
     |--------------------------------------------------------------------*/

    public function index()
    {
        return view('admin.calendar.index');
    }

    /**
     * Feed de FullCalendar: fusiona los pedidos registrados en el sistema
     * con los eventos externos que ya existan en Google Calendar.
     */
    public function getEvents(Request $request)
    {
        try {
            $desde = $request->input('start') ? \Carbon\Carbon::parse($request->input('start'))->startOfDay() : now()->startOfMonth();
            $hasta = $request->input('end') ? \Carbon\Carbon::parse($request->input('end'))->startOfDay() : now()->endOfMonth();

            $hastaPedidos = $request->input('end') ? $hasta->copy()->subMicrosecond() : $hasta;
            $events = $this->pedidosComoEventos($desde, $hastaPedidos);
            $googleIdsLocales = Pedido::query()
                ->whereNotNull('google_event_id')
                ->pluck('google_event_id')
                ->all();

            // Los eventos de Google se cargan aparte: si la API falla no
            // se rompe el calendario completo.
            try {
                $events = array_merge($events, $this->eventosDeGoogle($desde, $hasta, $googleIdsLocales));
            } catch (\Throwable $e) {
                Log::warning('Google Calendar no disponible: '.$e->getMessage());
            }

            return response()->json($events);
        } catch (\Throwable $e) {
            Log::error('CalendarController@getEvents: '.$e->getMessage());

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** Pedidos de una fecha concreta (alimenta el modal del calendario). */
    public function pedidosDelDia(string $fecha)
    {
        $pedidos = Pedido::with('items')
            ->whereDate('fecha_entrega', $fecha)
            ->orderBy('hora_entrega')
            ->get();

        return response()->json([
            'fecha' => $fecha,
            'pedidos' => $pedidos->map(fn (Pedido $p) => [
                'id' => $p->id,
                'codigo' => $p->codigo,
                'cliente' => $p->cliente_nombre_completo,
                'telefono' => $p->cliente_telefono,
                'hora' => $p->hora_entrega,
                'estado' => $p->estado,
                'estado_label' => $p->estado_label,
                'estado_color' => $p->estado_color,
                'total' => (float) $p->total_estimado,
                'detalles' => $p->detalles,
                'productos' => $p->items->map(fn ($i) => $i->cantidad.'x '.$i->nombre_producto)->implode(', '),
                'url' => route('admin.pedidos.show', $p),
            ]),
            'total' => $pedidos->count(),
        ]);
    }

    public function actualizarEvento(
        UpdatePedidoScheduleRequest $request,
        Pedido $pedido,
        PedidoBusinessRules $businessRules
    )
    {
        $data = $request->validated();
        $pedido = $businessRules->updateSchedule(
            Auth::user(),
            $pedido,
            $data['fecha_entrega'],
            $data['hora_entrega'] ?? null
        );
        $this->sincronizarPedido($pedido);

        return back()->with('success', 'Fecha del pedido '.$pedido->codigo.' actualizada.');
    }

    public function eliminarEvento(Pedido $pedido, PedidoBusinessRules $businessRules)
    {
        $businessRules->authorizeUpdate(Auth::user(), $pedido);

        if (! $this->eliminarEventoDeGoogle($pedido)) {
            return back()->with(
                'warning',
                'No se pudo quitar el evento de Google Calendar. El pedido sigue en el calendario interno.'
            );
        }

        $pedido->update(['google_event_id' => null, 'updated_by' => Auth::id()]);

        return back()->with('success', 'Evento quitado de Google Calendar. El pedido sigue registrado en el sistema.');
    }

    /* ---------------------------------------------------------------------
     | Sincronización con Google Calendar
     |--------------------------------------------------------------------*/

    /** Crea o actualiza el evento de Google del pedido. Devuelve bool. */
    public function sincronizarPedido(Pedido $pedido, bool $notificarErrores = true): bool
    {
        if (! $this->googleDisponible()) {
            return false;
        }

        try {
            $service = $this->getCalendarService();
            $calendarId = config('google-calendar.calendar_id');

            $eventData = [
                'summary' => $pedido->codigo.' - '.$pedido->cliente_nombre_completo,
                'description' => $this->descripcionEvento($pedido),
                'start' => [
                    'dateTime' => \Carbon\Carbon::parse(
                        $pedido->fecha_entrega->format('Y-m-d').' '.($pedido->hora_entrega ?: '10:00:00'),
                        config('app.timezone')
                    )->toIso8601String(),
                    'timeZone' => config('app.timezone'),
                ],
                'end' => [
                    'dateTime' => \Carbon\Carbon::parse(
                        $pedido->fecha_entrega->format('Y-m-d').' '.($pedido->hora_entrega ?: '10:00:00'),
                        config('app.timezone')
                    )->addHour()->toIso8601String(),
                    'timeZone' => config('app.timezone'),
                ],
            ];

            if ($pedido->google_event_id) {
                $event = $service->events->get($calendarId, $pedido->google_event_id);
                $event->set($eventData);
                $service->events->update($calendarId, $event->getId(), $event);
            } else {
                $event = new Event($eventData);
                $created = $service->events->insert($calendarId, $event);
                $pedido->update(['google_event_id' => $created->getId()]);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo sincronizar el pedido '.$pedido->codigo.': '.$e->getMessage());

            if ($notificarErrores) {
                session()->flash('warning', 'El pedido se guardó, pero Google Calendar no respondió: '.$e->getMessage());
            }

            return false;
        }
    }

    public function eliminarEventoDeGoogle(Pedido $pedido): bool
    {
        if (! $pedido->google_event_id || ! $this->googleDisponible()) {
            return false;
        }

        try {
            $this->getCalendarService()->events->delete(
                config('google-calendar.calendar_id'),
                $pedido->google_event_id
            );

            return true;
        } catch (\Throwable $e) {
            Log::warning('No se pudo borrar el evento de '.$pedido->codigo.': '.$e->getMessage());

            return false;
        }
    }

    /* ---------------------------------------------------------------------
     | Internos
     |--------------------------------------------------------------------*/

    private function getCalendarService()
    {
        $credentials = storage_path('app/google-calendar/google-calendar-credentials.json');

        $client = new Client();
        $client->setAuthConfig($credentials);
        $client->addScope(Calendar::CALENDAR);

        return new Calendar($client);
    }

    private function googleDisponible(): bool
    {
        $credentials = storage_path('app/google-calendar/google-calendar-credentials.json');

        return (bool) config('google-calendar.calendar_id')
            && is_file($credentials)
            && is_readable($credentials);
    }

    /** Pedidos → eventos de FullCalendar. */
    private function pedidosComoEventos(\Carbon\Carbon $desde, \Carbon\Carbon $hasta): array
    {
        $colores = [
            Pedido::ESTADO_PENDIENTE => '#ffc107',
            Pedido::ESTADO_EN_PRODUCCION => '#17a2b8',
            Pedido::ESTADO_LISTO => '#28a745',
            Pedido::ESTADO_ENTREGADO => '#6c757d',
            Pedido::ESTADO_CANCELADO => '#dc3545',
        ];

        return Pedido::entrega($desde->toDateString(), $hasta->toDateString())
            ->get()
            ->map(fn (Pedido $p) => [
                'id' => 'pedido-'.$p->id,
                'title' => $p->codigo.' · '.$p->cliente_nombre_completo,
                'start' => $p->inicio_calendar,
                'end' => \Carbon\Carbon::parse($p->inicio_calendar)->addHour()->toIso8601String(),
                'backgroundColor' => $colores[$p->estado] ?? '#6c3461',
                'borderColor' => $colores[$p->estado] ?? '#6c3461',
                'extendedProps' => [
                    'tipo' => 'pedido',
                    'pedido_id' => $p->id,
                    'estado' => $p->estado_label,
                    'total' => (float) $p->total_estimado,
                    'edit_url' => route('admin.pedidos.edit', $p),
                    'show_url' => route('admin.pedidos.show', $p),
                ],
            ])
            ->all();
    }

    /** Eventos externos de Google Calendar paginados por rango de fechas. */
    private function eventosDeGoogle(\Carbon\Carbon $desde, \Carbon\Carbon $hasta, array $googleIdsLocales): array
    {
        if (! $this->googleDisponible()) {
            return [];
        }

        $service = $this->getCalendarService();
        $calendarId = config('google-calendar.calendar_id');

        $eventos = [];
        $pageToken = null;

        do {
            $optParams = [
                'singleEvents' => true,
                'orderBy' => 'startTime',
                'timeMin' => $desde->toIso8601String(),
                'timeMax' => $hasta->toIso8601String(),
                'maxResults' => 250,
            ];

            if ($pageToken) {
                $optParams['pageToken'] = $pageToken;
            }

            $results = $service->events->listEvents($calendarId, $optParams);

            foreach ($results->getItems() ?? [] as $event) {
                if (in_array($event->getId(), $googleIdsLocales, true)) {
                    continue;
                }

                $start = $event->getStart()->getDateTime() ?: $event->getStart()->getDate();
                $end = $event->getEnd()?->getDateTime() ?: $event->getEnd()?->getDate();

                $eventos[] = [
                    'id' => 'google-'.$event->getId(),
                    'title' => $event->getSummary() ?: '(sin título)',
                    'start' => $start,
                    'end' => $end,
                    'backgroundColor' => '#6c3461',
                    'borderColor' => '#4a2848',
                    'extendedProps' => [
                        'tipo' => 'google',
                        'google_id' => $event->getId(),
                        'descripcion' => $event->getDescription(),
                    ],
                ];
            }

            $pageToken = $results->getNextPageToken();
        } while ($pageToken);

        return $eventos;
    }

    private function descripcionEvento(Pedido $pedido): string
    {
        $lineas = [
            'Cliente: '.$pedido->cliente_nombre_completo,
        ];

        if ($pedido->cliente_telefono) {
            $lineas[] = 'Teléfono: '.$pedido->cliente_telefono;
        }

        if ($pedido->detalles) {
            $lineas[] = 'Detalle: '.$pedido->detalles;
        }

        if ($pedido->items->isNotEmpty()) {
            $lineas[] = 'Productos:';
            foreach ($pedido->items as $item) {
                $lineas[] = '  • '.$item->cantidad.'x '.$item->nombre_producto
                    .($item->observacion ? ' ('.$item->observacion.')' : '');
            }
        }

        $lineas[] = 'Estado: '.$pedido->estado_label;
        $lineas[] = 'Total estimado: Bs. '.number_format((float) $pedido->total_estimado, 2);

        return implode("\n", $lineas);
    }

}
