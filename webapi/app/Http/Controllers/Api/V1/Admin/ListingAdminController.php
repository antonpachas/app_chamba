<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Services\ListingLifecycleService;
use App\Services\ListingModerationService;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ListingAdminController extends Controller
{
    public function __construct(
        private readonly ListingModerationService $moderation,
        private readonly ListingLifecycleService $lifecycle,
        private readonly MediaStorageService $media,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        $filter = (string) $request->query('filter', 'all');

        $query = ProviderService::query()
            ->with([
                'category:id,name',
                'providerProfile:id,business_name,user_id',
                'providerProfile.user:id,full_name,email,status',
                'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
            ])
            ->orderByDesc('id');

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhereHas('providerProfile', fn ($p) => $p->where('business_name', 'like', "%{$q}%"))
                    ->orWhereHas('providerProfile.user', fn ($u) => $u->where('email', 'like', "%{$q}%"));
            });
        }

        match ($filter) {
            'hidden' => $query->where('admin_hidden', true),
            'visible' => $query->where('admin_hidden', false)->where('is_active', true)
                ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now())),
            'paused' => $query->where('admin_hidden', false)->where('is_active', false),
            'expired' => $query->where('admin_hidden', false)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now()),
            default => null,
        };

        $paginator = $query->paginate(min(50, max(10, (int) $request->query('per_page', 20))));

        $data = collect($paginator->items())->map(fn (ProviderService $s) => $this->toAdminRow($s));

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function hide(Request $request, int $listing): JsonResponse
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $service = ProviderService::query()->findOrFail($listing);
        $updated = $this->moderation->hide($service, $request->user(), $data['reason'] ?? null);

        return response()->json([
            'message' => 'Anuncio ocultado de la plataforma.',
            'data' => $this->toAdminRow($updated->load([
                'category:id,name',
                'providerProfile.user:id,full_name,email,status',
                'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
            ])),
        ]);
    }

    public function restore(int $listing): JsonResponse
    {
        $service = ProviderService::query()->findOrFail($listing);
        $updated = $this->moderation->restore($service);

        return response()->json([
            'message' => 'Anuncio visible de nuevo (si cumple vigencia y está activo).',
            'data' => $this->toAdminRow($updated->load([
                'category:id,name',
                'providerProfile.user:id,full_name,email,status',
                'images' => fn ($q) => $q->orderBy('sort_order')->limit(1),
            ])),
        ]);
    }

    private function toAdminRow(ProviderService $s): array
    {
        $cover = $s->images->first();

        return [
            'id' => $s->id,
            'title' => $s->title,
            'description' => $s->description,
            'base_price' => $s->base_price,
            'price_type' => $s->price_type,
            'is_active' => (bool) $s->is_active,
            'admin_hidden' => (bool) $s->admin_hidden,
            'admin_hidden_at' => $s->admin_hidden_at,
            'admin_hidden_reason' => $s->admin_hidden_reason,
            'is_visible' => $this->lifecycle->isVisible($s),
            'expires_at' => $s->expires_at,
            'published_at' => $s->published_at,
            'created_at' => $s->created_at,
            'cover_image_url' => $cover ? $this->media->publicUrl($cover->path) : null,
            'category' => $s->category ? ['id' => $s->category->id, 'name' => $s->category->name] : null,
            'provider' => [
                'profile_id' => $s->providerProfile?->id,
                'business_name' => $s->providerProfile?->business_name,
                'user_id' => $s->providerProfile?->user_id,
                'user_name' => $s->providerProfile?->user?->full_name,
                'user_email' => $s->providerProfile?->user?->email,
                'user_status' => $s->providerProfile?->user?->status,
            ],
        ];
    }
}
