<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
}
