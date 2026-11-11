<?php

namespace App\Http\Requests\Admin;

class UpdateRoleRequest extends RoleRequest
{
    public function rules(): array
    {
        return $this->roleRules(updating: true);
    }
}
