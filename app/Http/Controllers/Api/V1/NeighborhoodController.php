<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

/**
 * @group Events
 * 
 * Endpoints for location-based event discovery.
 */
class NeighborhoodController extends Controller
{
    /**
     * List active neighborhoods.
     * 
     * Retrieves a unique list of neighborhoods where events are currently scheduled.
     * 
     * @response 200 {
     *  "data": ["Poble Espanyol", "Poblenou", "Eixample"]
     * }
     */
    public function index(): JsonResponse
    {
        $neighborhoods = Event::query()
            ->whereNotNull('neighborhood')
            ->distinct()
            ->pluck('neighborhood');

        return response()->json([
            'data' => $neighborhoods
        ], 200);
    }
}