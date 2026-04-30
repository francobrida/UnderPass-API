<?php

namespace App\Http\Requests\V1\Admin;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\UserRole;
use Illuminate\Validation\Rules\Enum;

/**
 * @authenticated
 * @header Authorization Bearer {token}
 * 
 * @bodyParam name string optional The updated name of the user. Example: Lolo Techno
 * @bodyParam email string optional A unique email address. Example: fran@underpass.app
 * @bodyParam role string optional The new role for the user (admin, organizer, clubber). Example: admin
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Restricted to users with the ADMIN role.
     */
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Uses the 'sometimes' rule to allow partial updates. The email uniqueness 
     * check excludes the current user ID being updated.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Extracts ID whether the route binding is an object or an integer
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