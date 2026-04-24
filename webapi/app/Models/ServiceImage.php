<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceImage extends Model
{
    protected $fillable = [
        'service_id',
        'image_url',
    ];

    public function providerService(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class, 'service_id');
    }
}
