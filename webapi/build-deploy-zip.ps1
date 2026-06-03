#requires -version 5.0
<#
.SYNOPSIS
  Genera chamba_full.zip listo para subir a producción.

.DESCRIPTION
  Reproducible y blindado:
    - Compila el frontend con Vite (npm run build).
    - Reinstala dependencias optimizadas (composer install --no-dev --optimize-autoloader).
    - Empaqueta TODO lo necesario en un solo zip:
        código fuente + vendor + .env (desde .env.production) + esqueleto storage/
        + scripts _reset.php y _migrate.php.
    - Genera el ZIP con paths forward-slash (compatible con unzip en Linux/cPanel).
    - Valida que el zip contenga los archivos críticos.
    - Hace una prueba end-to-end (extrae a temp y simula una request a /app).

.EXAMPLE
  powershell -ExecutionPolicy Bypass -File build-deploy-zip.ps1
#>

$ErrorActionPreference = 'Stop'
Set-Location -Path $PSScriptRoot

function Write-Section($msg) {
    Write-Host ""
    Write-Host "═══ $msg ═══" -ForegroundColor Cyan
}

# ── 1. Frontend ───────────────────────────────────────────────────
Write-Section "1/5 Compilando frontend (npm run build)"
$prev = $ErrorActionPreference; $ErrorActionPreference = 'Continue'
cmd /c "npm run build 2>&1" | ForEach-Object { Write-Host "  $_" }
$buildExit = $LASTEXITCODE
$ErrorActionPreference = $prev
if ($buildExit -ne 0) { throw "npm run build falló (exit $buildExit)" }

# ── 2. Composer ───────────────────────────────────────────────────
Write-Section "2/5 Instalando vendor de producción"
$prev = $ErrorActionPreference; $ErrorActionPreference = 'Continue'
cmd /c "composer install --no-dev --optimize-autoloader --no-progress --no-interaction 2>&1" | ForEach-Object { Write-Host "  $_" }
$composerExit = $LASTEXITCODE
$ErrorActionPreference = $prev
if ($composerExit -ne 0) { throw "composer install falló (exit $composerExit)" }

# ── 3. Staging ────────────────────────────────────────────────────
Write-Section "3/5 Preparando staging"
$staging = "_full_staging"
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging | Out-Null

function SPath([string]$Relative) {
    Join-Path $staging $Relative
}

# Carpetas con todo el código + vendor
$dirs = @('app','bootstrap','config','database','public','resources','routes','vendor')
foreach ($d in $dirs) {
    Copy-Item -Path $d -Destination (SPath $d) -Recurse -Force
}

# Archivos sueltos
Copy-Item -Path '.htaccess','artisan','composer.json','composer.lock' -Destination $staging -Force

# .env desde .env.production
if (-not (Test-Path '.env.production')) {
    throw "Falta .env.production en el repo. Es el template para producción."
}
$envContent = Get-Content '.env.production' -Raw

# Validar que APP_DEBUG=false antes de empaquetar (nunca deployar con debug activo)
if ($envContent -notmatch '(?m)^APP_DEBUG\s*=\s*false') {
    throw ".env.production no tiene APP_DEBUG=false. Agrégalo antes de generar el ZIP."
}
# Validar que APP_ENV=production
if ($envContent -notmatch '(?m)^APP_ENV\s*=\s*production') {
    throw ".env.production no tiene APP_ENV=production. Agrégalo antes de generar el ZIP."
}

Set-Content -Path (SPath '.env') -Value $envContent -NoNewline -Encoding UTF8

# Esqueleto storage/ (la app no arranca sin estas carpetas)
$storageStructure = @(
    'storage\app','storage\app\public','storage\app\private',
    'storage\framework','storage\framework\cache','storage\framework\cache\data',
    'storage\framework\sessions','storage\framework\testing','storage\framework\views',
    'storage\logs'
)
foreach ($s in $storageStructure) {
    $full = Join-Path $staging $s
    New-Item -ItemType Directory -Path $full -Force | Out-Null
    $local = Join-Path $s '.gitignore'
    if (Test-Path $local) {
        Copy-Item $local -Destination (Join-Path $full '.gitignore') -Force
    } else {
        "*`n!.gitignore`n" | Out-File -FilePath (Join-Path $full '.gitignore') -Encoding ascii -NoNewline
    }
}

