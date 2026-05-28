<?php

namespace App\Services;

final class ListingGuestPreviewService
{
    public function maxSearchResults(): int
    {
        $max = (int) chamba_setting('listings.guest_search_max', 24);

        return max(6, min(100, $max));
    }

    public function maxDescriptionChars(): int
    {
        $n = (int) chamba_setting('listings.guest_description_max', 280);

        return max(80, min(2000, $n));
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function scrubRow(array $row): array
    {
        $row['guest_preview'] = true;

        foreach (['description', 'service_description'] as $key) {
            if (! array_key_exists($key, $row) || $row[$key] === null) {
                continue;
            }
            $full = (string) $row[$key];
            $row[$key] = $this->truncate($full);
            if ($full !== '' && mb_strlen($full) > $this->maxDescriptionChars()) {
                $row['description_truncated'] = true;
            }
        }

        return $row;
    }

    public function truncate(?string $text): ?string
    {
        if ($text === null || $text === '') {
            return $text;
        }
        $max = $this->maxDescriptionChars();
        if (mb_strlen($text) <= $max) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $max - 1)).'…';
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @return array{rows: array<int, array<string, mixed>>, total: int, limited: bool}
     */
    public function limitSearchResults(array $rows): array
    {
        $total = count($rows);
        $max = $this->maxSearchResults();
        if ($total <= $max) {
            return ['rows' => $rows, 'total' => $total, 'limited' => false];
        }

        return [
            'rows' => array_slice($rows, 0, $max),
            'total' => $total,
            'limited' => true,
        ];
    }
}
