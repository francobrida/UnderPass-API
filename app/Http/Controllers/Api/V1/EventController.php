<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Policies\EventPolicy;

class EventController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request): JsonResponse
    {
        // Waiting room
        if ($request->query('verified') === 'false') {
            $events = Event::where('is_verified', false)->with('organizer')->latest()->get();
        } else { 
            // Main dashboard
            $events = Event::where('is_verified', true)->with('organizer')->get();
        }
    
        $list = [];

        foreach ($events as $event) {
            $eventData = [
                'id'    => $event->id,
                'title'    => $event->title,
                'organizer'   => $event->organizer->name,
                'is_verified'   => (bool) $event->is_verified,
                'vouch_count'   => $event->vouches()->count(),
            ];

            $list[] = $eventData;
        }

        return response()->json(['data' => $list]);
    }


    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'lineup' => 'required|string',
            'description' => 'required|string',
            'location_name' => 'required|string',
            'neighborhood' => 'required|string',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'required',
            'price' => 'required|numeric',
            'is_18_plus' => 'required|boolean',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['is_verified'] = false;

        $event = Event::create($validated);

        return response()->json([
            'message' => 'Event created successfully, pending verification',
            'data' => $event
        ], 201);
    }

    public function update(Request $request, Event $id): JsonResponse
    {
        $event = $id;

        $this->authorize('update', $event);

        $validated = $request->validate([
            'title'         => 'required|string|max:255',
            'lineup'        => 'required|string',
            'description'   => 'required|string',
            'location_name' => 'required|string',
            'neighborhood'  => 'required|string',
            'date'          => 'required|date|after_or_equal:today',
            'start_time'    => 'required',
            'end_time'      => 'required',
            'price'         => 'required|numeric',
            'is_18_plus'    => 'required|boolean',
        ]);

        if ($request->user()->role !== UserRole::ADMIN) {
            $validated['is_verified'] = false; 
        } else {
            $validated['is_verified'] = $request->input('is_verified', $event->is_verified);
        }

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully, pending re-verification',
            'data'    => $event
        ], 200);
    }

    public function destroy(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        $this->authorize('delete', $event);

        $event->delete();

        return response()->json([
            'message' => 'Event successfully deleted'
        ], 200);
    }

    public function show(Request $request,Event $id): JsonResponse
    {
        $event = $id;

        if (!$event->is_verified && $event->user_id !== $request->user()->id) {
            return response()->json([
                'message' => 'This event is pending verification and is not public yet.'
            ], 403);
        }

        return response()->json([
            'data' => [
                'id'  => $event->id,
                'title'  => $event->title,
                'lineup'   => $event->lineup,
                'description'   => $event->description,
                'date'  => $event->date,
                'start_time' => $event->start_time,
                'end_time'  => $event->end_time,
                'location_name' => $event->location_name,
                'neighborhood'  => $event->neighborhood,
                'price'  => (float) $event->price,
                'is_18_plus'  => (bool) $event->is_18_plus,
                'is_verified'  => (bool) $event->is_verified,
                'organizer'  => $event->organizer->name,
                'vouch_count'  => $event->vouches()->count(),
                'created_at'  => $event->created_at->toDateTimeString(),
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
            $eventData = [
                'id'    => $event->id,
                'title'   => $event->title,
                'date'     => $event->date,
                'location'   => $event->location_name,
                'organizer'   => $user->name, 
                'is_verified'   => (bool) $event->is_verified,
                'vouch_count'   => $event->vouches()->count(),
            ];

            $list[] = $eventData;
        }

        return response()->json(['data' => $list], 200);
    }
}