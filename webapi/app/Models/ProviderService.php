<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderService extends Model
{
    public const TYPE_PRESENCIA = 'presencia';

    public const TYPE_PROMOCION = 'promocion';

    protected $fillable = [
        'provider_profile_id',
        'category_id',
        'title',
        'description',
        'listing_type',
        'location_label',
        'address_text',
        'department_id',
        'province_id',
        'district_id',
        'ubigeo',
        'latitude',
        'longitude',
        'business_hours',
        'base_price',
        'price_type',
        'is_active',
        'admin_hidden',
        'admin_hidden_at',
        'admin_hidden_reason',
        'admin_hidden_by',
        'published_at',
        'expires_at',
        'deactivated_at',
        'duration_days',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'admin_hidden' => 'boolean',
            'admin_hidden_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'duration_days' => 'integer',
            'business_hours' => 'array',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function isPresencia(): bool
    {
        return ($this->listing_type ?? self::TYPE_PRESENCIA) === self::TYPE_PRESENCIA;
    }

    public function isPromocion(): bool
    {
        return $this->listing_type === self::TYPE_PROMOCION;
    }

    public function scopeVisible($query)
    {
        return $query
            ->where('admin_hidden', false)
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
