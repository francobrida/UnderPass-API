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

class UserController extends Controller
{
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

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->delete();

        return response()->json([
            'message' => 'Account successfully deleted'
        ], 200);
    }
}