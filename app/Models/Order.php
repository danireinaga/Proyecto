<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $table = 'ventas';

    public const ESTADO_COMPLETADO = 'completado';

    public const ESTADO_CANCELADO = 'cancelado';

    public const PAGOS = ['efectivo', 'tarjeta', 'transferencia', 'qr'];

    /** Etiquetas legibles de las formas de pago, para la ficha y los selectores. */
    public const PAGOS_LABELS = [
        'efectivo' => 'Efectivo',
        'tarjeta' => 'Tarjeta',
        'transferencia' => 'Transferencia',
        'qr' => 'QR / Codigo',
    ];

    protected $fillable = [
        'user_id',
        'cliente_nombre',
        'cliente_apellido',
        'cliente_telefono',
        'cliente_nit',
        'cliente_direccion',
        'total',
        'descuento',
        'propina',
        'total_final',
        'pago_tipo',
        'pago_recibido',
        'pago_vuelto',
        'estado',
        'origen',
        'numero_ficha',
        'notas',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'descuento' => 'decimal:2',
        'propina' => 'decimal:2',
        'total_final' => 'decimal:2',
        'pago_recibido' => 'decimal:2',
        'pago_vuelto' => 'decimal:2',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeCompletados(Builder $query): Builder
    {
        return $query->where('estado', self::ESTADO_COMPLETADO);
    }

    public function scopeDeHoy(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeEntreFechas(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        if ($desde) {
            $query->whereDate('created_at', '>=', $desde);
        }

        if ($hasta) {
            $query->whereDate('created_at', '<=', $hasta);
        }

        return $query;
    }

    /** Nombre completo del cliente, tolerante a campos vacíos. */
    public function getClienteNombreCompletoAttribute(): string
    {
        return trim(($this->cliente_nombre ?? '').' '.($this->cliente_apellido ?? '')) ?: 'Cliente de mostrador';
    }

    /** Siguiente correlativo diario de ficha para el usuario, ej. F-20260929-0007 */
    public static function siguienteNumeroFicha(int $userId): string
    {
        $prefijo = 'F-'.now()->format('Ymd').'-';

        $ultimo = static::query()
            ->where('user_id', $userId)
            ->where('numero_ficha', 'like', $prefijo.'%')
            ->orderByDesc('numero_ficha')
            ->value('numero_ficha');

        $siguiente = $ultimo ? ((int) substr($ultimo, strlen($prefijo)) + 1) : 1;

        return $prefijo.str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
    }
}
