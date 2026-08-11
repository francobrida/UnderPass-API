<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthCookie;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserRole;
use App\Http\Requests\V1\Auth\{RegisterRequest, LoginRequest};
use Illuminate\Support\Facades\Auth;


/**
 * @group Authentication
 * 
 * Endpoints for managing user access, registration, and logout sessions.
 */
class AuthController extends Controller
{
    /**
     * Login user.
     * 
     * Authenticates a user with email and password, returning a Personal Access Token (Passport).
     * 
     * @unauthenticated
     * @response 200 {
     *  "message": "Login successful",
     *  "user": {
     *      "id": 1,
     *      "name": "Pepe Clubber",
     *      "role": "clubber"
     *  }
     * }
     * @response 401 {
     *  "message": "Invalid credentials"
     * }
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        if (!Auth::attempt($credentials)) {

            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();

        $tokenResult = $user->createToken('auth_token');

        // Passport's default personal-access-token lifetime is ~1 year when
        // no expiry is set (see D-03: this migrates transport, not policy).
        $minutes = $tokenResult->token->expires_at
            ? now()->diffInMinutes($tokenResult->token->expires_at)
            : 60 * 24 * 365;

        return response()->json([
            'message'      => 'Login successful',
            'user'         => [
                'id'    => $user->id,
                'name'  => $user->name,
                'role'  => $user->role
            ]
        ], 200)->withCookie(AuthCookie::make($tokenResult->accessToken, $minutes));
    }

    /**
     * Register user.
     * 
     * Creates a new "Clubber" account and returns the initial access token.
     * 
     * @unauthenticated
     * @bodyParam password_confirmation string required Must match the password field. Example: secret1234
     * @response 201 {
     *  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6...",
     *  "token_type": "Bearer",
     *  "user": {
     *      "id": 5,
     *      "name": "Pepe lolo",
     *      "email": "pepe@example.com",
     *      "role": "clubber"
     *  }
     * }
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

    /**
     * Logout.
     * 
     * Revokes the current access token for the authenticated user.
     * 
     * @authenticated
     * @response 200 {
     *  "message": "Session successfully logged out"
     * }
     */
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