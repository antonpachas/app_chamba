<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\ServiceRequestEvidence;
use App\Services\MediaStorageService;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

/**
 * Endpoints relacionados con la entrega del trabajo:
 *   - Subir/eliminar evidencias
 *   - Marcar trabajo como entregado (inicia ventana de auto-liberación)
 */
final class ServiceRequestDeliveryController extends Controller
{
    public function __construct(
        private readonly MediaStorageService $media,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Sube hasta N fotos a la vez como evidencias del trabajo entregado.
     */
    public function uploadEvidence(Request $request, int $serviceRequest): JsonResponse
    {
        $request->validate([
            'photos' => 'required|array|min:1|max:10',
            'photos.*' => 'required|file|max:5120',
            'caption' => 'nullable|string|max:255',
        ]);

        $sr = $this->ownedRequest($request, $serviceRequest);
        if (! $sr) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }
        if (! in_array($sr->status, ['en_custodia', 'en_progreso', 'entregado'], true)) {
            return response()->json([
                'message' => 'Solo se pueden subir evidencias después de aceptar el trabajo.',
            ], 422);
        }

        $uploaded = [];
        $existingCount = ServiceRequestEvidence::query()
            ->where('service_request_id', $sr->id)
            ->count();

        try {
            foreach ($request->file('photos') as $idx => $file) {
                $path = $this->media->storeImage(
                    $file,
                    MediaStorageService::FOLDER_SERVICE,
                    ['max_w' => 1600, 'max_h' => 1600]
                );
                $ev = ServiceRequestEvidence::query()->create([
                    'service_request_id' => $sr->id,
                    'path' => $path,
                    'caption' => $request->input('caption'),
                    'sort_order' => $existingCount + $idx,
                    'uploaded_by_user_id' => $request->user()->id,
                    'created_at' => now(),
                ]);
                $uploaded[] = $this->formatEvidence($ev);
            }
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $uploaded], 201);
    }

    /**
     * Elimina una evidencia subida previamente (mientras no se haya marcado como entregado).
     */
    public function deleteEvidence(Request $request, int $serviceRequest, int $evidence): JsonResponse
    {
        $sr = $this->ownedRequest($request, $serviceRequest);
        if (! $sr) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }
        if ($sr->status === 'confirmado' || $sr->status === 'cerrado') {
            return response()->json(['message' => 'Ya no puedes modificar evidencias de un trabajo cerrado.'], 422);
        }
        $ev = ServiceRequestEvidence::query()
            ->where('service_request_id', $sr->id)
            ->findOrFail($evidence);

        $this->media->delete($ev->path);
        $ev->delete();

        return response()->json(['data' => ['id' => $evidence, 'deleted' => true]]);
    }

    /**
     * Marca el trabajo como entregado. Requiere al menos N evidencias.
     */
    public function markDelivered(Request $request, int $serviceRequest): JsonResponse
    {
        $sr = $this->ownedRequest($request, $serviceRequest);
        if (! $sr) {
            return response()->json(['message' => 'Solicitud no encontrada.'], 404);
        }

        $count = ServiceRequestEvidence::query()->where('service_request_id', $sr->id)->count();
        try {
            $this->payments->providerMarkDelivered($sr, $count);
        } catch (Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['data' => $sr->fresh()]);
    }

    private function ownedRequest(Request $request, int $serviceRequest): ?ServiceRequest
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) return null;

        return ServiceRequest::query()
            ->whereHas('providerService', fn ($q) => $q->where('provider_profile_id', $profile->id))
            ->find($serviceRequest);
    }

    private function formatEvidence(ServiceRequestEvidence $e): array
    {
        return [
            'id' => $e->id,
            'service_request_id' => $e->service_request_id,
            'path' => $e->path,
            'url' => $this->media->publicUrl($e->path),
            'caption' => $e->caption,
            'sort_order' => $e->sort_order,
            'created_at' => $e->created_at,
        ];
    }
}
