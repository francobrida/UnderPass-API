<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\VibeCheck;
use App\Enums\UserRole;
use App\Http\Requests\V1\StoreVibecheckRequest;
use App\Services\VibecheckService;
use App\Http\Resources\V1\VibecheckResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * @group Vibe Check
 * 
 * Endpoints for post-event reviews. Users can rate the "vibe" and sound quality of an event after it concludes.
 */
class VibecheckController extends Controller
{
    public function __construct(private VibecheckService $vibecheckService){}

    /**
     * Submit Vibe Check.
     * 
     * Allows a user to rate an event. Note: The vibe check window only opens 6 hours after the event has ended.
     * 
     * @authenticated
     * @urlParam id int required The ID of the event to review. Example: 1
     * 
     * @bodyParam sound_score int required Rating from 1-5. Example: 5
     * @bodyParam comment string optional User's feedback. Example: "The acoustics were incredible."
     * 
     * @response 201 {
     *  "message": "Vibecheck added successfully.",
     *  "data": { "id": 1, "sound_score": 5, "comment": "..." }
     * }
     * @response 403 {
     *  "message": "Too early! The vibe check opens 6 hours after the event ends."
     * }
     */
    public function store(StoreVibecheckRequest $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        if (!$event->isReadyForVibeCheck()) {
            return response()->json([
                'message' => 'Too early! The vibe check opens 6 hours after the event ends.'
            ], 403);
        }
        
        $vibecheck = $this->vibecheckService->store(
            Auth::user(), 
            $event, 
            $request->validated()
        );

        return response()->json([
            'message' => 'Vibecheck added successfully.',
            'data'    => $vibecheck
        ], 201);
    }

    /**
     * Get Event Vibe Checks.
     * 
     * Retrieves all reviews for a specific event, including the calculated average sound score.
     * 
     * @urlParam id int required The ID of the event. Example: 1
     * 
     * @response 200 {
     *  "event_title": "Techno Warehouse 01",
     *  "average_sound": 4.5,
     *  "data": [
     *    {
     *      "user": "Pepe Clubber",
     *      "sound_score": 5,
     *      "comment": "Boooring"
     *    }
     *  ]
     * }
     */
    public function index(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $vibechecks = VibeCheck::where('event_id', $id)
            ->with('user')
            ->latest()
            ->get();

        $averageSound = $vibechecks->avg('sound_score') ?? 0;

        return response()->json([
            'event_title'   => $event->title,
            'average_sound' => (float) $averageSound,
            'data'          => VibecheckResource::collection($vibechecks)
        ], 200);
    }

    /**
     * Delete Vibe Check.
     * 
     * Removes a specific vibe check record. Restricted to administrators.
     * 
     * @authenticated
     * @urlParam id int required The ID of the Vibe Check. Example: 1
     * 
     * @response 204 {
     *  "message": "Vibecheck deleted successfully."
     * }
     * @response 403 {
     *  "message": "Unauthorized. Only admins can delete vibechecks."
     * }
     */
    public function destroy(int $id): JsonResponse
    {
        if (Auth::user()->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized. Only admins can delete vibechecks.'], 403);
        }

        $this->vibecheckService->delete($id);

        return response()->json(['message' => 'Vibecheck deleted successfully.'], 204);
    }
}