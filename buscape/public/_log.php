<?php
/**
 * Muestra el log más reciente de Laravel y prueba la conexión a BD
 * usando el .env directamente (sin pasar por el container HTTP).
 *
 * Sube a /v1/chamba/public/_log.php y visita la URL.
 * BORRA después de usarlo.
 */

header('Content-Type: text/plain; charset=utf-8');
@error_reporting(E_ALL);
@ini_set('display_errors', '1');

$base = dirname(__DIR__);

echo "=== INFO ===\n";
echo "PHP " . PHP_VERSION . " (" . PHP_SAPI . ")\n";
echo "App: $base\n\n";

// Parser .env mínimo
echo "=== .ENV PARSEADO ===\n";
$env = [];
if (file_exists("$base/.env")) {
    foreach (file("$base/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (! str_contains($line, '=')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        $v = trim($v, "\"' \r\n");
        $env[$k] = $v;
    }
    foreach (['APP_URL','APP_ENV','APP_DEBUG','APP_KEY','DB_CONNECTION','DB_HOST','DB_PORT','DB_DATABASE','DB_USERNAME','DB_PASSWORD'] as $k) {
        $val = $env[$k] ?? '(no definido)';
        if ($k === 'APP_KEY' || $k === 'DB_PASSWORD') $val = $val ? substr($val, 0, 14).'…' : '(vacío)';
        echo str_pad($k, 18) . " = $val\n";
    }
} else {
    echo "(no existe)\n";
}

echo "\n=== CONEXIÓN A BD (PDO directo) ===\n";
try {
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
        $env['DB_HOST'] ?? '', $env['DB_PORT'] ?? '3306', $env['DB_DATABASE'] ?? '');
    $pdo = new PDO($dsn, $env['DB_USERNAME'] ?? '', $env['DB_PASSWORD'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 10,
    ]);
    $row = $pdo->query("SELECT VERSION() AS v, DATABASE() AS db")->fetch();
    echo "OK · MySQL " . $row['v'] . " · DB " . $row['db'] . "\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas (" . count($tables) . "): " . implode(', ', array_slice($tables, 0, 10)) . (count($tables) > 10 ? ', …' : '') . "\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== ÚLTIMO LOG DE LARAVEL ===\n";
$logs = glob("$base/storage/logs/*.log") ?: [];
if (! $logs) {
    echo "(sin logs)\n";
} else {
    usort($logs, fn($a, $b) => filemtime($b) - filemtime($a));
    $latest = $logs[0];
    echo "Archivo: " . basename($latest) . " (" . date('Y-m-d H:i:s', filemtime($latest)) . ")\n";
    echo "Tamaño: " . number_format(filesize($latest)) . " bytes\n\n";
    $content = file_get_contents($latest);
    echo "----- ÚLTIMOS 6000 BYTES -----\n";
    echo substr($content, -6000);
    echo "\n----- FIN -----\n";
}

echo "\n=== INTENTO DE BOOT LARAVEL ===\n";
try {
    require $base.'/vendor/autoload.php';
    $app = require_once $base.'/bootstrap/app.php';
    echo "Bootstrap: OK\n";

    // Forzar el HTTP kernel para que registre los providers reales (incluido db)
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

    // Crear request fake y bootear
    $request = \Illuminate\Http\Request::create('/setup-test', 'GET');
    $kernel->bootstrap();
    echo "Kernel bootstrap: OK\n";
    echo "APP_URL config: " . config('app.url') . "\n";

    try {
        $pdo = $app->make('db')->connection()->getPdo();
        echo "Laravel DB: OK (" . $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION) . ")\n";
    } catch (\Throwable $e) {
        echo "Laravel DB: " . $e->getMessage() . "\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . get_class($e) . "\n";
    echo "Mensaje: " . $e->getMessage() . "\n";
    echo "En: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace (top 8):\n";
    foreach (array_slice(explode("\n", $e->getTraceAsString()), 0, 8) as $l) {
        echo "  $l\n";
    }
}
