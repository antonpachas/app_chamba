<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sube imágenes al disco FTP con validación fuerte:
 *   - MIME real con finfo (no se confía en el header).
 *   - Magic bytes verificados (JPEG, PNG, WEBP).
 *   - Re-encodificación con GD (destruye cualquier payload, EXIF malicioso, etc.).
 *   - Tamaño y dimensiones acotadas; downscale automático.
 *   - Nombre aleatorio (no se conserva el del cliente).
 *
 * No es un antivirus, pero es la defensa estándar contra archivos maliciosos
 * disfrazados de imagen ("php-en-jpeg", payloads en EXIF, polyglots, etc.).
 */
class MediaStorageService
{
    public const FOLDER_AVATAR = 'avatars';
    public const FOLDER_SERVICE = 'services';
    public const FOLDER_PAYMENT = 'payments';

    private const ALLOWED_MIMES = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const MAGIC_BYTES = [
        'image/jpeg' => ["\xFF\xD8\xFF"],
        'image/png' => ["\x89PNG\r\n\x1a\n"],
        'image/webp' => ['RIFF'],
    ];

    private string $disk = 'chamba_ftp';

    public function __construct()
    {
        $this->disk = (string) config('filesystems.default_chamba_disk', 'chamba_ftp');
    }

    /**
     * Sube una imagen al FTP. Devuelve el path relativo guardado (ej: "avatars/abc.jpg").
     *
     * @param  array  $opts  ['max_kb' => int, 'max_w' => int, 'max_h' => int]
     */
    public function storeImage(UploadedFile $file, string $folder, array $opts = []): string
    {
        if (! in_array($folder, [self::FOLDER_AVATAR, self::FOLDER_SERVICE, self::FOLDER_PAYMENT], true)) {
            throw new RuntimeException("Carpeta no permitida: {$folder}");
        }

        if (! $file->isValid()) {
            throw new RuntimeException('Archivo inválido: '.$file->getErrorMessage());
        }

        $maxKb = (int) ($opts['max_kb'] ?? config('chamba.media.max_kb', env('CHAMBA_MEDIA_MAX_KB', 5120)));
        if ($file->getSize() > $maxKb * 1024) {
            throw new RuntimeException("La imagen pesa más de {$maxKb} KB.");
        }

        $tmp = $file->getRealPath();
        $mime = $this->detectMime($tmp);
        if (! isset(self::ALLOWED_MIMES[$mime])) {
            throw new RuntimeException('Solo se aceptan imágenes JPG, PNG o WEBP.');
        }

        $this->verifyMagicBytes($tmp, $mime);

        $maxW = (int) ($opts['max_w'] ?? 2000);
        $maxH = (int) ($opts['max_h'] ?? 2000);
        $clean = $this->reencodeImage($tmp, $mime, $maxW, $maxH);

        $ext = self::ALLOWED_MIMES[$mime];
        $path = $folder.'/'.date('Ymd').'_'.Str::random(24).'.'.$ext;

        $ok = Storage::disk($this->disk)->put($path, $clean);
        if (! $ok) {
            throw new RuntimeException('No se pudo subir la imagen al servidor.');
        }

        return $path;
    }

    /**
     * Elimina un archivo del FTP. Silencioso si no existe.
     */
    public function delete(?string $path): void
    {
        if (! $path) return;
        try {
            Storage::disk($this->disk)->delete($path);
        } catch (\Throwable) {
            // ignorar
        }
    }

    /**
     * Construye una URL pública o proxy para mostrar la imagen.
     * Si CHAMBA_FTP_PUBLIC_URL está configurado, usa esa base directa.
     * En caso contrario, usa el endpoint proxy /api/v1/media/{path}.
     */
    public function publicUrl(?string $path): ?string
    {
        if (! $path) return null;

        $base = (string) env('CHAMBA_FTP_PUBLIC_URL', '');
        if ($base !== '') {
            return rtrim($base, '/').'/'.ltrim($path, '/');
        }

        return url('/api/v1/media/'.ltrim($path, '/'));
    }

    /**
     * Devuelve el contenido del archivo desde el FTP (para el endpoint proxy).
     */
    public function read(string $path): ?array
    {
        $disk = Storage::disk($this->disk);
        if (! $disk->exists($path)) return null;

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        return [
            'mime' => $mime,
            'contents' => $disk->get($path),
        ];
    }

    private function detectMime(string $tmpPath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        return (string) $finfo->file($tmpPath);
    }

    private function verifyMagicBytes(string $tmpPath, string $expectedMime): void
    {
        $fp = @fopen($tmpPath, 'rb');
        if (! $fp) throw new RuntimeException('No se puede leer el archivo.');
        $head = fread($fp, 12);
        fclose($fp);

        $candidates = self::MAGIC_BYTES[$expectedMime] ?? [];

        if ($expectedMime === 'image/webp') {
            if (! str_starts_with($head, 'RIFF') || ! str_contains($head, 'WEBP')) {
                throw new RuntimeException('La imagen WEBP está corrupta o no es válida.');
            }
            return;
        }

        foreach ($candidates as $magic) {
            if (str_starts_with($head, $magic)) return;
        }

        throw new RuntimeException('Cabecera de archivo no coincide con el tipo declarado.');
    }

    /**
     * Re-encodifica la imagen con GD: cualquier payload incrustado se pierde.
     * Aplica downscale si excede max_w/max_h.
     */
    private function reencodeImage(string $tmpPath, string $mime, int $maxW, int $maxH): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('La extensión GD no está disponible en el servidor.');
        }

        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($tmpPath),
            'image/png' => @imagecreatefrompng($tmpPath),
            'image/webp' => @imagecreatefromwebp($tmpPath),
            default => false,
        };
        if (! $img) {
            throw new RuntimeException('La imagen no se pudo decodificar (posiblemente corrupta).');
        }

        try {
            $w = imagesx($img);
            $h = imagesy($img);

            if ($w > $maxW || $h > $maxH) {
                $ratio = min($maxW / $w, $maxH / $h);
                $newW = max(1, (int) floor($w * $ratio));
                $newH = max(1, (int) floor($h * $ratio));
                $resized = imagecreatetruecolor($newW, $newH);
                if ($mime !== 'image/jpeg') {
                    imagealphablending($resized, false);
                    imagesavealpha($resized, true);
                }
                imagecopyresampled($resized, $img, 0, 0, 0, 0, $newW, $newH, $w, $h);
                imagedestroy($img);
                $img = $resized;
            }

            ob_start();
            switch ($mime) {
                case 'image/jpeg': imagejpeg($img, null, 85); break;
                case 'image/png': imagepng($img, null, 6); break;
                case 'image/webp': imagewebp($img, null, 85); break;
            }
            $bytes = ob_get_clean();
            if (! $bytes) throw new RuntimeException('Falló la codificación de la imagen.');
            return $bytes;
        } finally {
            if ($img) imagedestroy($img);
        }
    }
}
