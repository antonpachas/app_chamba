<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupportMessageRequest extends FormRequest
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
            'body' => ['required', 'string', 'min:1', 'max:2000'],
            'images' => ['nullable', 'array', 'max:2'],
            'images.*' => ['file', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }
}
