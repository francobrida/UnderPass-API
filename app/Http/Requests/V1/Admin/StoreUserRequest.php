<?php

namespace App\Http\Requests\V1\Admin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

/**
 * @bodyParam name string required Example: Pepe Organizer
 * @bodyParam email email required Example: Pepe@club.com
 * @bodyParam role string required The user's role (admin, organizer, clubber). Example: organizer
 * @bodyParam password string required Example: secret1234
 * @bodyParam password_confirmation required string Example: secret1234
 */
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->role === UserRole::ADMIN;
    }

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

