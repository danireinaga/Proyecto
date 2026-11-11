<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rules\Password;

class StoreEmployeeRequest extends EmployeeRequest
{
    public function rules(): array
    {
        return [
            ...$this->employeeRules(),
            'password' => ['bail', 'required', Password::defaults()],
        ];
    }
}
