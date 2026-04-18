<?php

namespace App\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @bodyParam name string Example: Lolo Techno
 * @bodyParam email email Example: fran@underpass.app
 * @bodyParam password string Example: password123
 * @bodyParam password_confirmation string Example: password123
 */
class UpdateUserRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    
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
