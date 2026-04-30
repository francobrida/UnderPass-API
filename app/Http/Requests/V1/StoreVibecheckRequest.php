<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @authenticated
 * @header Authorization Bearer {token}
 * 
 * @bodyParam sound_score integer required Rating of the audio quality (1-5). Example: 5
 * @bodyParam safe_space_score integer required Rating of the environment's inclusivity and safety (1-5). Example: 4
 * @bodyParam comment string optional Detailed feedback about the experience. Max 500 characters. Example: Sound system was crystal clear, but the dancefloor was a bit too crowded.
 */
class StoreVibecheckRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Accessible by any authenticated user who attended the event.
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
            'sound_score' => 'required|integer|min:1|max:5',
            'safe_space_score' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:500',
        ];
    }
}