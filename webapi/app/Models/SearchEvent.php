<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'category_id',
        'query',
        'district_id',
        'ubigeo',
        'user_lat',
        'user_lng',
        'radius_km',
        'results_count',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'user_lat' => 'float',
            'user_lng' => 'float',
            'radius_km' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
