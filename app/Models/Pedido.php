<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    use HasFactory;

    public const TIPO_PERSONALIZADO = 'personalizado';

    public const TIPO_MENU_DIGITAL = 'menu_digital';

    public const ESTADO_PENDIENTE = 'pendiente';

    public const ESTADO_EN_PRODUCCION = 'en_produccion';

    public const ESTADO_LISTO = 'listo';

    public const ESTADO_ENTREGADO = 'entregado';

    public const ESTADO_CANCELADO = 'cancelado';

    /** Estados considered activos (aparecen en el calendario y en el tablero). */
    public const ESTADOS_ACTIVOS = [
        self::ESTADO_PENDIENTE,
        self::ESTADO_EN_PRODUCCION,
        self::ESTADO_LISTO,
    ];

    public const ESTADOS = [
        self::ESTADO_PENDIENTE => 'Pendiente',
        self::ESTADO_EN_PRODUCCION => 'En producción',
        self::ESTADO_LISTO => 'Listo para entregar',
        self::ESTADO_ENTREGADO => 'Entregado',
        self::ESTADO_CANCELADO => 'Cancelado',
    ];

    protected $fillable = [
        'tipo',
        'codigo',
        'cliente_nombre',
        'cliente_apellido',
        'cliente_telefono',
        'cliente_nit',
        'cliente_direccion',
        'detalles',
        'tipo_encargo',
        'ocasion',
        'porciones',
        'sabor',
        'relleno',
        'cobertura',
        'colores',
        'decoracion',
        'texto_torta',
        'alergias',
        'imagen_referencia',
        'fecha_pedido',
        'fecha_entrega',
        'hora_entrega',
        'estado',
        'total_estimado',
        'abono',
        'user_id',
        'created_by',
        'updated_by',
        'google_event_id',
        'notas',
    ];

    protected $casts = [
        'fecha_pedido' => 'date',
        'fecha_entrega' => 'date',
        'porciones' => 'integer',
        'total_estimado' => 'decimal:2',
        'abono' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        // Correlativo legible: PED-0001, PED-0002...
        static::creating(function (self $pedido) {
            if (blank($pedido->codigo)) {
                $ultimo = static::query()->orderByDesc('id')->value('codigo');
                $siguiente = $ultimo ? ((int) substr($ultimo, 4) + 1) : 1;
                $pedido->codigo = 'PED-'.str_pad((string) $siguiente, 4, '0', STR_PAD_LEFT);
            }

            if (blank($pedido->estado)) {
                $pedido->estado = self::ESTADO_PENDIENTE;
            }

            if (blank($pedido->user_id)) {
                $pedido->user_id = auth()->id();
            }
        });
    }

    public function items()
    {
        return $this->hasMany(PedidoItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lastEditor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->whereIn('estado', self::ESTADOS_ACTIVOS);
    }

    public function scopeEntrega(Builder $query, ?string $desde, ?string $hasta): Builder
    {
        if ($desde) {
            $query->whereDate('fecha_entrega', '>=', $desde);
        }

        if ($hasta) {
            $query->whereDate('fecha_entrega', '<=', $hasta);
        }

        return $query;
    }

    public function scopeDeHoy(Builder $query): Builder
    {
        return $query->whereDate('fecha_entrega', today());
    }

    public function getClienteNombreCompletoAttribute(): string
    {
        return trim(($this->cliente_nombre ?? '').' '.($this->cliente_apellido ?? '')) ?: 'Sin nombre';
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? ucfirst((string) $this->estado);
    }

    /** Color de badge Bootstrap 4 para el estado. */
    public function getEstadoColorAttribute(): string
    {
        return match ($this->estado) {
            self::ESTADO_PENDIENTE => 'warning',
            self::ESTADO_EN_PRODUCCION => 'info',
            self::ESTADO_LISTO => 'success',
            self::ESTADO_ENTREGADO => 'secondary',
            self::ESTADO_CANCELADO => 'danger',
            default => 'light',
        };
    }

    public function getSaldoPendienteAttribute(): float
    {
        return max(0, round((float) $this->total_estimado - (float) $this->abono, 2));
    }

    /** Datetime para FullCalendar (usa hora_entrega si viene, si no 09:00). */
    public function getInicioCalendarAttribute(): string
    {
        return $this->fecha_entrega->format('Y-m-d').'T'.($this->hora_entrega ?: '09:00:00');
    }
}
