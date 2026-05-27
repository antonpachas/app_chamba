<?php

namespace App\Http\Requests\Api\V1\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('is_active')) {
            return;
        }

        $raw = $this->input('is_active');
        $bool = filter_var($raw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        $this->merge([
            'is_active' => $bool ?? (bool) $raw,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // "present" acepta false; "required" a veces falla con is_active=false en JSON.
            'is_active' => ['present', 'boolean'],
        ];
    }
}
