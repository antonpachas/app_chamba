<?php

namespace App\Http\Controllers\Api\V1\Client;

use App\Http\Controllers\Controller;
use App\Models\ServicePayment;
use App\Models\SubscriptionPayment;
use App\Services\MediaStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Historial unificado del cliente: pagos de membresía + pagos de servicios contratados.
 */
final class HistoryController extends Controller
{
    public function __construct(private readonly MediaStorageService $media) {}

    public function index(Request $request): JsonResponse
    {
        $type = (string) $request->query('type', 'all'); // all|membership|service
        $userId = (int) $request->user()->id;

        $items = collect();

        if ($type === 'all' || $type === 'membership') {
            $subs = SubscriptionPayment::query()
                ->where('user_id', $userId)
                ->with('subscription.plan:id,code,name,tier,audience')
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            foreach ($subs as $sp) {
                $items->push([
                    'kind' => 'membership',
                    'id' => 'membership-'.$sp->id,
                    'reference_id' => $sp->id,
                    'date' => $sp->created_at,
                    'amount' => (float) $sp->amount,
                    'currency' => $sp->currency,
                    'status' => $sp->status,
                    'concept' => $sp->subscription?->plan?->name ?? 'Membresía',
                    'concept_detail' => $sp->subscription?->plan?->tier,
                    'payment_method' => $sp->payment_method,
                    'payment_reference' => $sp->payment_reference,
                    'paid_at' => $sp->paid_at,
                    'confirmed_at' => $sp->confirmed_at,
                    'proof_image_url' => $this->media->publicUrl($sp->proof_image_path),
                    'notes' => $sp->notes,
                ]);
            }
        }

        if ($type === 'all' || $type === 'service') {
            $services = ServicePayment::query()
                ->where('client_user_id', $userId)
                ->with([
                    'providerProfile:id,user_id,business_name',
                    'providerProfile.user:id,full_name',
                    'serviceRequest.providerService:id,title',
                ])
                ->orderByDesc('created_at')
                ->limit(200)
                ->get();

            foreach ($services as $sp) {
                $items->push([
                    'kind' => 'service',
                    'id' => 'service-'.$sp->id,
                    'reference_id' => $sp->id,
                    'date' => $sp->created_at,
                    'amount' => (float) $sp->amount,
                    'commission_amount' => (float) $sp->commission_amount,
                    'net_amount' => (float) $sp->net_amount,
                    'currency' => $sp->currency,
                    'status' => $sp->status,
                    'concept' => $sp->serviceRequest?->providerService?->title ?? 'Servicio',
                    'concept_detail' => $sp->providerProfile?->business_name
                        ?: $sp->providerProfile?->user?->full_name,
                    'payment_method' => $sp->payment_method,
                    'payment_reference' => $sp->payment_reference,
                    'paid_at' => $sp->paid_at,
                    'confirmed_at' => $sp->confirmed_at,
                    'released_at' => $sp->released_at,
                    'proof_image_url' => $this->media->publicUrl($sp->proof_image_path),
                    'notes' => $sp->notes,
                ]);
            }
        }

        $sorted = $items->sortByDesc(fn ($i) => $i['date'])->values();

        return response()->json([
            'data' => $sorted->all(),
            'totals' => [
                'membership' => $items->where('kind', 'membership')->sum('amount'),
                'service' => $items->where('kind', 'service')->sum('amount'),
                'all' => $items->sum('amount'),
            ],
        ]);
    }
}
