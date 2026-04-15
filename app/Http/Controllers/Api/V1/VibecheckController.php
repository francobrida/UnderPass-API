<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Enums\UserRole;
use App\Http\Requests\V1\StoreVibecheckRequest;
use App\Services\VibecheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VibecheckController extends Controller
{
    public function __construct(private VibecheckService $vibecheckService){}

    public function store(StoreVibecheckRequest $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        // Delegamos todo al servicio
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

        // Solo el organizador puede ver el feedback detallado
        if ($event->user_id !== Auth::id() && Auth::user()->role !== UserRole::ADMIN) {
            return response()->json([
                'message' => 'Unauthorized. You can only view feedback for your own events.'
            ], 403);
        }

        $feedback = $this->vibecheckService->getEventFeedback($event);

        return response()->json($feedback, 200);
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