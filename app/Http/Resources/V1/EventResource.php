<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'title'      => $this->title,
            'lineup'        => $this->lineup,
            'description'   => $this->description,
            'genres'   => GenreResource::collection($this->whenLoaded('genres')),
            'date'       => $this->date,
            'start_time'  => $this->start_time,
            'end_time'    => $this->end_time,
            'location'    => $this->location_name,
            'neighborhood'  => $this->neighborhood,
            'price'     => (float) $this->price,
            'price_info' => $this->price_info,
            'ticket_link' => $this->ticket_link,
            'is_18_plus'    => (bool) $this->is_18_plus,
            'is_verified'   => (bool) $this->is_verified,
            'flyer'   => $this->flyer,
            'organizer'     => $this->organizer?->name, 
            'vouch_count'   => $this->vouches_count ?? $this->vouches()->count(),
            'created_at'    => $this->created_at->toDateTimeString(),
            'can_vibe_check' => $this->isReadyForVibeCheck(),
            'has_vouched' => $this->vouches()->where('user_id', auth()->id())->exists(),
            'is_mine' => $this->user_id === auth()->id(),
            
        ];
    }
}