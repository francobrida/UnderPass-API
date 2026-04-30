<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use App\Http\Resources\V1\UserResource;
use App\Http\Requests\V1\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group Profile Management
 * 
 * Endpoints for authenticated users to view, update, or delete their own account information.
 */
class UserController extends Controller
{
    /**
     * Get user profile.
     * 
     * Retrieves the profile details of a specific user. Access is restricted to the account owner or administrators.
     * 
     * @authenticated
     * @urlParam id string required The unique ID of the user. Example: 1
     * 
     * @apiResource App\Http\Resources\V1\UserResource
     * @apiResourceModel App\Models\User
     * 
     * @response 403 {
     *  "message": "You dont have permission to see this"
     * }
     * @response 404 {
     *  "message": "User not found"
     * }
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $isOwner = $request->user()->id === $user->id;
        $isAdmin = $request->user()->role === UserRole::ADMIN;

        if (!$isOwner && !$isAdmin) {
            return response()->json([
                'message' => 'You dont have permission to see this'
            ], 403);
        }

        return (new UserResource($user))->response();
    }

    /**
     * Update profile.
     * 
     * Updates the authenticated user's profile information, such as name, email, or password.
     * 
     * @authenticated
     * @bodyParam name string optional The user's full name. Example: Jordi
     * @bodyParam email string optional The user's email address. Example: jordi@example.com
     * @bodyParam password string optional The new account password.
     * 
     * @apiResource App\Http\Resources\V1\UserResource
     * @apiResourceModel App\Models\User
     * 
     * @response 200 {
     *  "message": "Profile successfully updated",
     *  "data": { "id": 1, "name": "Jordi", "email": "jordi@example.com" }
     * }
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile successfully updated',
            'data'    => new UserResource($user)
        ]);
    }

    /**
     * Delete account.
     * 
     * Permanently deletes the authenticated user's account from the platform.
     * 
     * @authenticated
     * 
     * @response 200 {
     *  "message": "Account successfully deleted"
     * }
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->user()->delete();

        return response()->json([
            'message' => 'Account successfully deleted'
        ], 200);
    }
}