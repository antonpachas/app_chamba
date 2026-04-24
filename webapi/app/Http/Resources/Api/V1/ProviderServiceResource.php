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
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'base_price' => $this->base_price,
            'price_type' => $this->price_type,
            'is_active' => (bool) $this->is_active,
            'category' => CategoryResource::make($this->whenLoaded('category')),
        ];
    }
}
