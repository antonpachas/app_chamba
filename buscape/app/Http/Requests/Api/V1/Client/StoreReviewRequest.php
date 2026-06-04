<?php

namespace App\Http\Requests\Api\V1\Client;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
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
            'service_request_id' => ['nullable', 'required_without:provider_service_id', 'integer', 'exists:service_requests,id'],
            'provider_service_id' => ['nullable', 'required_without:service_request_id', 'integer', 'exists:provider_services,id'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:500'],
        ];
    }
}
