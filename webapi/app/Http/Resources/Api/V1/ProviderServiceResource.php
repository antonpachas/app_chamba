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
        $visible = (bool) $this->is_active && ! $expired;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'price_type' => $this->price_type,
            'is_active' => (bool) $this->is_active,
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
            'location_ids' => $this->when(
                $this->relationLoaded('locations'),
                fn () => $this->locations->pluck('id')->values(),
            ),
        ];
    }
}
