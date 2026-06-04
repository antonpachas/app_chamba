<?php

namespace App\Http\Requests\Api\V1;

use App\Models\SupportTicket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupportTicketRequest extends FormRequest
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
            'subject' => ['required', 'string', 'min:5', 'max:200'],
            'category' => ['required', Rule::in(SupportTicket::CATEGORIES)],
            'body' => ['required', 'string', 'min:10', 'max:2000'],
            'images' => ['nullable', 'array', 'max:2'],
            'images.*' => ['file', 'mimes:jpeg,png,webp', 'max:5120'],
        ];
    }
}
