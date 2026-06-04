<?php

namespace App\Http\Requests\Api\V1\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequestRequest extends FormRequest
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
            'provider_service_id' => ['required', 'integer', 'exists:provider_services,id'],
            'message' => ['nullable', 'string'],
            'contact_channel' => ['required', Rule::in(['telefono', 'whatsapp', 'app'])],
        ];
    }
}
