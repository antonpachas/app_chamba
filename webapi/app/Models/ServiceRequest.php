<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    protected $fillable = [
        'client_user_id',
        'provider_service_id',
        'message',
        'contact_channel',
        'status',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function providerService(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(ServiceQuote::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(ServicePayment::class)
            ->whereIn('status', ['pendiente_revision', 'en_custodia', 'liberado']);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ServicePayment::class);
    }
}
