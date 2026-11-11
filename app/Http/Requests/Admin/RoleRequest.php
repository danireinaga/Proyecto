<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class RoleRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge(['name' => trim($name)]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function roleRules(bool $updating = false): array
    {
        $role = $this->route('role');
        $nameRule = Rule::unique('roles', 'name')->where('guard_name', 'web');

        if ($updating && $role instanceof \Spatie\Permission\Models\Role) {
            $nameRule->ignore($role->id);
        }

        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:255',
                $nameRule,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (
                        is_string($value)
                        && mb_strtolower(trim($value)) === User::ROL_ADMIN
                    ) {
                        $fail('Ese nombre está reservado para un rol del sistema.');
                    }
                },
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => [
                'string',
                Rule::exists('permissions', 'name')->where('guard_name', 'web'),
            ],
        ];
    }
}
