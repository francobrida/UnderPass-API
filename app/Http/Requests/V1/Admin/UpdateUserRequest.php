<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserRole;
use Illuminate\Validation\Rules\Enum;

class UpdateUserRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::ADMIN;
    }

    
    public function rules(): array
    {
        $userId = $this->route('user')->id ?? $this->route('user');

        return [
            'name'  => 'sometimes|string|max:255',
            'email' => [
                'sometimes', 
                'string', 
                'email', 
                'max:255', 
                'unique:users,email,' . $userId
            ],
            'role'  => ['sometimes', new Enum(UserRole::class)],
        ];
    }
}
