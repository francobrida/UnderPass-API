<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @bodyParam sound_score integer Example: 5
 * @bodyParam safe_space_score integer Example: 4
 * @bodyParam comment string Example: Sound system was crystal clear, but the dancefloor was a bit too crowded.
 */
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
