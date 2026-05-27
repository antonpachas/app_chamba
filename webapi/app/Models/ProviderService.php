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
        'published_at',
        'expires_at',
        'deactivated_at',
        'duration_days',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'duration_days' => 'integer',
        ];
    }

    public function scopeVisible($query)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
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

    public function locations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(
            ProviderLocation::class,
            'provider_service_locations',
            'provider_service_id',
            'provider_location_id',
        );
    }
}
