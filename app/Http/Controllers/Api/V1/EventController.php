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
     * 
     * Retrieves a filterable list of verified events. Includes organizer details and musical genres.
     * 
     * @queryParam neighborhood string Filter by neighborhood (e.g., Poblenou, Gràcia). Example: Poblenou
     * @queryParam date string Filter by specific date (Y-m-d). Example: 2026-05-15
     * 
     * @apiResourceCollection App\Http\Resources\V1\EventResource
     * @apiResourceModel App\Models\Event
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
     * Registers a new event in the platform. Upon creation, the event is set to "pending verification" status.
     * 
     * @authenticated
     * @bodyParam flyer file optional Event image (jpg, png).
     * 
     * @response 201 {
     *  "message": "Event created successfully, pending verification",
     *  "data": { "id": 1, "title": "Techno Night", "is_verified": false }
     * }
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        $event = $this->eventService->store(
            $request->user(), 
            $request->validated(), 
            $request->file('flyer')
        );

        return response()->json([
            'message' => 'Event created successfully, pending verification',
            'data' => $event
        ], 201);
    }

    /**
     * Update event.
     * 
     * Allows an organizer to modify their event details. If a new flyer is uploaded, the previous one is replaced.
     * 
     * @authenticated
     * @urlParam id int required The ID of the event. Example: 1
     * 
     * @apiResource App\Http\Resources\V1\EventResource
     * @apiResourceModel App\Models\Event
     */
    public function update(UpdateEventRequest $request, $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $updatedEvent = $this->eventService->update(
            $request->user(),
            $event,
            $request->validated(),
            $request->file('flyer')
        );

        return response()->json([
            'message' => 'Event updated successfully',
            'data' => new EventResource($updatedEvent)
        ], 200);
    }

    /**
     * Delete event.
     * 
     * Permanently removes an event from the system. Only allowed for the creator or administrators.
     * 
     * @authenticated
     * @urlParam id int required The ID of the event. Example: 1
     * 
     * @response 200 {
     *  "message": "Event successfully deleted"
     * }
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
     * 
     * Displays complete information for a specific event, including loaded genres and vouch count.
     * 
     * @authenticated
     * @urlParam id int required The ID of the event. Example: 1
     * 
     * @apiResource App\Http\Resources\V1\EventResource
     * @apiResourceModel App\Models\Event
     * @response 403 {
     *  "message": "This event is pending verification and is not public yet."
     * }
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
     * 
     * Returns events created by the authenticated user, sorted by creation date.
     * 
     * @authenticated
     * @urlParam user_id int required The ID of the organizer. Example: 3
     * 
     * @apiResourceCollection App\Http\Resources\V1\EventResource
     * @apiResourceModel App\Models\Event
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
     * 
     * Retrieves a list of neighborhood names that currently have at least one verified active event.
     * 
     * @response ["Poblenou", "Eixample", "Gràcia"]
     */
    public function neighborhoods() {
        return Event::where('is_verified', true)->distinct()->pluck('neighborhood');
    }
}