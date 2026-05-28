<?php

namespace App\Services;

use App\Models\ProviderService;
use App\Models\User;

final class ListingModerationService
{
    public function hide(ProviderService $listing, User $admin, ?string $reason = null): ProviderService
    {
        $listing->admin_hidden = true;
        $listing->admin_hidden_at = now();
        $listing->admin_hidden_reason = $reason ? trim($reason) : null;
        $listing->admin_hidden_by = (int) $admin->id;
        $listing->is_active = false;
        $listing->deactivated_at = $listing->deactivated_at ?? now();
        $listing->save();

        return $listing->refresh();
    }

    public function restore(ProviderService $listing): ProviderService
    {
        $listing->admin_hidden = false;
        $listing->admin_hidden_at = null;
        $listing->admin_hidden_reason = null;
        $listing->admin_hidden_by = null;
        $listing->save();

        return $listing->refresh();
    }
}
