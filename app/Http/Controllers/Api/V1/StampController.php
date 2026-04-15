<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Services\StampService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StampController extends Controller
{
    
    public function __construct(private StampService $stampService) {}

    public function index(): JsonResponse
    {
        $stamps = $this->stampService->getUserStamps(Auth::id());

        return response()->json([
            'count' => $stamps->count(),
            'data'  => $stamps
        ], 200);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string',]);

        $stamp = $this->stampService->collect(Auth::user(), $request->token);

        return response()->json([
            'message' => 'Stamp collected successfully!',
            'data'    => $stamp->load('event:id,title')
        ], 201);
    }

    public function getUserStamps(int $id): JsonResponse
    {
        if (Auth::id() !== $id && Auth::user()->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $stamps = $this->stampService->getUserStamps($id);

        return response()->json([
            'count' => $stamps->count(),
            'data'  => $stamps
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        if (Auth::user()->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->stampService->delete($id);

        return response()->json(['message' => 'Stamp deleted successfully.'], 204);
    }
}