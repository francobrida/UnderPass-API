<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Vouch;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VouchController extends Controller
{
    public function index($id)
    {
        $event = Event::findOrFail($id);

        $vouchers = $event->vouches()->get();

        $data = [];
        
        foreach ($vouchers as $user) {
            $data[] = [
                'user_id'   => $user->id,
                'user_name' => $user->name,
            ];
        }

        return response()->json([
            'data' => $data
        ], 200);
    }
    
    public function store(int $id): JsonResponse
    {
        $event_id = $id;
        $event = Event::findOrFail($event_id);
        $user = Auth::user();

        if ($event->user_id === $user->id) {
            return response()->json(['message' => 'You cannot vote for your own event.'], 403);
        }

        if ($event->date < now()->toDateString()) {
            return response()->json(['message' => 'You cannot vote for a past event.'], 422);
        }

        $alreadyVouched = Vouch::where('user_id', $user->id)->where('event_id', $event->id)->exists();

        if ($alreadyVouched) {
            return response()->json(['message' => 'You have already voted for this event.'], 422);
        }

        Vouch::create([
            'user_id' => $user->id,
            'event_id' => $event->id,
        ]);

        $vouchCount = $event->vouches()->count();
        
        if ($vouchCount >= 3 && !$event->is_verified) { // Quitar este 3 Hardcodeado y poner en una constante luego en capa de servicio
            $event->update(['is_verified' => true]);
            $event->organizer->update(['role' => \App\Enums\UserRole::ORGANIZER]);
        }

        return response()->json([
            'message' => 'Vouch added successfully.',
            'current_vouches' => $vouchCount,
            'is_verified' => (bool) $event->is_verified
        ], 201);
    }
}

