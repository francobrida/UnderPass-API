<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserRole;
use Illuminate\Validation\Rules\Enum;

/**
 * @bodyParam name string required Example: Lolo Techno
 * @bodyParam email email required Example: fran@underpass.app
 * @bodyParam password string required Example: password123
 * @bodyParam password_confirmation string required Example: password123
 */
class RegisterRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation()
    {
        $this->merge([
            'email' => strtolower($this->email),
            'role'  => $this->role ?? UserRole::CLUBBER->value,
        ]);
    }
    
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}
