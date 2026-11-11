<?php

namespace App\Http\Requests\Admin\Pedidos;

use App\BusinessRules\Pedidos\PedidoBusinessRules;

class StorePedidoRequest extends PedidoRequest
{
    public function authorize(PedidoBusinessRules $businessRules): bool
    {
        $businessRules->authorizeCreate($this->user());

        return true;
    }

    public function rules(): array
    {
        $rules = $this->pedidoRules();
        $rules['fecha_pedido'] = ['required', 'date_format:Y-m-d', 'date_equals:'.today()->toDateString()];
        $rules['fecha_entrega'][] = 'after_or_equal:today';

        return $rules;
    }
}
