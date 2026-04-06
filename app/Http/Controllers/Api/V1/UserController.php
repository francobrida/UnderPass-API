<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Enums\UserRole;

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

        return response()->json([
            'data' => [
                'id'     => $user->id,
                'name'   => $user->name,
                'email'  => $user->email,
                'role'   => $user->role->value,
                'points' => $user->points,
            ]
        ]);
    }
}