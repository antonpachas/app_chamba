<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessageAttachment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'support_message_id',
        'path',
        'sort_order',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }
}
