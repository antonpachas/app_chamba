<?php

namespace App\Http\Requests\Api\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class ToggleFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_profile_id' => ['required', 'integer', 'exists:provider_profiles,id'],
        ];
    }
}
