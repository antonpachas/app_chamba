<?php

namespace App\Http\Requests\Api\V1\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceStatusRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
        ];
    }
}
