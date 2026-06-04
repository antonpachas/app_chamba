<?php

namespace App\Http\Requests\Api\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class ToggleFavoriteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'cliente';
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'provider_service_id' => ['nullable', 'required_without:provider_profile_id', 'integer', 'exists:provider_services,id'],
            'provider_profile_id' => ['nullable', 'required_without:provider_service_id', 'integer', 'exists:provider_profiles,id'],
        ];
    }
}
