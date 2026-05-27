<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderService;
use App\Models\ServiceImage;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

final class ServiceImageController extends Controller
{
    public function __construct(private readonly MediaStorageService $media) {}

    public function store(Request $request, ProviderService $service): JsonResponse
    {
        $this->authorize($request, $service);

        $request->validate([
            'image' => 'required|file|max:5120',
        ]);

        try {
            $path = $this->media->storeImage(
                $request->file('image'),
                MediaStorageService::FOLDER_SERVICE,
                ['max_w' => 1600, 'max_h' => 1600]
            );

            $sortOrder = ((int) ServiceImage::where('provider_service_id', $service->id)->max('sort_order')) + 1;

            $image = ServiceImage::create([
                'provider_service_id' => $service->id,
                'path' => $path,
                'sort_order' => $sortOrder,
            ]);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'data' => [
                'id' => $image->id,
                'path' => $image->path,
                'url' => $this->media->publicUrl($image->path),
                'sort_order' => $image->sort_order,
            ],
        ], 201);
    }

    public function destroy(Request $request, ProviderService $service, ServiceImage $image): JsonResponse
    {
        $this->authorize($request, $service);

        if ($image->provider_service_id !== $service->id) {
            abort(404);
        }

        $this->media->delete($image->path);
        $image->delete();

        return response()->json(['data' => ['deleted' => true]]);
    }

    private function authorize(Request $request, ProviderService $service): void
    {
        // Comparar siempre con cast a int. En producción con MariaDB las foreign keys
        // pueden volver como string y la comparación estricta (!==) las trataría como
        // distintas, causando 403 al dueño legítimo.
        $userId = (int) $request->user()->id;
        $ownerId = (int) ($service->providerProfile?->user_id ?? 0);
        if ($ownerId !== $userId && $request->user()->role !== 'admin') {
            abort(403);
        }
    }
}
