<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderService extends Model
{
    protected $fillable = [
        'provider_profile_id',
        'category_id',
        'title',
        'description',
        'base_price',
        'price_type',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function serviceImages(): HasMany
    {
        return $this->hasMany(ServiceImage::class, 'provider_service_id');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ServiceImage::class, 'provider_service_id')->orderBy('sort_order');
    }
}
