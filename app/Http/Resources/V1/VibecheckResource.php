<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VibecheckResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'event_id'    => $this->event_id,
            'user'  => [
                'id'   => $this->user_id,
                'name' => $this->user?->name ?? 'Anonymous',
            ],
            'sound_score'      => (int) $this->sound_score,
            'safe_space_score' => (int) $this->safe_space_score,
            'comment'          => $this->comment,
            'average_score'    => round(($this->sound_score + $this->safe_space_score) / 2, 1),
            'created_at'       => $this->created_at->toDateTimeString(),
        ];
    }
}