<?php

namespace App\Services;

/**
 * Formato de anuncios en listados (búsqueda, perfil público): sin datos de contacto.
 */
final class ListingListFormatter
{
    public function __construct(
        private readonly BusinessHoursService $businessHours,
    ) {}

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function forList(array $row, ?array $businessHours = null): array
    {
        $hadContact = ! empty($row['whatsapp']) || ! empty($row['contact_phone']);
        unset($row['whatsapp'], $row['contact_phone']);
        if ($hadContact) {
            $row['contact_on_detail_only'] = true;
        }

        if ($businessHours !== null) {
            $summary = $this->businessHours->summarize($businessHours);
            $row['is_open_now'] = $summary['is_open_now'];
            $row['hours_summary'] = $summary['hours_summary'];
        }

        if (isset($row['distance_km']) && $row['distance_km'] !== null && $row['distance_km'] !== '') {
            $row['distance_km'] = round((float) $row['distance_km'], 2);
        }

        return $row;
    }

    /**
     * @param  array<int, array<string, mixed>|null>  $hoursByProfileId
     * @param  array<int, array<string, mixed>|null>  $hoursByLocationId
     * @param  array<int, int>  $primaryLocationIdByProfile
     * @param  array<int, array|null>  $hoursByServiceId
     */
    public function mapList(
        array $rows,
        array $hoursByProfileId = [],
        array $hoursByLocationId = [],
        array $primaryLocationIdByProfile = [],
        array $hoursByServiceId = [],
    ): array {
        return array_map(function (array $row) use ($hoursByProfileId, $hoursByLocationId, $primaryLocationIdByProfile, $hoursByServiceId): array {
            $pid = (int) ($row['provider_profile_id'] ?? 0);
            $sid = (int) ($row['service_id'] ?? 0);
            $profHours = $hoursByProfileId[$pid] ?? null;
            $locId = $primaryLocationIdByProfile[$pid] ?? 0;
            $locHours = $locId > 0 ? ($hoursByLocationId[$locId] ?? null) : null;
            $listingHours = $sid > 0 ? ($hoursByServiceId[$sid] ?? null) : null;
            $resolved = $this->businessHours->resolveForListing(
                is_array($profHours) ? $profHours : null,
                is_array($locHours) ? $locHours : null,
                is_array($listingHours) ? $listingHours : null,
            );

            return $this->forList($row, $resolved);
        }, $rows);
    }
}
