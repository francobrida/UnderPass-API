<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Http\Requests\V1\Auth\{RegisterRequest, LoginRequest};


class AuthController extends Controller
{
    /**
     * Login user.
     * @unauthenticated
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'message'      => 'Login successful',
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'name' => $user->name,
                'id' => $user->id, 
                'role' => $user->role
            ]
        ], 200);
    }

    /**
     * Register a new user.
     * @unauthenticated
     * @bodyParam password_confirmation string required Same pass as above. Example: secret1234
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $validated['password'] = Hash::make($validated['password']);

        $validated['role'] = UserRole::CLUBBER->value; 

        $user = User::create($validated);

        $token = $user->createToken('auth_token')->accessToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'name'  => $user->name,
                'email' => $user->email,
                'role'  => $user->role->value,
                'id' => $user->id, 
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
