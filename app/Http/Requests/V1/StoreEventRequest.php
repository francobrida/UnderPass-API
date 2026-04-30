<?php

namespace App\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @authenticated
 * @header Authorization Bearer {token}
 * 
 * @bodyParam title string required The name of the event. Example: Night Moves: Industrial Techno
 * @bodyParam lineup string required List of performing artists. Example: Amelie Lens, Richie Hawtin, local support
 * @bodyParam description string required Brief summary of the event. Example: A deep dive into industrial sounds in an intimate basement.
 * @bodyParam genres int[] required The IDs of the musical genres. Example: [1, 2]
 * @bodyParam genres.* integer Each genre ID must exist in the genres table. Example: 3
 * @bodyParam location_name string required The venue or club name. Example: Input High Fidelity Dance Club
 * @bodyParam neighborhood string required The area of the city. Example: Poble Espanyol
 * @bodyParam date string required Event date (Y-m-d). Example: 2026-08-15
 * @bodyParam start_time string required Start time (H:i). Example: 23:59
 * @bodyParam end_time string required End time (H:i). Example: 06:00
 * @bodyParam price number required Base entrance fee. Example: 25.50
 * @bodyParam is_18_plus boolean required Age restriction flag. Example: true
 * @bodyParam price_info string optional Additional pricing details (e.g., drinks). Example: Includes one drink before 1:30 AM
 * @bodyParam ticket_link url optional External link for tickets. Example: https://ra.co/events/123456
 * @bodyParam flyer image optional The event poster (max 2MB).
 */
class StoreEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Accessible by authenticated organizers and admins.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'lineup' => 'required|string',
            'description' => 'required|string',
            'genres' => 'sometimes|array|min:1',
            'genres.*' => 'integer|exists:genres,id',
            'location_name' => 'required|string',
            'neighborhood' => 'required|string',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'price' => 'required|numeric',
            'is_18_plus' => 'required|boolean',
            'price_info'   => ['nullable', 'string'],
            'ticket_link'  => ['nullable', 'url'],
            'flyer'        => ['nullable', 'image', 'max:2048']
        ];
    }
}