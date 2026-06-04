<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\Admin\Concerns\PaginatesAdminResources;
use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

final class GeoAdminController extends Controller
{
    use PaginatesAdminResources;
    public function departments(Request $request): JsonResponse
    {
        $q = Department::query()->orderBy('name');
        if ($search = trim((string) $request->query('q', ''))) {
            $q->where('name', 'like', '%'.$search.'%');
        }
        if ($request->query('is_active') !== null && $request->query('is_active') !== '') {
            $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $q->paginate($this->adminPerPage($request));

        return $this->adminPaginatedResponse($paginator, fn (Department $d) => $this->departmentRow($d));
    }

    public function storeDepartment(Request $request): JsonResponse
    {
        $data = $this->validateDepartment($request);
        $row = Department::query()->create($data);

        return response()->json(['message' => 'Departamento creado.', 'data' => $this->departmentRow($row)], 201);
    }

    public function updateDepartment(Request $request, int $department): JsonResponse
    {
        $row = Department::query()->findOrFail($department);
        $row->fill($this->validateDepartment($request, $row));
        $row->save();

        return response()->json(['message' => 'Departamento actualizado.', 'data' => $this->departmentRow($row)]);
    }

    public function provinces(Request $request): JsonResponse
    {
        $q = Province::query()->with('department:id,name')->orderBy('name');
        if ($request->filled('department_id')) {
            $q->where('department_id', (int) $request->query('department_id'));
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $q->where('name', 'like', '%'.$search.'%');
        }
        if ($request->query('is_active') !== null && $request->query('is_active') !== '') {
            $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $q->paginate($this->adminPerPage($request));

        return $this->adminPaginatedResponse($paginator, fn (Province $p) => $this->provinceRow($p));
    }

    public function storeProvince(Request $request): JsonResponse
    {
        $data = $this->validateProvince($request);
        $row = Province::query()->create($data);

        return response()->json(['message' => 'Provincia creada.', 'data' => $this->provinceRow($row->load('department'))], 201);
    }

    public function updateProvince(Request $request, int $province): JsonResponse
    {
        $row = Province::query()->findOrFail($province);
        $row->fill($this->validateProvince($request, $row));
        $row->save();

        return response()->json(['message' => 'Provincia actualizada.', 'data' => $this->provinceRow($row->load('department'))]);
    }

    public function districts(Request $request): JsonResponse
    {
        $q = District::query()->with('province.department')->orderBy('name');
        if ($request->filled('province_id')) {
            $q->where('province_id', (int) $request->query('province_id'));
        }
        if ($request->filled('department_id')) {
            $q->whereHas('province', fn ($p) => $p->where('department_id', (int) $request->query('department_id')));
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $q->where(function ($w) use ($search): void {
                $w->where('name', 'like', '%'.$search.'%');
                if (Schema::hasColumn('districts', 'ubigeo')) {
                    $w->orWhere('ubigeo', 'like', '%'.$search.'%');
                }
            });
        }
        if ($request->query('is_active') !== null && $request->query('is_active') !== '') {
            $q->where('is_active', filter_var($request->query('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $paginator = $q->paginate($this->adminPerPage($request));

        return $this->adminPaginatedResponse($paginator, fn (District $d) => $this->districtRow($d));
    }

    public function storeDistrict(Request $request): JsonResponse
    {
        $data = $this->validateDistrict($request);
        $row = District::query()->create($data);

        return response()->json(['message' => 'Distrito creado.', 'data' => $this->districtRow($row->load('province.department'))], 201);
    }

    public function updateDistrict(Request $request, int $district): JsonResponse
    {
        $row = District::query()->findOrFail($district);
        $row->fill($this->validateDistrict($request, $row));
        $row->save();

        return response()->json(['message' => 'Distrito actualizado.', 'data' => $this->districtRow($row->load('province.department'))]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDepartment(Request $request, ?Department $existing = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
            'ubigeo_code' => ['nullable', 'string', 'size:2'],
        ]);

        $payload = [
            'name' => trim($data['name']),
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : ($existing?->is_active ?? true),
        ];
        if (Schema::hasColumn('departments', 'ubigeo_code') && array_key_exists('ubigeo_code', $data)) {
            $payload['ubigeo_code'] = $data['ubigeo_code'] ? str_pad($data['ubigeo_code'], 2, '0', STR_PAD_LEFT) : null;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProvince(Request $request, ?Province $existing = null): array
    {
        $data = $request->validate([
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'name' => ['required', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
            'ubigeo_code' => ['nullable', 'string', 'max:4'],
        ]);

        $payload = [
            'department_id' => (int) $data['department_id'],
            'name' => trim($data['name']),
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : ($existing?->is_active ?? true),
        ];
        if (Schema::hasColumn('provinces', 'ubigeo_code') && array_key_exists('ubigeo_code', $data)) {
            $payload['ubigeo_code'] = $data['ubigeo_code'] ? str_pad($data['ubigeo_code'], 4, '0', STR_PAD_LEFT) : null;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function validateDistrict(Request $request, ?District $existing = null): array
    {
        $rules = [
            'province_id' => ['required', 'integer', 'exists:provinces,id'],
            'name' => ['required', 'string', 'max:120'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
        ];
        if (Schema::hasColumn('districts', 'ubigeo')) {
            $rules['ubigeo'] = ['nullable', 'string', 'regex:/^\d{6}$/'];
        }
        $data = $request->validate($rules);

        $payload = [
            'province_id' => (int) $data['province_id'],
            'name' => trim($data['name']),
            'latitude' => (float) $data['latitude'],
            'longitude' => (float) $data['longitude'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : ($existing?->is_active ?? true),
        ];
        if (Schema::hasColumn('districts', 'ubigeo') && array_key_exists('ubigeo', $data)) {
            $payload['ubigeo'] = $data['ubigeo'] ?? null;
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function departmentRow(Department $d): array
    {
        return [
            'id' => $d->id,
            'name' => $d->name,
            'ubigeo_code' => $d->ubigeo_code ?? null,
            'latitude' => $d->latitude,
            'longitude' => $d->longitude,
            'is_active' => (bool) ($d->is_active ?? true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function provinceRow(Province $p): array
    {
        return [
            'id' => $p->id,
            'department_id' => $p->department_id,
            'department_name' => $p->department?->name,
            'name' => $p->name,
            'ubigeo_code' => $p->ubigeo_code ?? null,
            'latitude' => $p->latitude,
            'longitude' => $p->longitude,
            'is_active' => (bool) ($p->is_active ?? true),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function districtRow(District $d): array
    {
        return [
            'id' => $d->id,
            'province_id' => $d->province_id,
            'province_name' => $d->province?->name,
            'department_id' => $d->province?->department_id,
            'department_name' => $d->province?->department?->name,
            'name' => $d->name,
            'ubigeo' => $d->ubigeo ?? null,
            'latitude' => $d->latitude,
            'longitude' => $d->longitude,
            'is_active' => (bool) ($d->is_active ?? true),
        ];
    }

}
