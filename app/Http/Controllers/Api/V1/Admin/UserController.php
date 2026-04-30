<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use App\Http\Resources\V1\{EventResource, UserResource}; 
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\V1\Admin\{StoreUserRequest, UpdateUserRequest};

/**
 * @group User Management (Admin)
 * 
 * Restricted endpoints for administrators to manage user accounts and their associated data.
 */
class UserController extends Controller
{
    /**
     * List all users.
     * 
     * Retrieves a complete list of registered users, including clubbers, organizers, and admins.
     * 
     * @authenticated
     * @apiResourceCollection App\Http\Resources\V1\UserResource
     * @apiResourceModel App\Models\User
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Users list retrieved successfully',
            'data' => UserResource::collection(User::all())
        ], 200);
    }

    /**
     * Create user.
     * 
     * Manually registers a new user with a specific assigned role.
     * 
     * @authenticated
     * @apiResource App\Http\Resources\V1\UserResource
     * @apiResourceModel App\Models\User
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'User created successfully',
            'data' => new UserResource($user)
        ], 201);
    }

    /**
     * Update user.
     * 
     * Updates the profile information or role of an existing user.
     * 
     * @authenticated
     * @apiResource App\Http\Resources\V1\UserResource
     * @apiResourceModel App\Models\User
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return response()->json([
            'message' => 'User updated successfully',
            'data' => new UserResource($user)
        ], 200);
    }

    /**
     * Get user events.
     * 
     * Retrieves all events created by a specific user, including the total vouch count for each.
     * 
     * @authenticated
     * @apiResourceCollection App\Http\Resources\V1\EventResource
     * @apiResourceModel App\Models\Event
     */
    public function getUserEvents(User $user): JsonResponse
    {
        $events = $user->events()->withCount('vouches')->get(); 

        return response()->json([
            'message' => "Events for user: {$user->name} retrieved successfully",
            'data'    => EventResource::collection($events)
        ], 200);
    }

    /**
     * Delete user.
     * 
     * Permanently removes a user account. Admins are prevented from deleting their own account.
     * 
     * @authenticated
     * @response 200 {
     *  "message": "User deleted successfully"
     * }
     * @response 403 {
     *  "message": "You cannot delete your own admin account"
     * }
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'You cannot delete your own admin account'], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ], 200);
    }
}