<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class ServiceSearchRequest extends FormRequest
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
            'category_id' => ['nullable', 'integer'],
            'district_id' => ['nullable', 'integer'],
            'ubigeo' => ['nullable', 'string', 'regex:/^\d{6}$/'],
            'keyword' => ['nullable', 'string', 'max:120'],
            'user_lat' => ['nullable', 'numeric'],
            'user_lng' => ['nullable', 'numeric'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:200'],
            'sort' => ['nullable', 'string', 'in:nearest,rating,recent'],
            'min_rating' => ['nullable', 'numeric', 'min:1', 'max:5'],
        ];
    }
}
