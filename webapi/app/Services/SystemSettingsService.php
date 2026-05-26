<?php

namespace App\Services;

use App\Models\SystemSetting;
use App\Models\SystemSettingLog;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class SystemSettingsService
{
    private const CACHE_KEY = 'chamba.system_settings.v1';
    private const CACHE_TTL = 3600;

    /**
     * Devuelve el valor castedo. Si no existe, regresa el default.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        if (! array_key_exists($key, $all)) {
            return $default;
        }
        return $all[$key];
    }

    /**
     * @return array<string, mixed> tabla key => valor castedo
     */
    public function all(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return SystemSetting::all()
                ->mapWithKeys(fn (SystemSetting $s) => [$s->key => $s->castedValue()])
                ->all();
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, SystemSetting>
     */
    public function listForAdmin(): \Illuminate\Support\Collection
    {
        return SystemSetting::query()->orderBy('group')->orderBy('key')->get();
    }

    public function update(string $key, mixed $newValue, ?User $admin = null): SystemSetting
    {
        $setting = SystemSetting::query()->where('key', $key)->firstOrFail();

        if (! $setting->is_editable) {
            throw new \RuntimeException("La configuración '{$key}' no es editable.");
        }

        $oldRaw = $setting->value;
        $newRaw = self::serialize($newValue, $setting->type);

        if ($oldRaw === $newRaw) {
            return $setting;
        }

        $setting->value = $newRaw;
        $setting->save();

        SystemSettingLog::create([
            'setting_key' => $key,
            'old_value' => $oldRaw,
            'new_value' => $newRaw,
            'changed_by' => $admin?->id,
            'created_at' => now(),
        ]);

        $this->flush();
        return $setting;
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function serialize(mixed $value, string $type): ?string
    {
        if ($value === null) return null;
        return match ($type) {
            'integer' => (string) (int) $value,
            'decimal' => number_format((float) $value, 2, '.', ''),
            'boolean' => ((bool) $value) ? '1' : '0',
            'json' => json_encode($value, JSON_UNESCAPED_UNICODE),
            default => (string) $value,
        };
    }
}
