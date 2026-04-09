<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Vibecheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VibecheckController extends Controller
{
    
    public function store(Request $request, int $id): JsonResponse
    {
        
        $event = Event::findOrFail($id);
        $user = Auth::user();

      
        if ($event->date >= now()->toDateString()) {
            return response()->json([
                'message' => 'You cannot review an event that has not ended yet.'
            ], 422);
        }

        
        $alreadyReviewed = Vibecheck::where('user_id', $user->id)->where('event_id', $event->id)->exists();

        if ($alreadyReviewed) {
            return response()->json([
                'message' => 'You have already reviewed this event.'
            ], 422);
        }

    
        $validated = $request->validate([
            'sound_score' => 'required|integer|min:1|max:5',
            'safe_space_score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ]);

        $vibecheck = Vibecheck::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
            'sound_score' => $validated['sound_score'],
            'safe_space_score' => $validated['safe_space_score'],
            'comment' => $validated['comment'] ?? null,
        ]);

        return response()->json([
            'message' => 'Vibecheck added successfully.',
            'data' => $vibecheck
        ], 201);
    }

    public function index(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);

        
        if ($event->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized. You can only view feedback for your own events.'], 403);
        }


        $vibechecks = $event->vibechecks()->with('user:id,name')->get();

        return response()->json([
            'event_title' => $event->title,
            'total_reviews' => $vibechecks->count(),
            'average_sound' => round($vibechecks->avg('sound_score'), 1),
            'average_safety' => round($vibechecks->avg('safe_space_score'), 1),
            'data' => $vibechecks
        ], 200);
    }
    
}