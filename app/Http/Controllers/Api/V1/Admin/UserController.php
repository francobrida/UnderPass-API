<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\V1\Admin\{StoreUserRequest, UpdateUserRequest};

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'message' => 'Users list retrieved successfully',
            'data' => User::all()
        ], 200);
    }

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
            'data' => $user
        ], 201);
    }

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

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {

        $validated = $request->validated();

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user
        ], 200);
    }

    public function getUserEvents(User $user): JsonResponse
    {
        
        if (Auth::user()->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $events = $user->events; 

        return response()->json([
            'message' => "Events for user: {$user->name} retrieved successfully",
            'data'    => $events
        ], 200);
    }
}

