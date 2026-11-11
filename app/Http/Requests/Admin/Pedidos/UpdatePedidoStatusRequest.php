<?php

namespace App\Http\Requests\Admin\Pedidos;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePedidoStatusRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'estado' => ['required', Rule::in(array_keys(Pedido::ESTADOS))],
        ];
    }
}
