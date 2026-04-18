<?php

namespace App\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam title string Example: Night Moves: Rescheduled
 * @bodyParam price number Example: 30.00
 * @bodyParam is_18_plus boolean Example: true
 */
class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        $event = $this->route('id');

        return $this->user()->id === $event->user_id || $this->user()->role === \App\Enums\UserRole::ADMIN;
    }

    public function rules(): array
    {
        return [
            'title'         => 'sometimes|string|max:255',
            'lineup'        => 'sometimes|string',
            'description'   => 'sometimes|string',
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