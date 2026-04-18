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


class EventController extends Controller
{
    use AuthorizesRequests;

    public function __construct(private EventService $eventService){}

    public function index(Request $request): JsonResponse
    {
        $events = $this->eventService->filter($request->all());

        $events->load('organizer')->loadCount('vouches');

        return EventResource::collection($events)->response();
    }

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
     * Edit own event.
     * * This endpoint allows an organizer to update the details of an event they created.
     * * @urlParam id int required The ID of the event to edit. Example: 1
     */
    public function update(UpdateEventRequest $request, $id): JsonResponse
    {
        $event = $id;

        $updatedEvent = $this->eventService->update(
            $request->user(), 
            $event, 
            $request->validated(), 
            $request->file('flyer')
        );

        return response()->json([
            'message' => 'Event updated successfully',
            'data'    => $updatedEvent
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        $this->authorize('delete', $event);

        $this->eventService->delete($event);

        return response()->json([
            'message' => 'Event successfully deleted'
        ], 200);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        if (!$event->is_verified && $event->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'This event is pending verification and is not public yet.'
            ], 403);
        }

        $event->load('organizer')->loadCount('vouches');

        return (new EventResource($event))->response();
    }

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
}