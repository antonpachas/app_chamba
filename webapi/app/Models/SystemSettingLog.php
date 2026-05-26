<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSettingLog extends Model
{
    public $timestamps = false;

    protected $table = 'system_settings_log';

    protected $fillable = [
        'setting_key',
        'old_value',
        'new_value',
        'changed_by',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
