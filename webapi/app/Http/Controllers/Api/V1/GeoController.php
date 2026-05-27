<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class GeoController extends Controller
{
    public function departments(): JsonResponse
    {
        $rows = Department::query()->orderBy('name')->get(['id', 'name', 'latitude', 'longitude']);

        return response()->json(['data' => $rows]);
    }

    public function provinces(Request $request): JsonResponse
    {
        $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
        ]);

        $rows = Province::query()
            ->where('department_id', $request->integer('department_id'))
            ->orderBy('name')
            ->get(['id', 'department_id', 'name', 'latitude', 'longitude']);

        return response()->json(['data' => $rows]);
    }

    public function districts(Request $request): JsonResponse
    {
        $request->validate([
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
        ]);

        $columns = ['id', 'province_id', 'name', 'latitude', 'longitude'];
        if (Schema::hasColumn('districts', 'ubigeo')) {
            $columns[] = 'ubigeo';
        }
        $rows = District::query()
            ->where('province_id', $request->integer('province_id'))
            ->orderBy('name')
            ->get($columns);

        return response()->json(['data' => $rows]);
    }

    public function resolveUbigeo(Request $request): JsonResponse
    {
        $request->validate([
            'ubigeo' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $code = $request->string('ubigeo')->toString();
        $district = District::query()->where('ubigeo', $code)->first();

        if (! $district) {
            return response()->json([
                'message' => 'Ubigeo no encontrado en el catálogo.',
            ], 404);
        }

        $district->load(['province.department']);

        return response()->json([
            'data' => [
                'ubigeo' => $code,
                'district_id' => $district->id,
                'district_name' => $district->name,
                'province_id' => $district->province_id,
                'province_name' => $district->province?->name,
                'department_id' => $district->province?->department_id,
                'department_name' => $district->province?->department?->name,
                'latitude' => $district->latitude,
                'longitude' => $district->longitude,
            ],
        ]);
    }
}
