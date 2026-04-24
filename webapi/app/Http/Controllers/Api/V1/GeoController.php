<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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

        $rows = District::query()
            ->where('province_id', $request->integer('province_id'))
            ->orderBy('name')
            ->get(['id', 'province_id', 'name', 'latitude', 'longitude']);

        return response()->json(['data' => $rows]);
    }
}
