<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\PaginatesAdminResources;
use App\Http\Controllers\Controller;
use App\Models\PlatformAd;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class PlatformAdAdminController extends Controller
{
    use PaginatesAdminResources;

    public function __construct(private readonly MediaStorageService $media) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = PlatformAd::query()
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->paginate($this->adminPerPage($request));

        return $this->adminPaginatedResponse($paginator, fn (PlatformAd $a) => $this->format($a));
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);
        $path = $this->media->storeImage($request->file('image'), MediaStorageService::FOLDER_ADS);

        $ad = PlatformAd::create([
            ...$data,
            'image_path' => $path,
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['data' => $this->format($ad)], 201);
    }

    public function update(Request $request, int $ad): JsonResponse
    {
        $model = PlatformAd::query()->findOrFail($ad);
        $data = $this->validated($request, partial: true);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->media->storeImage($request->file('image'), MediaStorageService::FOLDER_ADS);
        }

        $model->fill($data)->save();

        return response()->json(['data' => $this->format($model)]);
    }

    public function destroy(int $ad): JsonResponse
    {
        PlatformAd::query()->where('id', $ad)->delete();

        return response()->json(['message' => 'Anuncio publicitario eliminado.']);
    }

    private function validated(Request $request, bool $partial = false): array
    {
        return $request->validate([
            'title' => ($partial ? 'sometimes|' : '').'required|string|max:150',
            'link_url' => 'nullable|url|max:500',
            'placement' => ($partial ? 'sometimes|' : '').'required|in:home,search,detail,all',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after_or_equal:starts_at',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|integer|min:0|max:9999',
            'image' => ($partial ? 'sometimes|' : '').'image|mimes:jpeg,png,webp|max:5120',
        ]);
    }

    private function format(PlatformAd $a): array
    {
        return [
            'id' => $a->id,
            'title' => $a->title,
            'image_url' => $this->media->publicUrl($a->image_path),
            'link_url' => $a->link_url,
            'placement' => $a->placement,
            'starts_at' => $a->starts_at,
            'ends_at' => $a->ends_at,
            'is_active' => (bool) $a->is_active,
            'sort_order' => $a->sort_order,
            'impressions' => $a->impressions,
            'clicks' => $a->clicks,
        ];
    }
}
