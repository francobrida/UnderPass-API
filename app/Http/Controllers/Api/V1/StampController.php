<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Enums\UserRole;
use App\Services\StampService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Gamification
 * 
 * Management of collectible stamps. Users earn stamps by attending verified events.
 */
class StampController extends Controller
{
    
    public function __construct(private StampService $stampService) {}

    /**
     * My Stamps.
     * 
     * Retrieves the collection of stamps belonging to the currently authenticated user.
     * 
     * @authenticated
     * @response 200 {
     *  "count": 2,
     *  "data": [
     *    {
     *      "id": 10,
     *      "user_id": 1,
     *      "event_id": 5,
     *      "created_at": "2026-04-15T22:00:00.000000Z",
     *      "event": { "id": 5, "title": "Barcelona Techno Sessions" }
     *    }
     *  ]
     * }
     */
    public function index(): JsonResponse
    {
        $stamps = $this->stampService->getUserStamps(Auth::id());

        return response()->json([
            'count' => $stamps->count(),
            'data'  => $stamps
        ], 200);
    }

    /**
     * Collect Stamp.
     * 
     * Registers a new stamp for the authenticated user using a unique event token.
     * 
     * @authenticated
     * @bodyParam token string required The unique alphanumeric event token (stamp_token). Example: "underpass_bcn_2026_xyz"
     * 
     * @response 201 {
     *  "message": "Stamp collected successfully!",
     *  "data": {
     *      "id": 11,
     *      "user_id": 1,
     *      "event_id": 8,
     *      "event": { "id": 8, "title": "Raval Underground" }
     *  }
     * }
     * @response 400 {
     *  "message": "Invalid token or stamp already collected."
     * }
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate(['token' => 'required|string',]);

        $stamp = $this->stampService->collect(Auth::user(), $request->token);

        return response()->json([
            'message' => 'Stamp collected successfully!',
            'data'    => $stamp->load('event:id,title')
        ], 201);
    }

    /**
     * List User Stamps.
     * 
     * Retrieves the stamp collection for a specific user ID. Accessible by the owner or an administrator.
     * 
     * @authenticated
     * @urlParam id int required The ID of the user. Example: 1
     * 
     * @response 200 {
     *  "count": 5,
     *  "data": [...]
     * }
     * @response 403 {
     *  "message": "Unauthorized"
     * }
     */
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

    /**
     * Delete Stamp.
     * 
     * Permanently removes a specific stamp record. Admin only.
     * 
     * @authenticated
     * @urlParam id int required The ID of the stamp. Example: 10
     * 
     * @response 204 {
     *  "message": "Stamp deleted successfully."
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        if (Auth::user()->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->stampService->delete($id);

        return response()->json(['message' => 'Stamp deleted successfully.'], 204);
    }
}