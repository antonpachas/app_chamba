<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Services\StoredProcedureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __construct(
        private readonly StoredProcedureService $storedProcedures,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->providerProfile;
        if ($profile === null) {
            return response()->json([
                'message' => 'Aún no existe un perfil de proveedor.',
            ], 404);
        }

        $rows = $this->storedProcedures->getProviderDashboard((int) $profile->id);
        $row = $rows[0] ?? null;

        if ($row === null) {
            return response()->json([
                'message' => 'No se pudo obtener el resumen.',
            ], 500);
        }

        return response()->json([
            'data' => (array) $row,
        ]);
    }
}
