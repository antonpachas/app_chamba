<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/** @mixin \App\Models\ProviderService */
class ProviderServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $media = app(\App\Services\MediaStorageService::class);
        $images = $this->relationLoaded('images')
            ? $this->images
            : $this->images()->get();

        $expiresAt = $this->expires_at ? Carbon::parse($this->expires_at) : null;
        $expired = $expiresAt !== null && $expiresAt->isPast();
        $adminHidden = (bool) $this->admin_hidden;
        $visible = ! $adminHidden && (bool) $this->is_active && ! $expired;

        return [
            'id' => $this->id,
            'listing_type' => $this->listing_type ?? 'presencia',
            'title' => $this->title,
            'description' => $this->description,
            'location_label' => $this->location_label,
            'address_text' => $this->address_text,
            'department_id' => $this->department_id,
            'province_id' => $this->province_id,
            'district_id' => $this->district_id,
            'ubigeo' => $this->ubigeo,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'district' => $this->whenLoaded('district', fn () => [
                'id' => $this->district?->id,
                'name' => $this->district?->name,
            ]),
            'base_price' => $this->base_price,
            'price_type' => $this->price_type,
            'is_active' => (bool) $this->is_active,
            'admin_hidden' => $adminHidden,
            'admin_hidden_reason' => $this->admin_hidden_reason,
            'published_at' => $this->published_at,
            'expires_at' => $this->expires_at,
            'duration_days' => $this->duration_days,
            'deactivated_at' => $this->deactivated_at,
            'is_expired' => $expired,
            'is_visible' => $visible,
            'days_remaining' => $expiresAt && $expiresAt->isFuture()
                ? max(0, (int) now()->startOfDay()->diffInDays($expiresAt->startOfDay(), false))
                : 0,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'images' => $images->map(fn ($i) => [
                'id' => $i->id,
                'url' => $media->publicUrl($i->path),
                'sort_order' => $i->sort_order,
            ])->values(),
            'cover_image_url' => $images->isNotEmpty() ? $media->publicUrl($images->first()->path) : null,
            'business_hours' => $this->business_hours,
        ];
    }
}
