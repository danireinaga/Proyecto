<?php

namespace App\Policies;

use App\Models\Pedido;
use App\Models\User;

class PedidoPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('admin.pedidos.ver');
    }

    public function view(User $user, Pedido $pedido): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user) && $user->can('admin.pedidos.crear');
    }

    public function update(User $user, Pedido $pedido): bool
    {
        return $this->viewAny($user)
            && $user->can('admin.pedidos.editar')
            && ($user->esAdministrador() || (int) $pedido->user_id === (int) $user->id);
    }

    public function delete(User $user, Pedido $pedido): bool
    {
        return $this->viewAny($user)
            && $user->can('admin.pedidos.anular')
            && ($user->esAdministrador() || (int) $pedido->user_id === (int) $user->id);
    }
}
