<?php

namespace App\Http\Requests\Admin\Pedidos;

use App\BusinessRules\Pedidos\PedidoBusinessRules;
use App\Models\Pedido;

class UpdatePedidoRequest extends PedidoRequest
{
    public function authorize(PedidoBusinessRules $businessRules): bool
    {
        $pedido = $this->route('pedido');
        abort_unless($pedido instanceof Pedido, 404);
        $businessRules->authorizeUpdate($this->user(), $pedido);

        return true;
    }

    public function rules(): array
    {
        return $this->pedidoRules();
    }
}
