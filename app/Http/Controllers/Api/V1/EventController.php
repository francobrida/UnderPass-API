<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Enums\UserRole;
use App\Http\Requests\V1\{StoreEventRequest, UpdateEventRequest};
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
    
        $list = [];
        foreach ($events as $event) {
            $list[] = [
                'id'            => $event->id,
                'title'         => $event->title,
                'organizer'     => $event->organizer->name,
                'is_verified'   => (bool) $event->is_verified,
                'vouch_count'   => $event->vouches()->count(),
            ];
        }

        return response()->json(['data' => $list]);
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

    public function update(UpdateEventRequest $request, Event $id): JsonResponse
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

    public function show(Request $request, Event $id): JsonResponse
    {
        $event = $id;

        if (!$event->is_verified && $event->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'This event is pending verification and is not public yet.'
            ], 403);
        }

        return response()->json([
            'data' => [
                'id'            => $event->id,
                'title'         => $event->title,
                'lineup'        => $event->lineup,
                'description'   => $event->description,
                'date'          => $event->date,
                'start_time'    => $event->start_time,
                'end_time'      => $event->end_time,
                'location_name' => $event->location_name,
                'neighborhood'  => $event->neighborhood,
                'price'         => (float) $event->price,
                'is_18_plus'    => (bool) $event->is_18_plus,
                'is_verified'   => (bool) $event->is_verified,
                'organizer'     => $event->organizer->name,
                'vouch_count'   => $event->vouches()->count(),
                'created_at'    => $event->created_at->toDateTimeString(),
            ]
        ]);
    }

    public function getUserEvents(int $user_id): JsonResponse
    {
        $user = User::findOrFail($user_id);

        if (Auth::user()?->id !== $user->id && Auth::user()?->role !== UserRole::ADMIN) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $events = $user->events()->latest()->get();

        $list = [];
        foreach ($events as $event) {
            $list[] = [
                'id'            => $event->id,
                'title'         => $event->title,
                'date'          => $event->date,
                'location'      => $event->location_name,
                'organizer'     => $user->name, 
                'is_verified'   => (bool) $event->is_verified,
                'vouch_count'   => $event->vouches()->count(),
            ];
        }

        return response()->json(['data' => $list], 200);
    }
}