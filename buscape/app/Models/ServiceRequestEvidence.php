<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceRequestEvidence extends Model
{
    public $timestamps = false;

    protected $table = 'service_request_evidence';

    protected $fillable = [
        'service_request_id',
        'path',
        'caption',
        'sort_order',
        'uploaded_by_user_id',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
