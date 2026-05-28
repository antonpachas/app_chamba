<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderVisibilityEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'provider_profile_id',
        'provider_service_id',
        'search_event_id',
        'viewer_user_id',
        'source',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }
}

