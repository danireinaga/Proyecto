<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\SoftDeletes; 
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;
    // Agrega SoftDeletes aquí adentro
    use SoftDeletes;
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'celular',
        'carnet_identidad',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ---------------------------------------------------------------------
     | Roles del negocio
     |--------------------------------------------------------------------*/

    public const ROL_ADMIN = 'admin';

    /**
     * Roles autorizados a ver la totalidad de las ventas.
     */
    public function esAdministrador(): bool
    {
        return $this->hasRole(self::ROL_ADMIN);
    }

    public function esSupervisor(): bool
    {
        return $this->esAdministrador();
    }

    /** Un cajero sólo opera sobre su propia caja. */
    public function puedeVerVentasDe(?int $userId): bool
    {
        return $this->esSupervisor() || $userId === null || $userId === $this->id;
    }

    /* ---------------------------------------------------------------------
     | Relaciones
     |--------------------------------------------------------------------*/

    public function ventas()
    {
        return $this->hasMany(Order::class);
    }

    public function pedidos()
    {
        return $this->hasMany(Pedido::class);
    }
}
