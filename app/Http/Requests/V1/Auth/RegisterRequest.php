<?php

namespace App\Http\Requests\V1\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserRole;
use Illuminate\Validation\Rules\Enum;

/**
 * @bodyParam name string required The user's full name. Example: Lolo Techno
 * @bodyParam email string required A valid and unique email address. Example: fran@underpass.app
 * @bodyParam password string required Must be at least 8 characters. Example: password123
 * @bodyParam password_confirmation string required Must match the password field. Example: password123
 */
class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Publicly accessible for new account creation.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     * Normalizes email to lowercase and defaults the role to 'clubber'.
     */
    public function prepareForValidation()
    {
        $this->merge([
            'email' => strtolower($this->email),
            'role'  => $this->role ?? UserRole::CLUBBER->value,
        ]);
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }
}