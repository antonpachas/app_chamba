<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class SubscriptionPlanAdminController extends Controller
{
    public function index(): JsonResponse
    {
        $rows = SubscriptionPlan::query()
            ->orderBy('audience')
            ->orderByRaw("FIELD(tier,'free','pro','premium')")
            ->get();

        return response()->json(['data' => $rows]);
    }

    public function update(Request $request, SubscriptionPlan $plan): JsonResponse
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:120',
            'price' => 'sometimes|numeric|min:0',
            'features' => 'sometimes|array',
            'features.contacts_per_month' => 'sometimes|nullable|integer|min:0',
            'features.max_services' => 'sometimes|nullable|integer|min:0',
            'features.support' => 'sometimes|nullable|string|max:80',
            'features.priority_listing' => 'sometimes|nullable|boolean',
            'features.badge' => 'sometimes|nullable|boolean',
            'features.verified_badge' => 'sometimes|nullable|boolean',
            'is_active' => 'sometimes|boolean',
        ]);

        $admin = $request->user();
        $changes = [];

        DB::transaction(function () use ($plan, $data, $admin, &$changes) {
            foreach (['name', 'price', 'is_active'] as $field) {
                if (array_key_exists($field, $data) && (string) $plan->$field !== (string) $data[$field]) {
                    $changes[] = [
                        'field' => $field,
                        'old' => (string) $plan->$field,
                        'new' => (string) $data[$field],
                    ];
                    $plan->$field = $data[$field];
                }
            }

            if (array_key_exists('features', $data)) {
                $oldFeatures = $plan->features ?? [];
                $newFeatures = array_merge($oldFeatures, $data['features']);
                if (json_encode($oldFeatures) !== json_encode($newFeatures)) {
                    $changes[] = [
                        'field' => 'features',
                        'old' => json_encode($oldFeatures, JSON_UNESCAPED_UNICODE),
                        'new' => json_encode($newFeatures, JSON_UNESCAPED_UNICODE),
                    ];
                    $plan->features = $newFeatures;
                }
            }

            $plan->save();

            foreach ($changes as $c) {
                SubscriptionPlanLog::create([
                    'plan_id' => $plan->id,
                    'field' => $c['field'],
                    'old_value' => $c['old'],
                    'new_value' => $c['new'],
                    'changed_by' => $admin?->id,
                    'created_at' => now(),
                ]);
            }
        });

        Cache::forget('chamba.pricing.public.v1');

        return response()->json(['data' => $plan->fresh()]);
    }

    public function logs(SubscriptionPlan $plan): JsonResponse
    {
        $rows = SubscriptionPlanLog::query()
            ->where('plan_id', $plan->id)
            ->with('changedBy:id,full_name,email')
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->map(fn (SubscriptionPlanLog $l) => [
                'id' => $l->id,
                'field' => $l->field,
                'old_value' => $l->old_value,
                'new_value' => $l->new_value,
                'changed_by' => $l->changedBy?->full_name,
                'changed_by_email' => $l->changedBy?->email,
                'created_at' => $l->created_at,
            ]);

        return response()->json([
            'plan' => $plan,
            'data' => $rows,
        ]);
    }
}
