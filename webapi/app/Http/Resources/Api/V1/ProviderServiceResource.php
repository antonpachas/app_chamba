<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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

        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'price_type' => $this->price_type,
            'is_active' => (bool) $this->is_active,
            'category' => CategoryResource::make($this->whenLoaded('category')),
            'images' => $images->map(fn ($i) => [
                'id' => $i->id,
                'url' => $media->publicUrl($i->path),
                'sort_order' => $i->sort_order,
            ])->values(),
            'cover_image_url' => $images->isNotEmpty() ? $media->publicUrl($images->first()->path) : null,
        ];
    }
}
