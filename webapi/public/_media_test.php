<?php
/**
 * CHAMBA · Diagnóstico de media (avatars/services/payments)
 * --------------------------------------------------------------------
 * Sube a:  /v1/chamba/public/_media_test.php
 *
 * Uso:
 *   - Sin parámetros: lista últimos avatares en BD + verifica c/u.
 *   - ?path=avatars/xxxx.png: prueba ese archivo específico.
 *   - ?fix=1: setVisibility('public') a TODOS los archivos en
 *     avatars/, services/ y payments/ (chmod 0644 vía FTP).
 *
 * BORRA cuando termines.
 */

use App\Services\MediaStorageService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

// Capturar errores fatales en la salida
@ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');
echo "CHAMBA · Diagnóstico de media\n";
echo str_repeat('=', 70) . "\n";
echo "Fecha: " . date('Y-m-d H:i:s') . "\n";

$appRoot = realpath(__DIR__ . '/..');
echo "App  : {$appRoot}\n\n";

// ── Bootstrapping Laravel ────────────────────────────────────────────
try {
    require $appRoot . '/vendor/autoload.php';
    $app = require $appRoot . '/bootstrap/app.php';

    // Bootear el kernel HTTP procesando un request dummy con host correcto.
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    $req = \Illuminate\Http\Request::create('https://jaapsystem.com/v1/chamba/_diag', 'GET');
    $res = $kernel->handle($req);
    echo "[BOOT] Laravel boot OK (HTTP {$res->getStatusCode()} para /_diag)\n";

    // Verificar APP_URL desde varias fuentes
    echo "[CONFIG] APP_URL env() : " . (env('APP_URL') ?: '(vacío)') . "\n";
    echo "[CONFIG] APP_URL config: " . (config('app.url') ?: '(vacío)') . "\n";

    // Caché de config?
    $configCacheFile = $appRoot . '/bootstrap/cache/config.php';
    if (is_file($configCacheFile)) {
        echo "[CACHE] config.php cacheado: SI (peligro si APP_URL cambió)\n";
        echo "        mtime: " . date('Y-m-d H:i:s', filemtime($configCacheFile)) . "\n";
    } else {
        echo "[CACHE] config.php cacheado: NO\n";
    }

    // Listar rutas media
    $routes = app('router')->getRoutes();
    $mediaRoutes = [];
    foreach ($routes as $r) {
        if (str_contains((string)$r->uri(), 'media/')) {
            $mediaRoutes[] = $r->uri() . ' [' . implode(',', $r->methods()) . ']';
        }
    }
    echo "[ROUTES] media/* registradas: " . count($mediaRoutes) . "\n";
    foreach ($mediaRoutes as $mr) echo "         · {$mr}\n";

    echo "\n";
} catch (\Throwable $e) {
    echo "[BOOT] ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit;
}

/** @var MediaStorageService $media */
$media = app(MediaStorageService::class);

// Helper curl
function httpGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_HEADER         => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => 0,
    ]);
    $resp = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $hsz  = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $ct   = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);
    $body = $resp ? substr((string)$resp, (int)$hsz) : '';
    return ['code' => $code, 'ct' => $ct, 'body' => $body];
}

