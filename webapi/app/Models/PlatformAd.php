<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformAd extends Model
{
    protected $fillable = [
        'title',
        'image_path',
        'link_url',
        'placement',
        'starts_at',
        'ends_at',
        'is_active',
        'sort_order',
        'impressions',
        'clicks',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function scopeActiveForPlacement($query, string $placement)
    {
        return $query
            ->where('is_active', true)
            ->where(function ($q) use ($placement) {
                $q->where('placement', $placement)->orWhere('placement', 'all');
            })
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('sort_order')
            ->orderByDesc('id');
    }
}
