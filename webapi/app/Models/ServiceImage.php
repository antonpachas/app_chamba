<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceImage extends Model
{
    protected $fillable = [
        'provider_service_id',
        'path',
        'sort_order',
    ];

    public function service(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'provider_service_id');
    }
}
