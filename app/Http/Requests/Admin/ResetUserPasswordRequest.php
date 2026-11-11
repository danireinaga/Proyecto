<?php

namespace App\Http\Requests\Admin;

use App\BusinessRules\UserBusinessRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ResetUserPasswordRequest extends FormRequest
{
    public function authorize(UserBusinessRules $businessRules): bool
    {
        $user = $this->route('usuario');

        abort_unless($user instanceof User, 404);
        $businessRules->authorizePasswordReset($user);

        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ];
    }
}
