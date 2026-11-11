<?php

namespace App\Http\Requests\Admin\Pedidos;

use App\Models\Pedido;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class PedidoRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $clientName = $this->input('cliente_nombre');
        $attributes = [];
        if (is_string($clientName)) {
            $attributes['cliente_nombre'] = trim($clientName);
        }

        $pedido = $this->route('pedido');
        $attributes['fecha_pedido'] = $this->isMethod('post')
            ? today()->toDateString()
            : ($pedido instanceof Pedido ? $pedido->fecha_pedido->format('Y-m-d') : $this->input('fecha_pedido'));
        $this->merge($attributes);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function pedidoRules(): array
    {
        return [
            'cliente_nombre' => ['bail', 'required', 'string', 'max:120'],
            'cliente_apellido' => ['nullable', 'string', 'max:120'],
            'cliente_telefono' => ['required', 'string', 'max:40'],
            'cliente_nit' => ['nullable', 'string', 'max:40'],
            'cliente_direccion' => ['nullable', 'string', 'max:255'],
            'detalles' => ['nullable', 'string', 'max:2000'],
            'tipo_encargo' => ['nullable', 'string', 'max:120'],
            'ocasion' => ['nullable', 'string', 'max:80'],
            'porciones' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'sabor' => ['nullable', 'string', 'max:120'],
            'relleno' => ['nullable', 'string', 'max:120'],
            'cobertura' => ['nullable', 'string', 'max:120'],
            'colores' => ['nullable', 'string', 'max:255'],
            'decoracion' => ['nullable', 'string', 'max:2000'],
            'texto_torta' => ['nullable', 'string', 'max:255'],
            'alergias' => ['nullable', 'string', 'max:1000'],
            'imagen_referencia' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'quitar_imagen_referencia' => ['nullable', 'boolean'],
            'fecha_pedido' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'fecha_entrega' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:fecha_pedido',
            ],
            'hora_entrega' => ['nullable', 'date_format:H:i'],
            'estado' => ['required', Rule::in(array_keys(Pedido::ESTADOS))],
            'total_estimado' => ['required', 'numeric', 'min:0', 'decimal:0,2'],
            'abono' => ['nullable', 'numeric', 'min:0', 'decimal:0,2'],
            'notas' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha_pedido.before_or_equal' => 'La fecha del pedido no puede ser futura.',
            'fecha_entrega.after_or_equal' => 'La fecha de entrega no puede ser anterior a la fecha del pedido.',
            'total_estimado.required' => 'Indica el monto acordado con el cliente.',
        ];
    }
}
