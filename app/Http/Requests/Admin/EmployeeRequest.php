<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class EmployeeRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->only([
            'nombres',
            'apellido_paterno',
            'apellido_materno',
            'email',
            'celular',
            'carnet_identidad',
        ]);

        foreach (['nombres', 'apellido_paterno', 'apellido_materno', 'celular'] as $field) {
            if (isset($data[$field]) && is_string($data[$field])) {
                $data[$field] = trim($data[$field]);
            }
        }

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = mb_strtolower(trim($data['email']));
        }

        if (isset($data['carnet_identidad']) && is_string($data['carnet_identidad'])) {
            $data['carnet_identidad'] = mb_strtoupper(
                preg_replace('/\s+/', '', trim($data['carnet_identidad'])) ?? ''
            );
        }

        $this->merge($data);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function employeeRules(?User $employee = null): array
    {
        $emailRule = Rule::unique('users', 'email');
        $identityRule = Rule::unique('users', 'carnet_identidad');

        if ($employee) {
            $emailRule->ignore($employee->id);
            $identityRule->ignore($employee->id);
        }

        return [
            'nombres' => ['bail', 'required', 'string', 'max:80'],
            'apellido_paterno' => ['bail', 'required', 'string', 'max:80'],
            'apellido_materno' => ['bail', 'nullable', 'string', 'max:80'],
            'email' => ['bail', 'required', 'string', 'email', 'max:255', $emailRule],
            'celular' => ['bail', 'required', 'string', 'max:25', 'regex:/^\+?[0-9\s().-]{7,25}$/'],
            'carnet_identidad' => [
                'bail',
                'required',
                'string',
                'min:4',
                'max:30',
                'regex:/^[A-Z0-9-]+$/',
                $identityRule,
            ],
            'role' => [
                'bail',
                'required',
                Rule::exists('roles', 'name')
                    ->where('guard_name', 'web')
                    ->where('is_active', true),
                Rule::notIn([User::ROL_ADMIN]),
            ],
        ];
    }
}
