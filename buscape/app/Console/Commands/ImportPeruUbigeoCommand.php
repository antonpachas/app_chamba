<?php

namespace App\Console\Commands;

use App\Models\Department;
use App\Models\District;
use App\Models\Province;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

final class ImportPeruUbigeoCommand extends Command
{
    protected $signature = 'chamba:import-peru-ubigeo
                            {--url= : URL CSV (default: ubigeo-peru-aumentado)}
                            {--file= : Ruta local al CSV (evita descarga HTTP)}
                            {--offset=0 : Saltar N filas de datos (para lotes HTTP)}
                            {--limit=0 : Procesar como máximo N filas (0 = todas)}
                            {--fresh : Vacía tablas geo antes de importar (¡cuidado en producción!)}';

    protected $description = 'Importa departamentos, provincias y distritos del Perú (UBIGEO INEI + coordenadas)';

    private const DEFAULT_CSV_URL = 'https://raw.githubusercontent.com/jmcastagnetto/ubigeo-peru-aumentado/main/ubigeo_distrito.csv';

    /** @var array<string, int> */
    private array $deptIds = [];

    /** @var array<string, int> */
    private array $provIds = [];

    private bool $cachesWarmed = false;

    public function handle(): int
    {
        if (! Schema::hasTable('departments')) {
            $this->error('Faltan tablas geo. Ejecuta las migraciones del proyecto.');

            return self::FAILURE;
        }

        $file = (string) $this->option('file');
        if ($file !== '' && is_file($file)) {
            $this->info("Leyendo catálogo desde archivo: {$file}");
            $body = (string) file_get_contents($file);
        } else {
            $url = (string) ($this->option('url') ?: self::DEFAULT_CSV_URL);
            $this->info("Descargando catálogo desde: {$url}");

            $response = Http::timeout(120)->get($url);
            if (! $response->successful()) {
                $this->error('No se pudo descargar el CSV de ubigeo. Usa --file=ruta/local.csv si hay problemas SSL.');

                return self::FAILURE;
            }
            $body = $response->body();
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($body));
        if ($lines === false || count($lines) < 2) {
            $this->error('CSV vacío o inválido.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            if (! $this->confirm('¿Vaciar departments, provinces y districts? Esto puede romper FKs si hay datos.')) {
                return self::SUCCESS;
            }
            $this->withRetry(function (): void {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                District::query()->delete();
                Province::query()->delete();
                Department::query()->delete();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            });
            $this->warn('Tablas geo vaciadas.');
            $this->deptIds = [];
            $this->provIds = [];
            $this->cachesWarmed = false;
        }

        $header = str_getcsv(array_shift($lines));
        $idx = array_flip(array_map('strtolower', $header));

        foreach (['inei', 'departamento', 'provincia', 'distrito', 'latitude', 'longitude'] as $col) {
            if (! isset($idx[$col])) {
                $this->error("Columna «{$col}» no encontrada en CSV.");

                return self::FAILURE;
            }
        }

        $offset = max(0, (int) $this->option('offset'));
        $limit = max(0, (int) $this->option('limit'));
        if ($offset > 0 || $limit > 0) {
            $lines = array_slice($lines, $offset, $limit > 0 ? $limit : null);
            $this->info('Lote: offset='.$offset.', filas='.count($lines));
        }

        $this->warmGeoCaches();

        $countDept = 0;
        $countProv = 0;
        $countDist = 0;
        $rowNum = 0;

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $row = str_getcsv($line);
            if (count($row) < 6) {
                continue;
            }

            $inei = str_pad(preg_replace('/\D/', '', (string) $row[$idx['inei']]), 6, '0', STR_PAD_LEFT);
            if (strlen($inei) !== 6) {
                continue;
            }

            $deptCode = substr($inei, 0, 2);
            $provCode = substr($inei, 0, 4);
            $deptName = trim((string) $row[$idx['departamento']]);
            $provName = trim((string) $row[$idx['provincia']]);
            $distName = trim((string) $row[$idx['distrito']]);
            $lat = $this->floatOr($row[$idx['latitude']] ?? null, -12.046374);
            $lng = $this->floatOr($row[$idx['longitude']] ?? null, -77.042793);

            if ($deptName === '' || $provName === '' || $distName === '') {
                continue;
            }

            try {
                if (! isset($this->deptIds[$deptCode])) {
                    $this->deptIds[$deptCode] = $this->upsertDepartment($deptCode, $deptName, $lat, $lng);
                    $countDept++;
                }

                if (! isset($this->provIds[$provCode])) {
                    $this->provIds[$provCode] = $this->upsertProvince(
                        $provCode,
                        $this->deptIds[$deptCode],
                        $provName,
                        $lat,
                        $lng,
                    );
                    $countProv++;
                }

                $this->upsertDistrict($inei, $this->provIds[$provCode], $distName, $lat, $lng);
                $countDist++;
            } catch (QueryException $e) {
                if ($this->isLockError($e)) {
                    $this->error('Bloqueo en BD (otra importación activa o lote anterior colgado).');
                    $this->error('Espera 2–3 min, cierra otras pestañas/terminales y reintenta el mismo offset.');
                    $this->line("Última fila del lote: ~{$rowNum} (ubigeo {$inei})");

                    return self::FAILURE;
                }
                throw $e;
            }

            $rowNum++;
        }

        $this->info("Importación lista: ~{$countDept} deptos nuevos en caché, ~{$countProv} prov., {$countDist} distritos procesados.");
        $this->line('Departamentos en BD: '.Department::query()->count());
        $this->line('Provincias en BD: '.Province::query()->count());
        $this->line('Distritos en BD: '.District::query()->count());

