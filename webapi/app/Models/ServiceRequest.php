<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
}
