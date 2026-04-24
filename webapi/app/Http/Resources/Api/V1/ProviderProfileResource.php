<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProviderProfile */
class ProviderProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'business_name' => $this->business_name,
            'description' => $this->description,
            'whatsapp' => $this->whatsapp,
            'contact_phone' => $this->contact_phone,
            'address_text' => $this->address_text,
            'district_id' => $this->district_id,
            'is_verified' => (bool) $this->is_verified,
            'avg_rating' => $this->avg_rating,
            'total_reviews' => $this->total_reviews,
        ];
    }
}
