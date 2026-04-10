<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RankingController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::select('id', 'name')
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