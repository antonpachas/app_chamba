<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderServiceLocation extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'provider_service_locations';

    protected $fillable = [
        'provider_service_id',
        'provider_location_id',
    ];

    public function listing(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'provider_service_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(ProviderLocation::class, 'provider_location_id');
    }
}
