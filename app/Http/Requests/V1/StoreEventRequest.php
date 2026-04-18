<?php

namespace App\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam title string Example: Night Moves: Industrial Techno
 * @bodyParam lineup string Example: Amelie Lens, Richie Hawtin, local support
 * @bodyParam description string Example: A deep dive into industrial sounds in an intimate basement.
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
class StoreEventRequest extends FormRequest
{
    
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'lineup' => 'required|string',
            'description' => 'required|string',
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
