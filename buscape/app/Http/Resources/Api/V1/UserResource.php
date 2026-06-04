<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $sub = $this->activeSubscription();
        $media = app(\App\Services\MediaStorageService::class);

        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'role' => $this->role,
            'status' => $this->status,
            'avatar_path' => $this->avatar_path,
            'avatar_url' => $media->publicUrl($this->avatar_path),
            'provider_profile' => $this->when(
                $this->relationLoaded('providerProfile'),
                fn () => $this->providerProfile !== null
                    ? ProviderProfileResource::make($this->providerProfile)
                    : null
            ),
            'subscription' => $sub ? [
                'plan_code' => $sub->plan?->code,
                'plan_name' => $sub->plan?->name,
                'tier' => $sub->plan?->tier,
                'status' => $sub->status,
                'is_pro' => $sub->isPro(),
                'in_trial' => $sub->inTrial(),
                'trial_ends_at' => $sub->trial_ends_at?->toIso8601String(),
                'current_period_end' => $sub->current_period_end?->toIso8601String(),
                'features' => $sub->plan?->features ?? [],
            ] : null,
        ];
    }
}
