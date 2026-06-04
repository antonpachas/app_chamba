<?php

namespace App\Services;

final class ListingPublicIdService
{
  /**
   * Identificador opaco para URLs (no es cifrado fuerte; evita adivinar IDs secuenciales).
   */
    public function encode(int $id): string
    {
        if ($id <= 0) {
            return '';
        }

        $key = hash('sha256', (string) config('app.key'), true);
        $payload = pack('N', $id);
        $sig = substr(hash_hmac('sha256', $payload, $key, true), 0, 4);

        return rtrim(strtr(base64_encode($payload.$sig), '+/', '-_'), '=');
    }

    public function resolve(string $param): ?int
    {
        $param = trim($param);
        if ($param === '') {
            return null;
        }

        if (ctype_digit($param)) {
            $n = (int) $param;

            return $n > 0 ? $n : null;
        }

        // Slug format: "titulo-del-anuncio-{id}" — extract trailing numeric ID.
        if (preg_match('/^[a-z0-9][a-z0-9-]*-(\d+)$/i', $param, $m)) {
            $n = (int) $m[1];
            if ($n > 0) {
                return $n;
            }
        }

        return $this->decode($param);
    }

    private function decode(string $token): ?int
    {
        $pad = strlen($token) % 4;
        if ($pad > 0) {
            $token .= str_repeat('=', 4 - $pad);
        }
        $raw = base64_decode(strtr($token, '-_', '+/'), true);
        if ($raw === false || strlen($raw) < 8) {
            return null;
        }

        $payload = substr($raw, 0, 4);
        $sig = substr($raw, 4, 4);
        $key = hash('sha256', (string) config('app.key'), true);
        $expected = substr(hash_hmac('sha256', $payload, $key, true), 0, 4);
        if (! hash_equals($expected, $sig)) {
            return null;
        }

        $unpacked = unpack('N', $payload);
        $id = (int) ($unpacked[1] ?? 0);

        return $id > 0 ? $id : null;
    }
}
