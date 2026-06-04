<?php

namespace App\Http\Requests\Api\V1\Provider;

use App\Models\ProviderService;
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
            'listing_type' => ['nullable', Rule::in([ProviderService::TYPE_PRESENCIA, ProviderService::TYPE_PROMOCION])],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string'],
            'base_price' => ['nullable', 'numeric', 'min:0'],
            'price_type' => ['required', Rule::in(['fijo', 'desde', 'cotizar'])],
            'district_id' => ['required', 'integer', 'exists:districts,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'province_id' => ['nullable', 'integer', 'exists:provinces,id'],
            'location_label' => ['nullable', 'string', 'max:120'],
            'address_text' => ['nullable', 'string', 'max:500'],
            'ubigeo' => ['nullable', 'string', 'max:6'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'business_hours' => ['nullable', 'array'],
            'location_ids' => ['nullable', 'array'],
            'location_ids.*' => ['integer', 'exists:provider_locations,id'],
        ];
    }
}
