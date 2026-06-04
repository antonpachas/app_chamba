<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'client_user_id',
        'provider_profile_id',
        'provider_user_id',
        'provider_service_id',
        'service_request_id',
        'kind',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class, 'provider_profile_id');
    }

    public function providerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_user_id');
    }
}
