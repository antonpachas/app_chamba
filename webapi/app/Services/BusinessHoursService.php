<?php

namespace App\Services;

use Carbon\Carbon;

final class BusinessHoursService
{
    private const DAY_KEYS = ['sun', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat'];

    private const DAY_LABELS = [
        'sun' => 'Domingo',
        'mon' => 'Lunes',
        'tue' => 'Martes',
        'wed' => 'Miércoles',
        'thu' => 'Jueves',
        'fri' => 'Viernes',
        'sat' => 'Sábado',
    ];

    /**
     * @param  array<string, mixed>|null  $hours
     * @return array{is_open_now: bool|null, hours_summary: string|null, schedule: array<string, mixed>|null}
     */
    public function summarize(?array $hours): array
    {
        if ($hours === null || $hours === []) {
            return [
                'is_open_now' => null,
                'hours_summary' => null,
                'schedule' => null,
            ];
        }

        $now = Carbon::now('America/Lima');
        $key = self::DAY_KEYS[(int) $now->dayOfWeek];
        $today = $this->dayBlock($hours, $key);
        $summary = $this->buildWeeklySummary($hours);

        if ($today === null) {
            return [
                'is_open_now' => null,
                'hours_summary' => $summary,
                'schedule' => $hours,
            ];
        }

        if (! empty($today['closed'])) {
            return [
                'is_open_now' => false,
                'hours_summary' => $summary,
                'schedule' => $hours,
            ];
        }

        $open = $this->parseTime((string) ($today['open'] ?? ''), $now);
        $close = $this->parseTime((string) ($today['close'] ?? ''), $now);
        if ($open === null || $close === null) {
            return [
                'is_open_now' => null,
                'hours_summary' => $summary,
                'schedule' => $hours,
            ];
        }

        $isOpen = $close->lessThanOrEqualTo($open)
            ? ($now->gte($open) || $now->lt($close))
            : ($now->gte($open) && $now->lt($close));

        return [
            'is_open_now' => $isOpen,
            'hours_summary' => $summary,
            'schedule' => $hours,
        ];
    }

    /**
     * @param  array<string, mixed>  $hours
     */
    private function dayBlock(array $hours, string $key): ?array
    {
        $block = $hours[$key] ?? $hours[strtoupper($key)] ?? null;
        if (! is_array($block)) {
            return null;
        }

        return $block;
    }

    /**
     * @param  array<string, mixed>  $hours
     */
    private function buildWeeklySummary(array $hours): string
    {
        $parts = [];
        foreach (self::DAY_KEYS as $key) {
            if ($key === 'sun') {
                continue;
            }
            $block = $this->dayBlock($hours, $key);
            if ($block === null) {
                continue;
            }
            $label = self::DAY_LABELS[$key];
            if (! empty($block['closed'])) {
                $parts[] = "{$label}: cerrado";

                continue;
            }
            $open = (string) ($block['open'] ?? '');
            $close = (string) ($block['close'] ?? '');
            if ($open !== '' && $close !== '') {
                $parts[] = "{$label}: {$open}–{$close}";
            }
        }

        return $parts !== [] ? implode(' · ', array_slice($parts, 0, 3)).(count($parts) > 3 ? '…' : '') : '';
    }

    private function parseTime(string $value, Carbon $base): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (! preg_match('/^(\d{1,2}):(\d{2})$/', $value, $m)) {
            return null;
        }

        return $base->copy()->setTime((int) $m[1], (int) $m[2], 0);
    }

    /**
     * Horario vacío listo para el formulario del proveedor.
     *
     * @return array<string, array{closed: bool, open: string, close: string}>
     */
    public function defaultSchedule(): array
    {
        $day = ['closed' => false, 'open' => '09:00', 'close' => '18:00'];

        return [
            'mon' => $day,
            'tue' => $day,
            'wed' => $day,
            'thu' => $day,
            'fri' => $day,
            'sat' => ['closed' => false, 'open' => '09:00', 'close' => '14:00'],
            'sun' => ['closed' => true, 'open' => '', 'close' => ''],
        ];
    }

    /**
     * @param  mixed  $input
     * @return array<string, array{closed: bool, open: string|null, close: string|null}>|null
     */
    /**
     * Horario de sede si existe; si no, el del perfil del negocio.
     *
     * @param  array<string, mixed>|null  $profileHours
     * @param  array<string, mixed>|null  $locationHours
     * @return array<string, mixed>|null
     */
    public function resolveForListing(?array $profileHours, ?array $locationHours): ?array
    {
        if (is_array($locationHours) && $locationHours !== []) {
            return $locationHours;
        }

        return is_array($profileHours) && $profileHours !== [] ? $profileHours : null;
    }

    public function normalizeInput(mixed $input): ?array
    {
        if ($input === null || $input === '') {
            return null;
        }
        if (! is_array($input)) {
            return null;
        }

        $out = [];
        foreach (self::DAY_KEYS as $key) {
            $block = $input[$key] ?? null;
            if (! is_array($block)) {
                continue;
            }
            $closed = ! empty($block['closed']);
            $open = isset($block['open']) ? trim((string) $block['open']) : '';
            $close = isset($block['close']) ? trim((string) $block['close']) : '';
            $out[$key] = [
                'closed' => $closed,
                'open' => $closed ? null : ($open !== '' ? $open : null),
                'close' => $closed ? null : ($close !== '' ? $close : null),
            ];
        }

        return $out === [] ? null : $out;
    }
}