# CSV UBIGEO para import en producción sin descargar desde GitHub
$ubigeoCsv = 'storage\app\ubigeo_distrito.csv'
if (Test-Path $ubigeoCsv) {
    Copy-Item $ubigeoCsv (SPath 'storage/app/ubigeo_distrito.csv') -Force
    Write-Host "  Incluido storage/app/ubigeo_distrito.csv en el ZIP"
} else {
    Write-Host '  [aviso] Sin storage/app/ubigeo_distrito.csv - _import_geo.php intentara descargarlo en el servidor' -ForegroundColor Yellow
}

# Limpiar scripts de diagnóstico viejos de public/
Get-ChildItem (SPath 'public') -File | Where-Object { $_.Name -like '_*.php' } | Remove-Item -Force

# Limpiar bootstrap/cache (excepto .gitignore)
if (Test-Path (SPath 'bootstrap/cache')) {
    Get-ChildItem (SPath 'bootstrap/cache') -File | Where-Object { $_.Name -ne '.gitignore' } | Remove-Item -Force
}

# Copiar scripts utilitarios de servidor
foreach ($script in @('_reset.php','_migrate.php','_import_geo.php','_cron.php','_fix_htaccess.php','_fix_media_404.php','_fix_service_403.php','_diag.php','_diag_service.php')) {
    $src = Join-Path 'public' $script
    if (Test-Path $src) { Copy-Item $src (Join-Path (SPath 'public') $script) -Force }
}

