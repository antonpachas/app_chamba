<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $fillable = [
        'name',
        'ubigeo_code',
        'latitude',
        'longitude',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function provinces(): HasMany
    {
        return $this->hasMany(Province::class);
    }
}
