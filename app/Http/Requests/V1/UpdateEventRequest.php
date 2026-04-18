<?php

namespace App\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam title string Example: Night Moves: Industrial Techno
 * @bodyParam lineup string Example: Amelie Lens, Richie Hawtin, local support
 * @bodyParam description string Example: A deep dive into industrial sounds in an intimate basement.
 * @bodyParam genres array An array of genre IDs associated with the event. Example: [1, 2, 3]
 * @bodyParam genres.* integer Each genre ID must exist in the genres table. Example: 1
 * @bodyParam location_name string Example: Input High Fidelity Dance Club
 * @bodyParam neighborhood string Example: Poble Espanyol
 * @bodyParam date string Example: 2026-08-15
 * @bodyParam start_time string Example: 23:59
 * @bodyParam end_time string Example: 06:00
 * @bodyParam price number Example: 25.50
 * @bodyParam is_18_plus boolean Example: true
 * @bodyParam price_info string Example: Includes one drink before 1:30 AM
 * @bodyParam ticket_link url Example: https://ra.co/events/123456
 * @bodyParam flyer image The event poster. Example: (binary)
 */
class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $eventId = $this->route('id') ?? $this->route('event');

        $event = $eventId instanceof \App\Models\Event ? $eventId : \App\Models\Event::findOrFail($eventId);

        return $this->user()->id === $event->user_id || $this->user()->role === \App\Enums\UserRole::ADMIN;
    }

    public function rules(): array
    {
        return [
            'title'         => 'sometimes|string|max:255',
            'lineup'        => 'sometimes|string',
            'description'   => 'sometimes|string',
            'genres' => 'sometimes|array|min:1',
            'genres.*' => 'integer|exists:genres,id',
            'location_name' => 'sometimes|string',
            'neighborhood'  => 'sometimes|string',
            'date'          => 'sometimes|date|after_or_equal:today',
            'start_time'    => 'sometimes|date_format:H:i',
            'end_time'      => 'sometimes|date_format:H:i',
            'price'         => 'sometimes|numeric',
            'is_18_plus'    => 'sometimes|boolean',
            'price_info'   => 'sometimes|string',
            'ticket_link'  => 'sometimes|url',
            'flyer'        => ['sometimes', 'image', 'max:2048']
        ];
    }
}