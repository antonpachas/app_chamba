<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'is_editable',
    ];

    protected $casts = [
        'is_editable' => 'boolean',
    ];

    public function castedValue(): mixed
    {
        return self::castValue($this->value, $this->type);
    }

    public static function castValue(mixed $raw, string $type): mixed
    {
        if ($raw === null) return null;
        return match ($type) {
            'integer' => (int) $raw,
            'decimal' => (float) $raw,
            'boolean' => in_array(strtolower((string) $raw), ['1', 'true', 'on', 'yes'], true),
            'json' => json_decode((string) $raw, true),
            default => (string) $raw,
        };
    }

    public function logs()
    {
        return $this->hasMany(SystemSettingLog::class, 'setting_key', 'key');
    }
}
