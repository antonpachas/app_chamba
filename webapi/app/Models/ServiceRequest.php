<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ServiceRequest extends Model
{
    /** Estados Busca PE (solo contacto, sin custodia). */
    public const STATUSES = [
        'nuevo',
        'visto',
        'cerrado',
        'cancelado',
    ];

    /** @deprecated Estados legacy del modo custodia */
    public const LEGACY_STATUSES = [
        'contactado', 'cotizado', 'aceptado', 'pagado_pendiente', 'en_custodia',
        'en_progreso', 'entregado', 'terminado', 'confirmado', 'disputado', 'reembolsado',
    ];

    protected $fillable = [
        'client_user_id',
        'provider_service_id',
        'message',
        'contact_channel',
        'status',
        'delivered_at',
        'client_confirmed_at',
        'auto_release_at',
        'disputed_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'client_confirmed_at' => 'datetime',
            'auto_release_at' => 'datetime',
            'disputed_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function providerService(): BelongsTo
    {
        return $this->belongsTo(ProviderService::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(ServiceQuote::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(ServicePayment::class)
            ->whereIn('status', ['pendiente_revision', 'en_custodia', 'liberado']);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ServicePayment::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(ServiceRequestEvent::class)->orderBy('created_at');
    }

    public function evidence(): HasMany
    {
        return $this->hasMany(ServiceRequestEvidence::class)->orderBy('sort_order');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ServiceRequestMessage::class)->orderBy('created_at');
    }
}
