<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{

    public function index(): JsonResponse
    {

        $events = Event::where('is_verified', true)->get();

        $list = [];

        foreach ($events as $event) {
            $list[] = [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'location' => $event->location_name,
                'organizer' => $event->organizer->name,
                'vouch_count' => $event->vouches()->count(),
            ];
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

    public function update(Request $request, Event $event): JsonResponse
    {
        if ($event->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You are not authorized to edit this event'], 403);
        }

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

        $validated['is_verified'] = false;

        $event->update($validated);

        return response()->json([
            'message' => 'Event updated successfully, pending re-verification',
            'data'    => $event
        ], 200);
    }

    public function destroy(Request $request, Event $event): JsonResponse
    {
        if ($event->user_id !== $request->user()->id) {
            return response()->json(['message' => 'You are not authorized to delete this event'], 403);
        }
        $event->delete();

        return response()->json([
            'message' => 'Event successfully deleted'
        ], 200);
    }
}