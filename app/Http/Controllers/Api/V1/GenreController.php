<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Http\Resources\V1\GenreResource;
use Illuminate\Http\JsonResponse;

/**
 * @group Events
 * 
 * Management of musical genres for event categorization.
 */
class GenreController extends Controller
{
    /**
     * List all genres.
     * 
     * Returns a complete list of musical genres available for filtering and event creation.
     * 
     * @response 200 {
     *  "data": [
     *    { "id": 1, "name": "Techno", "slug": "techno" },
     *    { "id": 2, "name": "Industrial", "slug": "industrial" }
     *  ]
     * }
     */
    public function index(): JsonResponse
    {
        $genres = Genre::all();

        return GenreResource::collection($genres)->response();
    }
}