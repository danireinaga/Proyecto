<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'nombre',
        'descripcion',
        'precio',
        'stock',
        'imagen',
        'estado',
        'destacado',
        'en_menu_digital',
        'orden_destacado',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'stock' => 'integer',
        'estado' => 'boolean',
        'destacado' => 'boolean',
        'en_menu_digital' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'product_id');
    }

    public function pedidoItems()
    {
        return $this->hasMany(PedidoItem::class);
    }

    /** Productos seleccionados para menú digital/Caja con existencias. */
    public function scopeDisponibleEnMenuDigital(Builder $query): Builder
    {
        return $query->enMenuDigital()->conStock();
    }

    public function scopeEnMenuDigital(Builder $query): Builder
    {
        return $query->where('en_menu_digital', true);
    }

    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('estado', true);
    }

    public function scopeConStock(Builder $query): Builder
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        if (! $termino) {
            return $query;
        }

        $like = '%'.$termino.'%';

        return $query->where(function (Builder $q) use ($like) {
            $q->where('nombre', 'like', $like)
                ->orWhere('descripcion', 'like', $like);
        });
    }

    public function getAgotadoAttribute(): bool
    {
        return (int) $this->stock <= 0;
    }
}
