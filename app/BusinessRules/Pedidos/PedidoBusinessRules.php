<?php

namespace App\BusinessRules\Pedidos;

use App\Models\Pedido;
use App\Models\PedidoItem;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PedidoBusinessRules
{
    public function authorizeModule(User $actor): void
    {
        Gate::forUser($actor)->authorize('viewAny', Pedido::class);
    }

    public function authorizeCreate(User $actor): void
    {
        Gate::forUser($actor)->authorize('create', Pedido::class);
    }

    public function authorizeView(User $actor, Pedido $pedido): void
    {
        Gate::forUser($actor)->authorize('view', $pedido);
    }

    public function authorizeUpdate(User $actor, Pedido $pedido): void
    {
        Gate::forUser($actor)->authorize('update', $pedido);
    }

    public function authorizeCancel(User $actor, Pedido $pedido): void
    {
        Gate::forUser($actor)->authorize('delete', $pedido);
    }

    public function assignableUsers()
    {
        return User::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $actor, array $data): Pedido
    {
        $this->authorizeCreate($actor);
        $this->authorizeInitialStatus($actor, $data['estado']);

        return DB::transaction(function () use ($actor, $data): Pedido {
            $total = $this->amountInCents($data['total_estimado']);
            $abono = $this->amountInCents($data['abono'] ?? 0);
            $this->validateAdvancePayment($abono, $total);

            $pedido = Pedido::create([
                ...$this->header($data),
                'tipo' => Pedido::TIPO_PERSONALIZADO,
                'user_id' => $actor->id,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
                'total_estimado' => $total / 100,
                'abono' => $abono / 100,
            ]);

            return $pedido->fresh(['items', 'user', 'creator', 'lastEditor']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $actor, Pedido $pedido, array $data): Pedido
    {
        $this->authorizeUpdate($actor, $pedido);

        if (
            $data['estado'] !== $pedido->estado
            && in_array($data['estado'], [Pedido::ESTADO_CANCELADO, Pedido::ESTADO_ENTREGADO], true)
        ) {
            Gate::forUser($actor)->authorize('delete', $pedido);
        }

        return DB::transaction(function () use ($actor, $pedido, $data): Pedido {
            $pedido = Pedido::query()->lockForUpdate()->findOrFail($pedido->id);
            $total = $this->amountInCents($data['total_estimado']);
            $abono = $this->amountInCents($data['abono'] ?? 0);
            $this->validateAdvancePayment($abono, $total);

            $pedido->update([
                ...$this->header($data),
                'user_id' => $pedido->user_id,
                'updated_by' => $actor->id,
                'total_estimado' => $total / 100,
                'abono' => $abono / 100,
            ]);

            return $pedido->fresh(['items', 'user', 'creator', 'lastEditor']);
        });
    }

    public function updateStatus(User $actor, Pedido $pedido, string $status): Pedido
    {
        if (in_array($status, [Pedido::ESTADO_CANCELADO, Pedido::ESTADO_ENTREGADO], true)) {
            Gate::forUser($actor)->authorize('delete', $pedido);
        } else {
            Gate::forUser($actor)->authorize('update', $pedido);
        }

        $this->authorizeUpdate($actor, $pedido);
        $pedido->update(['estado' => $status, 'updated_by' => $actor->id]);

        return $pedido->fresh(['items', 'user', 'creator', 'lastEditor']);
    }

    public function cancel(User $actor, Pedido $pedido): Pedido
    {
        $this->authorizeCancel($actor, $pedido);
        abort_if($pedido->estado === Pedido::ESTADO_CANCELADO, 422, 'El pedido ya está cancelado.');

        $pedido->update([
            'estado' => Pedido::ESTADO_CANCELADO,
            'updated_by' => $actor->id,
        ]);

        return $pedido->fresh(['items', 'user', 'creator', 'lastEditor']);
    }

    public function updateSchedule(User $actor, Pedido $pedido, string $date, ?string $time): Pedido
    {
        $this->authorizeUpdate($actor, $pedido);

        abort_if(
            $date < today()->toDateString(),
            422,
            'No se puede programar una entrega en una fecha pasada.'
        );

        $pedido->update([
            'fecha_entrega' => $date,
            'hora_entrega' => $time ?: $pedido->hora_entrega,
            'updated_by' => $actor->id,
        ]);

        return $pedido->fresh(['items', 'user', 'creator', 'lastEditor']);
    }

    private function amountInCents(float|int|string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function validateAdvancePayment(int $advance, int $total): void
    {
        if ($total > 9_999_999_999) {
            throw ValidationException::withMessages([
                'total_estimado' => 'El monto acordado supera el límite permitido.',
            ]);
        }

        if ($advance > $total) {
            throw ValidationException::withMessages([
                'abono' => 'El abono no puede ser mayor al total del pedido.',
            ]);
        }

    }

    private function authorizeInitialStatus(User $actor, string $status): void
    {
        if (in_array($status, [Pedido::ESTADO_CANCELADO, Pedido::ESTADO_ENTREGADO], true)) {
            abort_unless($actor->can('admin.pedidos.anular'), 403);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function header(array $data): array
    {
        $header = [
            'cliente_nombre' => trim($data['cliente_nombre']),
            'cliente_apellido' => filled($data['cliente_apellido'] ?? null) ? trim($data['cliente_apellido']) : null,
            'cliente_telefono' => filled($data['cliente_telefono'] ?? null) ? trim($data['cliente_telefono']) : null,
            'cliente_nit' => filled($data['cliente_nit'] ?? null) ? trim($data['cliente_nit']) : null,
            'cliente_direccion' => filled($data['cliente_direccion'] ?? null) ? trim($data['cliente_direccion']) : null,
            'detalles' => $data['detalles'] ?? null,
            'tipo_encargo' => $data['tipo_encargo'] ?? null,
            'ocasion' => $data['ocasion'] ?? null,
            'porciones' => $data['porciones'] ?? null,
            'sabor' => $data['sabor'] ?? null,
            'relleno' => $data['relleno'] ?? null,
            'cobertura' => $data['cobertura'] ?? null,
            'colores' => $data['colores'] ?? null,
            'decoracion' => $data['decoracion'] ?? null,
            'texto_torta' => $data['texto_torta'] ?? null,
            'alergias' => $data['alergias'] ?? null,
            'imagen_referencia' => $data['imagen_referencia'] ?? null,
            'fecha_pedido' => $data['fecha_pedido'],
            'fecha_entrega' => $data['fecha_entrega'],
            'hora_entrega' => $data['hora_entrega'] ?: '10:00',
            'estado' => $data['estado'],
            'notas' => $data['notas'] ?? null,
        ];

        if (array_key_exists('imagen_referencia', $data)) {
            $header['imagen_referencia'] = $data['imagen_referencia'];
        }

        return $header;
    }
}
