<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const STATUSES = [
        'abierto',
        'en_progreso',
        'esperando_usuario',
        'pendiente_soporte',
        'resuelto',
        'cerrado',
    ];

    public const CATEGORIES = [
        'cuenta',
        'anuncios',
        'pagos',
        'tecnico',
        'otro',
    ];

    protected $fillable = [
        'user_id',
        'subject',
        'category',
        'status',
        'last_message_at',
        'user_last_read_at',
        'admin_last_read_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'user_last_read_at' => 'datetime',
            'admin_last_read_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    public function isClosed(): bool
    {
        return $this->status === 'cerrado';
    }

    public function userCanReply(): bool
    {
        return $this->status !== 'cerrado';
    }
}