function probePath(string $path): void {
    /** @var MediaStorageService $media */
    $media = app(MediaStorageService::class);

    echo "── PATH: {$path}\n";
    echo str_repeat('-', 70) . "\n";

    $disk = Storage::disk('chamba_ftp');

    // 1) exists()
    try {
        $exists = $disk->exists($path);
        $size = $exists ? $disk->size($path) : null;
        echo "  FTP exists : " . ($exists ? "SI ({$size} bytes)" : "NO") . "\n";
    } catch (\Throwable $e) {
        echo "  FTP exists : ERROR {$e->getMessage()}\n";
        $exists = false;
    }

    // 2) Listar carpeta
    $folder = explode('/', $path)[0] ?? '';
    if ($folder !== '') {
        try {
            $files = $disk->files($folder);
            $target = basename($path);
            $found = false;
            foreach ($files as $f) {
                if (basename($f) === $target) {
                    $found = true;
                    try {
                        $vis = $disk->getVisibility($f);
                        echo "  FTP listado: ENCONTRADO '{$f}' (visibility: {$vis})\n";
                    } catch (\Throwable $e) {
                        echo "  FTP listado: ENCONTRADO '{$f}' (vis ERR: {$e->getMessage()})\n";
                    }
                    break;
                }
            }
            echo "  FTP dir    : '{$folder}/' tiene " . count($files) . " archivos\n";
            if (!$found) {
                echo "    > '{$target}' NO está en el listado\n";
                $recent = array_slice(array_reverse($files), 0, 8);
                echo "    Archivos del directorio (max 8):\n";
                foreach ($recent as $r) echo "      · {$r}\n";
            }
        } catch (\Throwable $e) {
            echo "  FTP dir    : ERROR {$e->getMessage()}\n";
        }
    }

    // 3) get() — lo que hace el controller realmente
    if ($exists) {
        try {
            $content = $disk->get($path);
            $len = $content === null ? 'null' : strlen($content);
            echo "  FTP get    : " . ($content === null ? "NULL ← problema de lectura" : "OK ({$len} bytes)") . "\n";
        } catch (\Throwable $e) {
            echo "  FTP get    : ERROR {$e->getMessage()}\n";
        }
    }

    // 4) URL generada
    try {
        $url = $media->publicUrl($path);
        echo "  publicUrl  : " . ($url ?: '(null)') . "\n";
    } catch (\Throwable $e) {
        echo "  publicUrl  : ERROR {$e->getMessage()}\n";
        $url = null;
    }

    // 5) Probar URL con curl (la que genera publicUrl, puede tener localhost)
    if ($url) {
        $r = httpGet($url);
        $marker = $r['code'] >= 200 && $r['code'] < 400 ? '✓' : '✗';
        echo "  HTTP(gen)  : {$marker} {$r['code']} ({$r['ct']})\n";
    }

    // 6) Probar la URL REAL en jaapsystem.com (la que usaría el navegador)
    $realUrl = 'https://jaapsystem.com/v1/chamba/api/v1/media/' . ltrim($path, '/');
    $r2 = httpGet($realUrl);
    $marker2 = $r2['code'] >= 200 && $r2['code'] < 400 ? '✓' : '✗';
    echo "  HTTP(real) : {$marker2} {$r2['code']} ({$r2['ct']})\n";
    echo "             URL: {$realUrl}\n";
    if ($r2['code'] !== 200) {
        $snippet = preg_replace('/\s+/', ' ', trim(substr($r2['body'], 0, 250)));
        echo "             body[0:250]: {$snippet}\n";
    } else {
        echo "             size: " . strlen($r2['body']) . " bytes recibidos\n";
    }

    // 7) Probar la URL INTERNAMENTE con SCRIPT_NAME correcto, igual que mi patch
    try {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $serverOverride = [
            'SCRIPT_NAME'     => '/v1/chamba/index.php',
            'PHP_SELF'        => '/v1/chamba/index.php',
            'SCRIPT_FILENAME' => __DIR__ . '/index.php',
            'HTTPS'           => 'on',
        ];
        $internalReq = \Illuminate\Http\Request::create(
            'https://jaapsystem.com/v1/chamba/api/v1/media/' . ltrim($path, '/'),
            'GET',
            [], [], [],
            $serverOverride
        );
        $internalRes = $kernel->handle($internalReq);
        $internalCode = $internalRes->getStatusCode();
        $marker3 = $internalCode >= 200 && $internalCode < 400 ? '✓' : '✗';
        echo "  HTTP(intern): {$marker3} {$internalCode} (Kernel + SCRIPT_NAME patched)\n";
        echo "             baseUrl: " . $internalReq->getBaseUrl() . "\n";
        echo "             pathInfo: " . $internalReq->getPathInfo() . "\n";
        $route = $internalReq->route();
        if ($route) {
            echo "             route: " . $route->uri() . " -> " . ($route->getActionName() ?: '?') . "\n";
            $middlewares = $route->gatherMiddleware();
            echo "             middlewares: " . (empty($middlewares) ? '(ninguno)' : implode(', ', $middlewares)) . "\n";
        } else {
            echo "             route: NINGUNA matcheó\n";
        }
        if ($internalCode !== 200) {
            $body = (string) $internalRes->getContent();
            $snippet = preg_replace('/\s+/', ' ', trim(substr($body, 0, 250)));
            echo "             body[0:250]: {$snippet}\n";
        }
        $kernel->terminate($internalReq, $internalRes);
    } catch (\Throwable $e) {
        echo "  HTTP(intern): EXCEPTION " . $e->getMessage() . "\n";
    }

    // 8) Probar URL alternativa SIN extensión (.png) para descartar handling de LiteSpeed
    $noExtUrl = 'https://jaapsystem.com/v1/chamba/api/v1/media/avatars/testname';
    $r4 = httpGet($noExtUrl);
    $marker4 = $r4['code'] >= 200 && $r4['code'] < 400 ? '✓' : ($r4['code'] === 404 ? '·' : '✗');
    echo "  HTTP(noext): {$marker4} {$r4['code']} ({$r4['ct']})\n";
    echo "             URL: {$noExtUrl}\n";
    if ($r4['code'] === 404) {
        $isLaravel = stripos($r4['body'], 'lang="en"') !== false || stripos($r4['body'], 'normalize.css') !== false;
        echo "             -> 404 de " . ($isLaravel ? "LARAVEL (controller hizo abort, archivo no existe)" : "SERVIDOR (rewrite no aplicó)") . "\n";
    }

    // 9) Probar URL con .php para ver si la extensión cambia el comportamiento
    $phpUrl = 'https://jaapsystem.com/v1/chamba/api/v1/categories';  // sin extensión
    $r5 = httpGet($phpUrl);
    echo "  HTTP(api*) : " . ($r5['code'] === 200 ? '✓' : '✗') . " {$r5['code']} en /api/v1/categories (control: debe ser 200)\n";

    // 10) LLAMAR DIRECTAMENTE al MediaStorageService::read() — lo que hace el controller
    echo "\n  [STEP-BY-STEP del controller]\n";
    try {
        $mediaService = app(MediaStorageService::class);
        $result = $mediaService->read($path);
        if ($result === null) {
            echo "    media->read()  : NULL ← aquí está el 404\n";
        } else {
            echo "    media->read()  : OK (mime={$result['mime']}, " . strlen($result['contents'] ?? '') . " bytes)\n";
        }
    } catch (\Throwable $e) {
        echo "    media->read()  : EXCEPTION " . get_class($e) . ': ' . $e->getMessage() . "\n";
    }

    // 11) Llamar al controller directamente
    try {
        $ctrl = app(\App\Http\Controllers\Api\V1\MediaController::class);
        $folder = explode('/', $path)[0];
        $name   = substr($path, strlen($folder) + 1);
        $reqCtrl = \Illuminate\Http\Request::create('/api/v1/media/' . $path, 'GET');
        $res = $ctrl->show($reqCtrl, $folder, $name);
        echo "    ctrl->show()   : HTTP " . $res->getStatusCode() . " (" . strlen($res->getContent()) . " bytes)\n";
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        echo "    ctrl->show()   : HttpException " . $e->getStatusCode() . ' - ' . ($e->getMessage() ?: '(sin mensaje)') . "\n";
    } catch (\Throwable $e) {
        echo "    ctrl->show()   : EXCEPTION " . get_class($e) . ': ' . $e->getMessage() . "\n";
    }

    // 12) Estado del cache (puede tener un 404 cacheado)
    try {
        $cacheKey = "media:{$path}";
        $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
        echo "    Cache::get()   : " . (is_array($cached) ? "HIT (mime={$cached['mime']}, " . strlen($cached['contents'] ?? '') . " bytes)" : var_export($cached, true)) . "\n";
        \Illuminate\Support\Facades\Cache::forget($cacheKey);
        echo "    Cache::forget(): hecho\n";
    } catch (\Throwable $e) {
        echo "    Cache          : ERROR {$e->getMessage()}\n";
    }

    // 13) Listar contenido del middleware group 'api'
    echo "\n  [MIDDLEWARE GROUP 'api']\n";
    try {
        $router = app('router');
        $groups = $router->getMiddlewareGroups();
        if (isset($groups['api'])) {
            foreach ($groups['api'] as $mw) {
                echo "    · " . (is_string($mw) ? $mw : get_class($mw)) . "\n";
            }
        } else {
            echo "    (sin grupo 'api' definido)\n";
        }
    } catch (\Throwable $e) {
        echo "    ERROR {$e->getMessage()}\n";
    }

    // 14) Repetir HTTP(intern) AHORA SIN middleware 'api' para confirmar
    echo "\n  [REPRO sin middleware 'api']\n";
    try {
        $router = app('router');
        $origGroups = $router->getMiddlewareGroups();
        $router->middlewareGroup('api', []);

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $req2 = \Illuminate\Http\Request::create(
            'https://jaapsystem.com/v1/chamba/api/v1/media/' . ltrim($path, '/'),
            'GET',
            [], [], [],
            [
                'SCRIPT_NAME'     => '/v1/chamba/index.php',
                'PHP_SELF'        => '/v1/chamba/index.php',
                'SCRIPT_FILENAME' => __DIR__ . '/index.php',
                'HTTPS'           => 'on',
            ]
        );
        $res2 = $kernel->handle($req2);
        echo "    kernel HTTP    : " . $res2->getStatusCode() . " ({$res2->headers->get('Content-Type')})\n";
        $body = (string) $res2->getContent();
        echo "    size           : " . strlen($body) . " bytes\n";
        // Mostrar TODOS los headers del response
        foreach ($res2->headers->all() as $h => $v) {
            echo "    header         : {$h}: " . implode(', ', $v) . "\n";
        }
        $router->middlewareGroup('api', $origGroups['api'] ?? []);
    } catch (\Throwable $e) {
        echo "    EXCEPT         : " . get_class($e) . ': ' . $e->getMessage() . "\n";
    }

    // 14b) Listar TODOS los middleware del Kernel HTTP (globales + grupos + alias)
    echo "\n  [KERNEL HTTP middleware]\n";
    try {
        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $refl = new ReflectionClass($kernel);

        // middleware globales
        $globalProp = $refl->getProperty('middleware');
        $globalProp->setAccessible(true);
        $globalMw = $globalProp->getValue($kernel);
        echo "    GLOBAL middleware (" . count($globalMw) . "):\n";
        foreach ($globalMw as $m) echo "      · " . (is_string($m) ? $m : get_class($m)) . "\n";

        // middleware groups
        $groupsProp = $refl->getProperty('middlewareGroups');
        $groupsProp->setAccessible(true);
        $groups = $groupsProp->getValue($kernel);
        foreach ($groups as $name => $mws) {
            echo "    GROUP '{$name}' (" . count($mws) . "):\n";
            foreach ($mws as $m) echo "      · " . (is_string($m) ? $m : get_class($m)) . "\n";
        }
    } catch (\Throwable $e) {
        echo "    ERROR {$e->getMessage()}\n";
    }

    // 14c) Capturar la EXCEPCIÓN REAL que produce el 404 (interceptando el ExceptionHandler)
    echo "\n  [INTERCEPT EXCEPTION]\n";
    try {
        $captured = ['exc' => null, 'msg' => '', 'class' => '', 'trace' => ''];
        // Decorar el ExceptionHandler para capturar todo
        $origHandler = app(\Illuminate\Contracts\Debug\ExceptionHandler::class);
        app()->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, new class($origHandler, $captured) implements \Illuminate\Contracts\Debug\ExceptionHandler {
            public function __construct(private $orig, public &$captured) {}
            public function report(\Throwable $e): void {
                $this->captured['exc'] = $e;
                $this->captured['class'] = get_class($e);
                $this->captured['msg']   = $e->getMessage();
                $this->captured['trace'] = $e->getTraceAsString();
                $this->orig->report($e);
            }
            public function shouldReport(\Throwable $e): bool { return $this->orig->shouldReport($e); }
            public function render($request, \Throwable $e) {
                $this->captured['exc']   = $e;
                $this->captured['class'] = get_class($e);
                $this->captured['msg']   = $e->getMessage();
                $this->captured['trace'] = $e->getTraceAsString();
                return $this->orig->render($request, $e);
            }
            public function renderForConsole($output, \Throwable $e): void { $this->orig->renderForConsole($output, $e); }
        });

        $kernel2 = app(\Illuminate\Contracts\Http\Kernel::class);
        $req4 = \Illuminate\Http\Request::create(
            'https://jaapsystem.com/v1/chamba/api/v1/media/' . ltrim($path, '/'),
            'GET', [], [], [],
            [
                'SCRIPT_NAME'     => '/v1/chamba/index.php',
                'PHP_SELF'        => '/v1/chamba/index.php',
                'SCRIPT_FILENAME' => __DIR__ . '/index.php',
                'HTTPS'           => 'on',
            ]
        );
        $res4 = $kernel2->handle($req4);
        $cap = app(\Illuminate\Contracts\Debug\ExceptionHandler::class)->captured;
        echo "    HTTP code      : " . $res4->getStatusCode() . "\n";
        if ($cap['exc']) {
            echo "    EXCEPTION      : {$cap['class']}\n";
            echo "    MESSAGE        : {$cap['msg']}\n";
            echo "    --- trace (top 10) ---\n";
            $traceLines = explode("\n", $cap['trace']);
            foreach (array_slice($traceLines, 0, 10) as $line) echo "    {$line}\n";
        } else {
            echo "    (no se capturó excepción — el 404 NO viene de exception handler)\n";
        }

        // Restaurar
        app()->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, $origHandler);
    } catch (\Throwable $e) {
        echo "    EXCEPT outside : " . get_class($e) . ': ' . $e->getMessage() . "\n";
    }

    // 15) BINDING TRICK — reemplazar el controller con uno que SIEMPRE retorna 'HELLO'
    //     Si vemos 'HELLO' → el kernel SI llega al controller (problema en el código del controller real)
    //     Si vemos 404 → el kernel ABORTA antes del controller (problema arriba en el pipeline)
    echo "\n  [BIND-FAKE-CONTROLLER]\n";
    try {
        app()->bind(\App\Http\Controllers\Api\V1\MediaController::class, function () {
            return new class {
                public function show(\Illuminate\Http\Request $r, $folder = null, $name = null) {
                    return response("HELLO_FROM_FAKE folder={$folder} name={$name}", 200, ['Content-Type' => 'text/plain']);
                }
            };
        });

        $kernel = app(\Illuminate\Contracts\Http\Kernel::class);
        $req3 = \Illuminate\Http\Request::create(
            'https://jaapsystem.com/v1/chamba/api/v1/media/' . ltrim($path, '/'),
            'GET',
            [], [], [],
            [
                'SCRIPT_NAME'     => '/v1/chamba/index.php',
                'PHP_SELF'        => '/v1/chamba/index.php',
                'SCRIPT_FILENAME' => __DIR__ . '/index.php',
                'HTTPS'           => 'on',
            ]
        );
        $res3 = $kernel->handle($req3);
        echo "    kernel HTTP    : " . $res3->getStatusCode() . "\n";
        echo "    body           : " . substr((string)$res3->getContent(), 0, 200) . "\n";
        if (str_contains((string)$res3->getContent(), 'HELLO_FROM_FAKE')) {
            echo "    >>> CONCLUSIÓN: el kernel SI llega al controller. El problema está EN el controller real.\n";
        } else {
            echo "    >>> CONCLUSIÓN: el kernel NO llega al controller. Problema antes del controller (middleware global).\n";
        }
    } catch (\Throwable $e) {
        echo "    EXCEPT         : " . get_class($e) . ': ' . $e->getMessage() . "\n";
    }

    // 14) Últimas 30 líneas del log de Laravel hoy
    echo "\n  [LOG de Laravel]\n";
    $logFile = dirname(__DIR__) . '/storage/logs/laravel-' . date('Y-m-d') . '.log';
    if (is_file($logFile)) {
        $lines = @file($logFile, FILE_IGNORE_NEW_LINES);
        $last = array_slice($lines, -30);
        foreach ($last as $l) echo "    " . $l . "\n";
    } else {
        echo "    no hay log {$logFile}\n";
    }

    echo "\n";
}

