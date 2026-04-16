<?php

namespace App\Http\Requests\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
            'date' => 'required|date|after_or_equal:today|date_format:d-m-Y',
            'start_time' => 'required',
            'end_time' => 'required',
            'price' => 'required|numeric',
            'is_18_plus' => 'required|boolean',
        ];
    }
}
