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
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, array<string, mixed>|null>  $hoursByProfileId
     * @return array<int, array<string, mixed>>
     */
    public function mapList(array $rows, array $hoursByProfileId = []): array
    {
        return array_map(function (array $row) use ($hoursByProfileId): array {
            $pid = (int) ($row['provider_profile_id'] ?? 0);
            $hours = $hoursByProfileId[$pid] ?? null;

            return $this->forList($row, is_array($hours) ? $hours : null);
        }, $rows);
    }
}
