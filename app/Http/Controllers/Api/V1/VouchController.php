<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Vouch;
use App\Services\VouchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Event Verification
 * 
 * Endpoints for the community-driven verification system. Users "vouch" for events to confirm their legitimacy.
 */
class VouchController extends Controller
{
    
    public function __construct(private VouchService $vouchService) {}

    /**
     * Vouch for an event.
     * 
     * Registers the authenticated user's support for an event. If the event reaches the required vouch threshold, it is automatically marked as verified and becomes public.
     * 
     * @authenticated
     * @urlParam id int required The ID of the event to vouch for. Example: 1
     * 
     * @response 201 {
     *  "message": "Vouch added successfully.",
     *  "current_vouches": 5,
     *  "is_verified": true
     * }
     * @response 400 {
     *  "message": "You have already vouched for this event."
     * }
     */
    public function store(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        
        $result = $this->vouchService->addVouch(Auth::user(), $event);

        return response()->json([
            'message' => 'Vouch added successfully.',
            'current_vouches' => $result['count'],
            'is_verified' => $result['is_verified']
        ], 201);
    }

    /**
     * Get event vouchers.
     * 
     * Retrieves a list of users who have vouched for a specific event. Useful for displaying community trust.
     * 
     * @urlParam id int required The ID of the event. Example: 1
     * 
     * @response 200 {
     *  "data": [
     *    {
     *      "user_id": 3,
     *      "user_name": "Fran"
     *    },
     *    {
     *      "user_id": 7,
     *      "user_name": "Agos"
     *    }
     *  ]
     * }
     */
    public function index($id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $vouchers = $event->vouches()->get();

        $data = [];
        
        foreach ($vouchers as $user) {
            $data[] = [
                'user_id'   => $user->id,
                'user_name' => $user->name,
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }

}