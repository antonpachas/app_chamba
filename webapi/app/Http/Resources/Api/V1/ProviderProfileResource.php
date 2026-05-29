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
        $media = app(\App\Services\MediaStorageService::class);

        return [
            'id' => $this->id,
            'cover_url' => $this->cover_path ? $media->publicUrl($this->cover_path) : null,
            'business_name' => $this->business_name,
            'razon_social' => $this->razon_social,
            'ruc' => $this->ruc,
            'description' => $this->description,
            'whatsapp' => $this->whatsapp,
            'contact_phone' => $this->contact_phone,
            'address_text' => $this->address_text,
            'business_hours' => $this->business_hours,
            'district_id' => $this->district_id,
            'is_verified' => (bool) $this->is_verified,
            'avg_rating' => $this->avg_rating,
            'total_reviews' => $this->total_reviews,
        ];
    }
}
