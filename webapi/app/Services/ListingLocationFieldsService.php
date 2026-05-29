<?php

namespace App\Services;

use App\Models\District;
use App\Models\ProviderService;

final class ListingLocationFieldsService
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validatedLocationPayload(array $data): array
    {
        $districtId = isset($data['district_id']) ? (int) $data['district_id'] : null;
        $district = $districtId ? District::query()->with('province')->find($districtId) : null;

        $lat = array_key_exists('latitude', $data) && $data['latitude'] !== null && $data['latitude'] !== ''
            ? (float) $data['latitude']
            : null;
        $lng = array_key_exists('longitude', $data) && $data['longitude'] !== null && $data['longitude'] !== ''
            ? (float) $data['longitude']
            : null;

        if ($lat === null && $district?->latitude !== null) {
            $lat = (float) $district->latitude;
        }
        if ($lng === null && $district?->longitude !== null) {
            $lng = (float) $district->longitude;
        }

        $ubigeo = isset($data['ubigeo']) && $data['ubigeo'] !== ''
            ? preg_replace('/\D/', '', (string) $data['ubigeo'])
            : ($district?->ubigeo);

        return [
            'location_label' => isset($data['location_label']) ? trim((string) $data['location_label']) : null,
            'address_text' => isset($data['address_text']) ? trim((string) $data['address_text']) : null,
            'department_id' => isset($data['department_id']) && $data['department_id'] !== ''
                ? (int) $data['department_id']
                : ($district?->province?->department_id ? (int) $district->province->department_id : null),
            'province_id' => isset($data['province_id']) && $data['province_id'] !== ''
                ? (int) $data['province_id']
                : ($district?->province_id ? (int) $district->province_id : null),
            'district_id' => $districtId,
            'ubigeo' => $ubigeo ? substr($ubigeo, 0, 6) : null,
            'latitude' => $lat,
            'longitude' => $lng,
        ];
    }

    public function applyToListing(ProviderService $listing, array $data): ProviderService
    {
        $listing->fill($this->validatedLocationPayload($data));
        $listing->save();

        return $listing->refresh();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function applyListingGeoToSearchRow(ProviderService $service, array $row): array
    {
        $service->loadMissing([
            'district:id,name,province_id,latitude,longitude,ubigeo',
            'district.province:id,name,department_id',
            'district.province.department:id,name',
        ]);

        if ($service->district_id) {
            $row['district_id'] = $service->district_id;
            $row['district_name'] = $service->district?->name;
            $row['province_name'] = $service->district?->province?->name;
            $row['department_name'] = $service->district?->province?->department?->name;
        }

        if ($service->address_text) {
            $row['address_text'] = $service->address_text;
        }

        if ($service->location_label) {
            $row['location_label'] = $service->location_label;
        }

        $lat = $service->latitude ?? $service->district?->latitude;
        $lng = $service->longitude ?? $service->district?->longitude;
        if ($lat !== null && $lng !== null) {
            $row['provider_latitude'] = (float) $lat;
            $row['provider_longitude'] = (float) $lng;
        }

        $row['listing_type'] = $service->listing_type ?? 'presencia';

        return $row;
    }
}
