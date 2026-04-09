<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use Illuminate\Validation\Rules\Enum;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        
        $fields = $request->validate([
            'email'    => 'required|string|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', strtolower($fields['email']))->first();

        if (!$user || !Hash::check($fields['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => 
            ['name' => $user->name,'role'  => $user->role]
        ], 200);

    }

    public function register(Request $request): JsonResponse
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => ['nullable', new Enum(UserRole::class)],
        ]);

        $user = User::create([
            'name' => $fields['name'],
            'email'    => strtolower($fields['email']),
            'password' => Hash::make($fields['password']),
            'role'     => $fields['role'] ?? UserRole::CLUBBER->value,
        ]);

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'name' => $user->name,
                'email'    => $user->email,
                'role'     => $user->role->value,
            ]
        ], 201);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        /** @var \Laravel\Passport\Token $token */
        $token = $user->token();
        $token->revoke();

        return response()->json([
            'message' => 'Session successfully logged out'
        ], 200);
    }
}