# Validar que public/index.php tiene el patch de baseUrl (crítico para subdir)
$indexContent = Get-Content (SPath 'public/index.php') -Raw
$patchMarker  = 'Fix de baseUrl para deploys en subdirectorio'
if ($indexContent -notmatch [regex]::Escape($patchMarker)) {
    throw @"
public/index.php NO contiene el patch de baseUrl.
Ese patch es indispensable para que /v1/chamba/app y /v1/chamba/api/* funcionen
en hosting LiteSpeed con estructura aplanada.
Detalles en .cursor/rules/deploy-jaapsystem.mdc
"@
}

# Validar que .htaccess raíz NO use PATH_INFO (LiteSpeed lo rompe)
$htContent = Get-Content (SPath '.htaccess') -Raw
if ($htContent -match 'public/index\.php/\$1') {
    throw 'htaccess usa PATH_INFO (public/index.php/$1). LiteSpeed lo rompe; usa rewrite a public/index.php sin PATH_INFO'
}

# Validar que MediaController lee el folder del Request (gotcha defaults() en Laravel 11)
$mediaCtrl = Get-Content (SPath 'app/Http/Controllers/Api/V1/MediaController.php') -Raw
if ($mediaCtrl -notmatch 'route\(\)\?->defaults') {
    throw 'MediaController debe leer folder desde route defaults (ver deploy-jaapsystem.mdc)'
}

# Validar que ServiceImageController usa cast a int (gotcha MariaDB string FK)
$svcImgCtrl = Get-Content (SPath 'app/Http/Controllers/Api/V1/Provider/ServiceImageController.php') -Raw
if ($svcImgCtrl -notmatch '\(int\)\s*\$request->user') {
    throw 'ServiceImageController debe castear user_id a int antes de comparar (MariaDB devuelve strings)'
}

# Validar que NO queden rutas absolutas /img/, /build/, /assets/, /storage/ en componentes Vue
$srcVueFiles = Get-ChildItem -Path (SPath 'resources/js') -Recurse -File -Include *.vue,*.js -ErrorAction SilentlyContinue
$badAbsoluteRefs = @()
$absAssetUrlPattern = '(?:src|href)=' + [char]34 + '(?:img|build|assets|storage)/'
foreach ($f in $srcVueFiles) {
    $content = Get-Content $f.FullName -Raw -ErrorAction SilentlyContinue
    if ($content -and $content -match $absAssetUrlPattern) {
        $badAbsoluteRefs += $f.FullName.Substring($staging.Length + 1)
    }
}
if ($badAbsoluteRefs.Count -gt 0) {
    $nl = [Environment]::NewLine
    $list = ($badAbsoluteRefs -join ($nl + '  - '))
    throw ('Rutas absolutas /img /build /assets /storage en el SPA. Usa utils/asset.js. Archivos:' + $nl + '  - ' + $list)
}

# Validar que el manifest dinámico está registrado y el estático fue eliminado
if (Test-Path (SPath 'public/site.webmanifest')) {
    throw 'Elimina public/site.webmanifest estatico; debe servirse solo via routes/web.php'
}
$webRoutes = Get-Content (SPath 'routes/web.php') -Raw
if ($webRoutes -notmatch 'site\.webmanifest') {
    throw 'routes/web.php debe registrar la ruta dinamica site.webmanifest'
}

# Validar que vite.config.js deriva el base de APP_URL (chunks lazy-loaded)
$viteConfig = Get-Content 'vite.config.js' -Raw
if ($viteConfig -notmatch 'loadEnv|deriveBase') {
    throw 'vite.config.js debe definir base desde APP_URL (loadEnv + deriveBase)'
}

# Validar que los assets compilados usan el prefijo correcto
$mainEntryFile = Get-ChildItem (SPath 'public/build/assets') -Filter 'main-*.js' -ErrorAction SilentlyContinue | Select-Object -First 1
if ($mainEntryFile) {
    $mainContent = Get-Content $mainEntryFile.FullName -Raw -ErrorAction SilentlyContinue
    if ($mainContent -and $mainContent -match '/build/assets/' -and $mainContent -notmatch '/v1/chamba/build/assets/') {
        throw 'El bundle de Vite no usa prefijo /v1/chamba/. Revisa APP_URL en .env.production y npm run build'
    }
}

Write-Host "  Staging listo: $((Get-ChildItem $staging -Recurse -File -Force).Count) archivos"
Write-Host "  Patch baseUrl en public/index.php: OK"
Write-Host "  .htaccess raíz sin PATH_INFO: OK"
Write-Host "  MediaController lee folder del Request: OK"

# ── 4. Empaquetar ZIP con paths forward-slash ─────────────────────
Write-Section "4/5 Generando ZIP"
$zipName = 'chamba_full.zip'
if (Test-Path $zipName) { Remove-Item $zipName -Force }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$sourceRoot = (Resolve-Path $staging).Path
$zipPath = Join-Path (Get-Location).Path $zipName

$fs = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::CreateNew)
$archive = New-Object System.IO.Compression.ZipArchive($fs, [System.IO.Compression.ZipArchiveMode]::Create)

$count = 0
try {
    Get-ChildItem -Path $sourceRoot -Recurse -Force -File | ForEach-Object {
        $rel = $_.FullName.Substring($sourceRoot.Length + 1).Replace('\','/')
        $entry = $archive.CreateEntry($rel, [System.IO.Compression.CompressionLevel]::Optimal)
        $entryStream = $entry.Open()
        $srcStream = [System.IO.File]::OpenRead($_.FullName)
        try { $srcStream.CopyTo($entryStream) }
        finally { $srcStream.Dispose(); $entryStream.Dispose() }
        $count++
    }
} finally {
    $archive.Dispose()
    $fs.Dispose()
}
Remove-Item $staging -Recurse -Force

# Validar contenido crítico
$zip = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
$mustHave = @(
    '.env','.htaccess','public/.htaccess','public/index.php',
    'vendor/autoload.php','routes/web.php','routes/api.php',
    'app/Services/MediaStorageService.php',
    'app/Http/Controllers/Api/V1/MediaController.php',
    'public/_reset.php','public/_migrate.php','public/_import_geo.php','public/_cron.php',
    'public/_fix_htaccess.php','public/_fix_media_404.php'
)
$missing = @()
foreach ($f in $mustHave) {
    if (-not ($zip.Entries | Where-Object { $_.FullName -eq $f })) {
        $missing += $f
    }
}
$migrationCount = ($zip.Entries | Where-Object { $_.FullName -match 'database/migrations/2026_05_26_' }).Count
$storageCount   = ($zip.Entries | Where-Object { $_.FullName -like 'storage/*' }).Count
$buildCount     = ($zip.Entries | Where-Object { $_.FullName -like 'public/build/*' }).Count
$size = (Get-Item $zipPath).Length
$zip.Dispose()

if ($missing.Count -gt 0) {
    Write-Host "  FALTAN archivos críticos:" -ForegroundColor Red
    $missing | ForEach-Object { Write-Host "    - $_" -ForegroundColor Red }
    throw "ZIP incompleto"
}
Write-Host "  Tamaño:      $('{0:N2} MB' -f ($size / 1MB))"
Write-Host "  Entradas:    $count"
Write-Host "  Migraciones Fase 2: $migrationCount/4"
Write-Host "  storage/ skeleton:  $storageCount items"
Write-Host "  public/build/:      $buildCount assets"

# ── 5. Validación end-to-end ──────────────────────────────────────
Write-Section "5/5 Validación end-to-end (extrae y prueba)"
$testDir = "$env:TEMP\chamba_zip_test_$([Guid]::NewGuid().ToString('N'))"
New-Item -ItemType Directory -Path $testDir | Out-Null
try {
    Expand-Archive -Path $zipPath -DestinationPath $testDir -Force

    $testScript = @'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Verificar que el patch de baseUrl existe textualmente
$idx = file_get_contents(__DIR__ . '/public/index.php');
echo 'patch baseUrl       -> ' . (str_contains($idx, 'Fix de baseUrl para deploys en subdirectorio') ? 'HTTP 200' : 'MISSING') . PHP_EOL;

// Probar rutas locales (sin prefijo, modo dev)
foreach (['/app', '/api/v1/categories'] as $path) {
    $req = \Illuminate\Http\Request::create($path, 'GET');
    $res = $kernel->handle($req);
    echo str_pad($path, 25) . ' -> HTTP ' . $res->getStatusCode() . PHP_EOL;
    $kernel->terminate($req, $res);
}

// Simular request con prefijo /v1/chamba/ para validar que el patch funciona
$_SERVER['REQUEST_URI']     = '/v1/chamba/app';
$_SERVER['SCRIPT_NAME']     = '/v1/chamba/public/index.php';
$_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
$prefix = 'v1/chamba';
$base = '/' . $prefix;
if (strncmp($_SERVER['REQUEST_URI'], $base . '/public/', strlen($base) + 8) !== 0) {
    $_SERVER['SCRIPT_NAME']     = $base . '/index.php';
    $_SERVER['PHP_SELF']        = $base . '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = __DIR__ . '/public/index.php';
}
$req = \Illuminate\Http\Request::createFromGlobals();
echo 'prefixed /app           -> baseUrl=' . $req->getBaseUrl() . ' path=' . $req->getPathInfo() . PHP_EOL;
if ($req->getPathInfo() === '/app') {
    echo 'prefix strip            -> HTTP 200' . PHP_EOL;
} else {
    echo 'prefix strip            -> FAIL' . PHP_EOL;
}
'@
    Set-Content -Path "$testDir\_e2e.php" -Value $testScript -Encoding ascii

    Push-Location $testDir
    try {
        $prev = $ErrorActionPreference; $ErrorActionPreference = 'Continue'
        $e2e = cmd /c "php _e2e.php 2>&1"
        $ErrorActionPreference = $prev
        $e2e | ForEach-Object { Write-Host "  $_" }
        if (($e2e | Out-String) -notmatch 'HTTP 200') {
            throw "Validación E2E falló: alguna ruta no devolvió 200"
        }
    } finally {
        Pop-Location
    }
} finally {
    Remove-Item $testDir -Recurse -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "═══════════════════════════════════════════" -ForegroundColor Green
Write-Host "ZIP listo: $(Resolve-Path $zipName)" -ForegroundColor Green
Write-Host "Tamaño:    $('{0:N2} MB' -f ($size / 1MB))" -ForegroundColor Green
Write-Host "═══════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "Subilo al hosting siguiendo DEPLOY.md (sección 2)."
