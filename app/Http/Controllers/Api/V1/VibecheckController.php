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

class VibecheckController extends Controller
{
    public function __construct(private VibecheckService $vibecheckService){}

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

    public function index(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $vibechecks = Vibecheck::where('event_id', $id)
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

    public function destroy(int $id): JsonResponse
    {
        if (Auth::user()->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Unauthorized. Only admins can delete vibechecks.'], 403);
        }

        $this->vibecheckService->delete($id);

        return response()->json(['message' => 'Vibecheck deleted successfully.'], 204);
    }
}