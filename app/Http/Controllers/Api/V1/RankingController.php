<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

/**
 * @group Gamification
 * 
 * Endpoints related to the status system, rankings, and loyalty of clubbers.
 */
class RankingController extends Controller
{
    /**
     * Get Clubber Ranking.
     * 
     * Retrieves the top 20 users based on the number of "stamps" collected at verified events.
     * 
     * @response 200 {
     *  "message": "Ranking retrieved successfully",
     *  "data": [
     *    {
     *      "id": 1,
     *      "name": "Pepe Clubber",
     *      "stamps_count": 15
     *    },
     *    {
     *      "id": 5,
     *      "name": "Marc Jordi",
     *      "stamps_count": 12
     *    }
     *  ]
     * }
     */
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name', 'points')
            ->withCount('stamps') 
            ->orderBy('stamps_count', 'desc')
            ->limit(20)
            ->get();

        return response()->json([
            'message' => 'Ranking retrieved successfully',
            'data' => $users
        ], 200);
    }
}