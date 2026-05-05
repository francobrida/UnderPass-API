<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Enums\UserRole;
use App\Http\Requests\V1\{StoreEventRequest, UpdateEventRequest};
use App\Http\Resources\V1\EventResource;
use App\Services\EventService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * @group Events
 * 
 * Management of the event agenda, club sessions, and parties for Underpass Barcelona.
 */
class EventController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private EventService $eventService){}

    /**
     * List events.
     */
    public function index(Request $request): JsonResponse
    {
        $events = $this->eventService->filter($request->all());
        $events->load('organizer')->loadCount('vouches');

        return EventResource::collection($events)->response();
    }

    /**
     * Create event.
     * 
     * Registers a new event. Supports both standard file upload (flyer) 
     * and Base64 encoded strings (flyer_base64).
     * 
     * @authenticated
     * @bodyParam flyer file optional Event image.
     * @bodyParam flyer_base64 string optional Event image in Base64 format.
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->store(
            $request->user(), 
            $request->validated(), 
            $request->file('flyer'),
            $request->input('flyer_base64') 
        );

        return response()->json([
            'message' => 'Event created successfully, pending verification',
            'data' => $event
        ], 201);
    }

    /**
     * Update event.
     */
    public function update(UpdateEventRequest $request, $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $updatedEvent = $this->eventService->update(
            $request->user(),
            $event,
            $request->validated(),
            $request->file('flyer'),
            $request->input('flyer_base64') 
        );

        return response()->json([
            'message' => 'Event updated successfully',
            'data' => new EventResource($updatedEvent)
        ], 200);
    }

    /**
     * Delete event.
     */
    public function destroy(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('delete', $event);

        $this->eventService->delete($event);

        return response()->json([
            'message' => 'Event successfully deleted'
        ], 200);
    }

    /**
     * Get event details.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        if (!$event->is_verified && $event->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'This event is pending verification and is not public yet.'
            ], 403);
        }

        $event->load(['organizer', 'genres'])->loadCount('vouches');

        return (new EventResource($event))->response();
    }

    /**
     * Get own events.
     */
    public function getUserEvents(int $user_id): JsonResponse
    {
        $user = User::findOrFail($user_id);

        if (Auth::user()?->id !== $user->id && Auth::user()?->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $events = $user->events()->latest()->get();
        $events->loadCount('vouches');

        return EventResource::collection($events)->response();
    }

    /**
     * List neighborhoods with events.
     */
    public function neighborhoods() {
        return Event::where('is_verified', true)->distinct()->pluck('neighborhood');
    }
}