        return self::SUCCESS;
    }

    private function warmGeoCaches(): void
    {
        if ($this->cachesWarmed) {
            return;
        }

        if (Schema::hasColumn('departments', 'ubigeo_code')) {
            foreach (Department::query()->whereNotNull('ubigeo_code')->get(['id', 'ubigeo_code']) as $d) {
                $this->deptIds[str_pad((string) $d->ubigeo_code, 2, '0', STR_PAD_LEFT)] = (int) $d->id;
            }
        } else {
            foreach (Department::query()->get(['id', 'name']) as $d) {
                $this->deptIds[mb_strtolower($d->name)] = (int) $d->id;
            }
        }

        if (Schema::hasColumn('provinces', 'ubigeo_code')) {
            foreach (Province::query()->whereNotNull('ubigeo_code')->get(['id', 'ubigeo_code']) as $p) {
                $this->provIds[str_pad((string) $p->ubigeo_code, 4, '0', STR_PAD_LEFT)] = (int) $p->id;
            }
        }

        $this->cachesWarmed = true;
        $this->line('Caché geo: '.count($this->deptIds).' deptos, '.count($this->provIds).' provincias en memoria.');
    }

    private function upsertDepartment(string $deptCode, string $deptName, float $lat, float $lng): int
    {
        $now = now();
        $values = [
            'name' => $deptName,
            'latitude' => $lat,
            'longitude' => $lng,
            'is_active' => true,
            'updated_at' => $now,
        ];

        $match = Schema::hasColumn('departments', 'ubigeo_code')
            ? ['ubigeo_code' => $deptCode]
            : ['name' => $deptName];

        if (Schema::hasColumn('departments', 'ubigeo_code')) {
            $values['ubigeo_code'] = $deptCode;
        }

        return $this->withRetry(function () use ($match, $values, $deptCode, $now): int {
            $exists = DB::table('departments')->where($match)->exists();
            DB::table('departments')->updateOrInsert($match, array_merge($values, $exists ? [] : ['created_at' => $now]));

            $id = (int) DB::table('departments')->where($match)->value('id');
            if ($id === 0 && Schema::hasColumn('departments', 'ubigeo_code')) {
                $id = (int) DB::table('departments')->where('ubigeo_code', $deptCode)->value('id');
            }
            if ($id === 0) {
                $id = (int) DB::table('departments')->whereRaw('LOWER(name) = ?', [mb_strtolower($deptName)])->value('id');
            }

            return $id;
        });
    }

    private function upsertProvince(string $provCode, int $departmentId, string $provName, float $lat, float $lng): int
    {
        $now = now();
        $values = [
            'department_id' => $departmentId,
            'name' => $provName,
            'latitude' => $lat,
            'longitude' => $lng,
            'is_active' => true,
            'updated_at' => $now,
        ];

        $match = Schema::hasColumn('provinces', 'ubigeo_code')
            ? ['ubigeo_code' => $provCode]
            : ['department_id' => $departmentId, 'name' => $provName];

        if (Schema::hasColumn('provinces', 'ubigeo_code')) {
            $values['ubigeo_code'] = $provCode;
        }

        return $this->withRetry(function () use ($match, $values, $provCode, $departmentId, $provName, $now): int {
            $exists = DB::table('provinces')->where($match)->exists();
            DB::table('provinces')->updateOrInsert($match, array_merge($values, $exists ? [] : ['created_at' => $now]));

            $id = (int) DB::table('provinces')->where($match)->value('id');
            if ($id === 0 && Schema::hasColumn('provinces', 'ubigeo_code')) {
                $id = (int) DB::table('provinces')->where('ubigeo_code', $provCode)->value('id');
            }
            if ($id === 0) {
                $id = (int) DB::table('provinces')
                    ->where('department_id', $departmentId)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($provName)])
                    ->value('id');
            }

            return $id;
        });
    }

    private function upsertDistrict(string $inei, int $provinceId, string $distName, float $lat, float $lng): void
    {
        $now = now();
        $values = [
            'province_id' => $provinceId,
            'name' => $distName,
            'latitude' => $lat,
            'longitude' => $lng,
            'is_active' => true,
            'updated_at' => $now,
        ];

        $match = Schema::hasColumn('districts', 'ubigeo')
            ? ['ubigeo' => $inei]
            : ['province_id' => $provinceId, 'name' => $distName];

        if (Schema::hasColumn('districts', 'ubigeo')) {
            $values['ubigeo'] = $inei;
        }

        $this->withRetry(function () use ($match, $values, $now): void {
            $exists = DB::table('districts')->where($match)->exists();
            DB::table('districts')->updateOrInsert($match, array_merge($values, $exists ? [] : ['created_at' => $now]));
        });
    }

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    private function withRetry(callable $callback, int $maxAttempts = 6): mixed
    {
        $attempt = 0;
        beginning:
        try {
            return $callback();
        } catch (QueryException $e) {
            if (! $this->isLockError($e) || $attempt >= $maxAttempts - 1) {
                throw $e;
            }
            $attempt++;
            usleep(300_000 * $attempt);

            goto beginning;
        }
    }

    private function isLockError(QueryException $e): bool
    {
        $code = (int) ($e->errorInfo[1] ?? 0);
        if (in_array($code, [1205, 1213, 1206], true)) {
            return true;
        }

        return str_contains($e->getMessage(), 'Lock wait timeout')
            || str_contains($e->getMessage(), 'Deadlock');
    }

    private function floatOr(mixed $value, float $fallback): float
    {
        if ($value === null || $value === '') {
            return $fallback;
        }
        $n = (float) str_replace(',', '.', (string) $value);

        return ($n >= -90 && $n <= 90) ? $n : $fallback;
    }
}
