<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * @authenticated
 * @header Authorization Bearer {token}
 * 
 * @bodyParam name string required The full name of the user. Example: Pepe Organizer
 * @bodyParam email string required A unique email address. Example: pepe@club.com
 * @bodyParam role string required The system role assigned to the user (admin, organizer, clubber). Example: organizer
 * @bodyParam password string required Must be at least 8 characters. Example: secret1234
 * @bodyParam password_confirmation string required Must match the password field. Example: secret1234
 */
class StoreUserRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', new Enum(UserRole::class)],
        ];
    }
}