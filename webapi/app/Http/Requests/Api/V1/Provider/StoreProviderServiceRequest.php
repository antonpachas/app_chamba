<?php

namespace App\Http\Requests\Api\V1\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProviderServiceRequest extends FormRequest
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
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['required', Rule::in(['fijo', 'desde', 'cotizar'])],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:provider_locations,id'],
        ];
    }
}
