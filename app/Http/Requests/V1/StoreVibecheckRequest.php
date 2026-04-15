<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreVibecheckRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true; 
    }

    public function rules(): array
    {
        return [
            'sound_score' => 'required|integer|min:1|max:5',
            'safe_space_score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ];
    }
}
