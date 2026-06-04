<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\SystemSettingLog;
use App\Services\SystemSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class SystemSettingsAdminController extends Controller
{
    public function __construct(private readonly SystemSettingsService $svc) {}

    public function index(): JsonResponse
    {
        $rows = $this->svc->listForAdmin()->map(fn (SystemSetting $s) => [
            'id' => $s->id,
            'key' => $s->key,
            'value' => $s->value,
            'casted_value' => $s->castedValue(),
            'type' => $s->type,
            'group' => $s->group,
            'label' => $s->label,
            'description' => $s->description,
            'is_editable' => $s->is_editable,
            'updated_at' => $s->updated_at,
        ]);

        return response()->json(['data' => $rows]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $data = $request->validate([
            'value' => 'present',
        ]);

        try {
            $setting = $this->svc->update($key, $data['value'], $request->user());
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'key' => $setting->key,
                'value' => $setting->value,
                'casted_value' => $setting->castedValue(),
                'type' => $setting->type,
                'updated_at' => $setting->updated_at,
            ],
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $data = $request->validate([
            'settings' => 'required|array|min:1',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'present',
        ]);

        $updated = [];
        foreach ($data['settings'] as $item) {
            try {
                $s = $this->svc->update($item['key'], $item['value'], $request->user());
                $updated[] = [
                    'key' => $s->key,
                    'value' => $s->value,
                    'casted_value' => $s->castedValue(),
                ];
            } catch (Throwable $e) {
                return response()->json([
                    'message' => "Error en '{$item['key']}': ".$e->getMessage(),
                ], 422);
            }
        }

        return response()->json(['data' => $updated]);
    }

    public function logs(Request $request): JsonResponse
    {
        $key = $request->query('key');
        $rows = SystemSettingLog::query()
            ->with('changedBy:id,full_name,email')
            ->when($key, fn ($q) => $q->where('setting_key', $key))
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (SystemSettingLog $l) => [
                'id' => $l->id,
                'setting_key' => $l->setting_key,
                'old_value' => $l->old_value,
                'new_value' => $l->new_value,
                'changed_by' => $l->changedBy?->full_name,
                'changed_by_email' => $l->changedBy?->email,
                'created_at' => $l->created_at,
            ]);

        return response()->json(['data' => $rows]);
    }
}
