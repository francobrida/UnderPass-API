<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Vouch;
use App\Services\VouchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class VouchController extends Controller
{
    
    public function __construct(private VouchService $vouchService) {}

    public function store(int $id): JsonResponse
    {
        $event = Event::findOrFail($id);
        
        $result = $this->vouchService->addVouch(Auth::user(), $event);

        return response()->json([
            'message' => 'Vouch added successfully.',
            'current_vouches' => $result['count'],
            'is_verified' => $result['is_verified']
        ], 201);
    }

    public function index($id): JsonResponse
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

}

