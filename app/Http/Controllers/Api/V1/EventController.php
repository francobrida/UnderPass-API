<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;

class EventController extends Controller
{

    public function index(): JsonResponse
    {

        $events = Event::where('is_verified', true)->get();

        $list = [];

        foreach ($events as $event) {
            $list[] = [
                'id'           => $event->id,
                'title'        => $event->title,
                'description'  => $event->description,
                'location'     => $event->location_name,
                'organizer'    => $event->organizer->name,
                'vouch_count'  => $event->vouches()->count(),
            ];
        }

        return response()->json(['data' => $list]);
    }
}