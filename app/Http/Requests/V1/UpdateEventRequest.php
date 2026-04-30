<?php

namespace App\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @authenticated
 * @header Authorization Bearer {token}
 * 
 * @bodyParam title string optional The updated name of the event. Example: Night Moves: Industrial Techno
 * @bodyParam lineup string optional Updated list of performing artists. Example: Amelie Lens, Richie Hawtin, local support
 * @bodyParam description string optional Updated summary of the event. Example: A deep dive into industrial sounds in an intimate basement.
 * @bodyParam genres int[] optional Updated array of genre IDs. Example: [1, 2, 3]
 * @bodyParam location_name string optional Updated venue or club name. Example: Input High Fidelity Dance Club
 * @bodyParam neighborhood string optional Updated area of the city. Example: Poble Espanyol
 * @bodyParam date string optional Updated event date (Y-m-d). Example: 2026-08-15
 * @bodyParam start_time string optional Updated start time (H:i). Example: 23:59
 * @bodyParam end_time string optional Updated end time (H:i). Example: 06:00
 * @bodyParam price number optional Updated entrance fee. Example: 25.50
 * @bodyParam is_18_plus boolean optional Updated age restriction flag. Example: true
 * @bodyParam price_info string optional Updated pricing details. Example: Includes one drink before 1:30 AM
 * @bodyParam ticket_link url optional Updated external ticket link. Example: https://ra.co/events/123456
 * @bodyParam flyer image optional A new event poster (replaces existing).
 */
class UpdateEventRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * Access is restricted to the event creator (organizer) or an administrator.
     */
    public function authorize(): bool
    {
        $eventId = $this->route('id') ?? $this->route('event');

        // Handles both Route Model Binding and raw ID parameters
        $event = $eventId instanceof \App\Models\Event ? $eventId : \App\Models\Event::findOrFail($eventId);

        return $this->user()->id === $event->user_id || $this->user()->role === \App\Enums\UserRole::ADMIN;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * All fields are optional (sometimes), allowing for partial updates of the event resource.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title'         => 'sometimes|string|max:255',
            'lineup'        => 'sometimes|string',
            'description'   => 'sometimes|string',
            'genres'        => 'sometimes|array|min:1',
            'genres.*'      => 'integer|exists:genres,id',
            'location_name' => 'sometimes|string',
            'neighborhood'  => 'sometimes|string',
            'date'          => 'sometimes|date|after_or_equal:today',
            'start_time'    => 'sometimes|date_format:H:i',
            'end_time'      => 'sometimes|date_format:H:i',
            'price'         => 'sometimes|numeric',
            'is_18_plus'    => 'sometimes|boolean',
            'price_info'    => 'sometimes|string',
            'ticket_link'   => 'sometimes|url',
            'flyer'         => ['sometimes', 'image', 'max:2048'],
            'is_verified'   => 'sometimes|boolean',
            'vouch_count'   => 'sometimes|integer',
        ];
    }
}