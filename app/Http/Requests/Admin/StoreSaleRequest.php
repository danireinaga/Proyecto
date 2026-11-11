<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('admin.caja.ver') ?? false;
    }

    public function rules(): array
    {
        return [
            // Datos del cliente
            'cliente_nombre' => ['required', 'string', 'max:120'],
            'cliente_apellido' => ['nullable', 'string', 'max:120'],
            'cliente_telefono' => ['nullable', 'string', 'max:40'],
            'cliente_nit' => ['nullable', 'string', 'max:40'],
            'cliente_direccion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string', 'max:1000'],

            // Ítems de la nota de venta
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable'],
            'items.*.nombre' => ['required', 'string', 'max:255'],
            'items.*.precio' => ['required', 'numeric', 'min:0'],
            'items.*.cantidad' => ['required', 'integer', 'min:1', 'max:9999'],
            'items.*.note' => ['nullable', 'string', 'max:500'],

            // Cobro
            'pago_tipo' => ['required', Rule::in(\App\Models\Order::PAGOS)],
            'pago_recibido' => ['nullable', 'numeric', 'min:0'],
            'descuento' => ['nullable', 'numeric', 'min:0'],
            'propina' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cliente_nombre.required' => 'Debe ingresar el nombre del cliente.',
            'items.required' => 'La nota de venta está vacía.',
            'items.min' => 'La nota de venta está vacía.',
            'pago_tipo.required' => 'Debe seleccionar una forma de pago.',
            'pago_tipo.in' => 'La forma de pago no es válida.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cliente_nombre' => trim((string) $this->input('cliente_nombre')),
            'cliente_apellido' => $this->filled('cliente_apellido')
                ? trim((string) $this->input('cliente_apellido'))
                : null,
            'descuento' => (float) ($this->input('descuento') ?: 0),
            'propina' => (float) ($this->input('propina') ?: 0),
            'pago_recibido' => (float) ($this->input('pago_recibido') ?: 0),
        ]);
    }

    /** Subtotal calculado en servidor (nunca se confía en el total del navegador). */
    public function subtotal(): float
    {
        return round(collect($this->input('items', []))->sum(
            fn ($item) => (float) $item['precio'] * (int) $item['cantidad']
        ), 2);
    }
}
