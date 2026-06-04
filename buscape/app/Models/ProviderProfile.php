<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'cover_path',
        'business_name',
        'razon_social',
        'ruc',
        'description',
        'whatsapp',
        'contact_phone',
        'address_text',
        'business_hours',
        'district_id',
        'listing_duration_days_override',
        'is_verified',
        'avg_rating',
        'total_reviews',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'listing_duration_days_override' => 'integer',
            'business_hours' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function providerServices(): HasMany
    {
        return $this->hasMany(ProviderService::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ProviderLocation::class)->orderByDesc('is_primary')->orderBy('label');
    }

    public function activeLocations(): HasMany
    {
        return $this->hasMany(ProviderLocation::class)->where('is_active', 1)->orderByDesc('is_primary')->orderBy('label');
    }
}
