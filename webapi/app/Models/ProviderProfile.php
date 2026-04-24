<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProviderProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'description',
        'whatsapp',
        'contact_phone',
        'address_text',
        'district_id',
        'is_verified',
        'avg_rating',
        'total_reviews',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
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
}
