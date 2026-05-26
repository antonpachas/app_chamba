<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    protected $fillable = [
        'subscription_id',
        'user_id',
        'amount',
        'currency',
        'payment_method',
        'payment_reference',
        'period_start',
        'period_end',
        'status',
        'paid_at',
        'confirmed_at',
        'confirmed_by',
        'rejection_reason',
        'notes',
        'proof_image_path',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(UserSubscription::class, 'subscription_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
