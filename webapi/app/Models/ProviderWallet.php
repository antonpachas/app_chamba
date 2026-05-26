<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderWallet extends Model
{
    protected $fillable = [
        'provider_profile_id',
        'balance',
        'total_earned',
        'total_withdrawn',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'yape_phone',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
        ];
    }

    public function providerProfile(): BelongsTo
    {
        return $this->belongsTo(ProviderProfile::class);
    }
}
