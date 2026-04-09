<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Stamp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class StampController extends Controller
{
    
    public function index(): JsonResponse
    {
        $stamps = Stamp::where('user_id', Auth::id())
            ->with('event:id,title,date,location') // Cargamos info del evento para la UI
            ->latest()
            ->get();

        return response()->json([
            'count' => $stamps->count(),
            'data' => $stamps
        ], 200);
    }

    
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        $event = Event::findOrFail($request->event_id);
        $now = Carbon::now();
        $eventDate = Carbon::parse($event->date);

        
        if ($now->lt($eventDate->startOfDay())) {
            return response()->json([
                'message' => 'This event has not started yet.'
            ], 422);
        }

       
        if ($now->gt($eventDate->addDay()->endOfDay())) {
            return response()->json([
                'message' => 'This QR code has expired.'
            ], 422);
        }

        $exists = Stamp::where('user_id', Auth::id())
            ->where('event_id', $event->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'You already collected this stamp!'
            ], 422);
        }


        $stamp = Stamp::create([
            'user_id' => Auth::id(),
            'event_id' => $event->id,
            'scanned_at' => $now,
        ]);

        return response()->json([
            'message' => 'Stamp collected successfully!',
            'data' => $stamp->load('event:id,title')
        ], 201);
    }
}