<?php

namespace App\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @authenticated
 * @header Authorization Bearer {token}
 * 
 * @bodyParam name string optional The user's full name. Example: Lolo Techno
 * @bodyParam email string optional A unique email address. Example: fran@underpass.app
 * @bodyParam password string optional New password (minimum 8 characters). Example: password123
 * @bodyParam password_confirmation string optional Must match the password field. Example: password123
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Publicly accessible to any authenticated user modifying their own profile.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Uses 'sometimes' to support partial updates. The unique email rule 
     * ignores the current authenticated user's ID to allow saving without changes.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes', 
                'email', 
                'max:255', 
                Rule::unique('users')->ignore($this->user()?->id)
            ],
            'password' => 'sometimes|string|min:8|confirmed',
        ];
    }
}