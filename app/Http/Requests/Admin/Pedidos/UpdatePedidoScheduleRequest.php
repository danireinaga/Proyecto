<?php

namespace App\Http\Requests\Admin\Pedidos;

use App\BusinessRules\Pedidos\PedidoBusinessRules;
use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePedidoScheduleRequest extends FormRequest
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
        return [
            'fecha_entrega' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'hora_entrega' => ['nullable', 'date_format:H:i'],
        ];
    }
}
