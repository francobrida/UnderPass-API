<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{

   public function toArray(Request $request): array
    {
        return [
            'id'  => $this->id,
            'name'    => $this->name,
            'email'    => $this->email, 
            'role'   => is_object($this->role) ? $this->role->value : $this->role,
            'points' => (int) ($this->points ?? 0),
            'created_at' => $this->created_at->toDateTimeString(),
            'stamps_count' => $this->whenCounted('stamps'),
        ];
    }
}
