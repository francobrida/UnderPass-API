<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserRankingResource extends JsonResource
{

    public function toArray(Request $request): array
    {
        return [
            'name'         => $this->name,
            'stamps_count' => $this->stamps_count ?? 0,
            'points'       => $this->points,
        ];
    }
}
