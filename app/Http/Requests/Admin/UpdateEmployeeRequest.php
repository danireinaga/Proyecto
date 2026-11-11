<?php

namespace App\Http\Requests\Admin;

use App\Models\User;

class UpdateEmployeeRequest extends EmployeeRequest
{
    public function rules(): array
    {
        $employee = $this->route('usuario');

        return $this->employeeRules($employee instanceof User ? $employee : null);
    }
}
