<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'is_staff',
        'body',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'is_staff' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
