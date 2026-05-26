<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Models\ServiceQuote;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class QuoteController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'service_request_id' => 'required|integer|exists:service_requests,id',
            'amount' => 'required|numeric|min:1|max:99999999.99',
            'estimated_days' => 'nullable|integer|min:0|max:365',
            'notes' => 'nullable|string|max:1000',
        ]);

        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json(['message' => 'Sin perfil de proveedor.'], 422);
        }

        $sr = ServiceRequest::query()
            ->whereHas('providerService', fn ($q) => $q->where('provider_profile_id', $profile->id))
            ->findOrFail((int) $data['service_request_id']);

        if (! in_array($sr->status, ['nuevo', 'contactado', 'cotizado'], true)) {
            return response()->json(['message' => 'No se puede cotizar en el estado actual.'], 422);
        }

        $quote = ServiceQuote::query()->create([
            'service_request_id' => $sr->id,
            'amount' => $data['amount'],
            'currency' => 'PEN',
            'estimated_days' => $data['estimated_days'] ?? null,
            'notes' => $data['notes'] ?? null,
            'status' => 'pendiente',
        ]);

        $sr->update(['status' => 'cotizado']);

        return response()->json(['data' => $quote], 201);
    }
}
