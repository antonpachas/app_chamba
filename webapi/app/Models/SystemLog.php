<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'level',
        'channel',
        'message',
        'exception_class',
        'file',
        'line',
        'trace',
        'context',
        'http_status',
        'request_method',
        'request_path',
        'user_id',
        'ip',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