// ── Modo FIX ─────────────────────────────────────────────────────────
if (($_GET['fix'] ?? '') === '1') {
    echo "[MODO FIX] setVisibility('public') a todos los archivos\n";
    echo str_repeat('-', 70) . "\n";
    $disk = Storage::disk('chamba_ftp');
    foreach (['avatars', 'services', 'payments'] as $folder) {
        echo "\n[{$folder}/]\n";
        try {
            $files = $disk->files($folder);
            $n = 0; $errors = 0;
            foreach ($files as $f) {
                try {
                    $disk->setVisibility($f, 'public');
                    $n++;
                } catch (\Throwable $e) {
                    $errors++;
                    echo "  ! {$f}: {$e->getMessage()}\n";
                }
            }
            echo "  ✓ {$n} archivos reparados, {$errors} errores\n";
        } catch (\Throwable $e) {
            echo "  ERROR listando: {$e->getMessage()}\n";
        }
    }
    echo "\nListo. Borra _media_test.php cuando termines.\n";
    exit;
}

// ── Path específico ──────────────────────────────────────────────────
$path = $_GET['path'] ?? '';
if ($path !== '') {
    probePath($path);
    echo "Listo.\n";
    exit;
}

// ── Listado de últimos avatares en BD ────────────────────────────────
echo "[INFO] Últimos avatares en BD\n\n";
try {
    $cols = DB::getSchemaBuilder()->getColumnListing('users');
    if (! in_array('avatar_path', $cols, true)) {
        echo "  No existe users.avatar_path. Columnas: " . implode(', ', $cols) . "\n";
    } else {
        $rows = DB::table('users')
            ->whereNotNull('avatar_path')
            ->where('avatar_path', '<>', '')
            ->orderByDesc('updated_at')
            ->limit(3)
            ->get(['id', 'name', 'email', 'avatar_path', 'updated_at']);
        if ($rows->isEmpty()) {
            echo "  (no hay usuarios con avatar)\n\n";
        } else {
            foreach ($rows as $u) {
                echo "USER #{$u->id} {$u->name} <{$u->email}>  (updated {$u->updated_at})\n";
                probePath((string) $u->avatar_path);
            }
        }
    }
} catch (\Throwable $e) {
    echo "  ERROR BD: {$e->getMessage()}\n";
}

echo str_repeat('=', 70) . "\n";
echo "Para probar archivo específico:\n";
echo "  ?path=avatars/EL_NOMBRE.png\n";
echo "Para reparar permisos de TODO:\n";
echo "  ?fix=1\n";
echo "BORRA este archivo cuando termines.\n";
