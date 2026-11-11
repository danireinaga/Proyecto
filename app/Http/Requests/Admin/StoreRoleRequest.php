<?php

namespace App\Http\Requests\Admin;

class StoreRoleRequest extends RoleRequest
{
    public function rules(): array
    {
        return $this->roleRules();
    }
}
