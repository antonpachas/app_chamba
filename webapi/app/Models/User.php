<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'password_hash',
        'role',
        'status',
        'avatar_path',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function providerProfile(): HasOne
    {
        return $this->hasOne(ProviderProfile::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    public function activeSubscription(): ?UserSubscription
    {
        return $this->subscriptions()
            ->whereIn('status', ['trial', 'active'])
            ->with('plan')
            ->latest('id')
            ->first();
    }

    public function isPro(): bool
    {
        $sub = $this->activeSubscription();

        return $sub ? $sub->isPro() : false;
    }
}
