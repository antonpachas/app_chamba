<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'service_request_id',
        'client_user_id',
        'provider_profile_id',
        'rating',
        'comment',
    ];

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
