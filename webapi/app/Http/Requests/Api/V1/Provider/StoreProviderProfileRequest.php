<?php

namespace App\Http\Requests\Api\V1\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderProfileRequest extends FormRequest
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
            'business_name' => ['nullable', 'string', 'max:150'],
            'razon_social' => ['required', 'string', 'max:200'],
            'ruc' => ['nullable', 'string', 'size:11', 'regex:/^\d{11}$/'],
            'description' => ['nullable', 'string'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'contact_phone' => ['nullable', 'string', 'max:20'],
            'address_text' => ['nullable', 'string', 'max:255'],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
        ];
    }
}
