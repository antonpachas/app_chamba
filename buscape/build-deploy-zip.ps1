#requires -version 5.0
<#
.SYNOPSIS
  Genera buscape.zip listo para subir al servidor.

.DESCRIPTION
  1. Compila el frontend con Vite  (npm run build)
  2. Instala vendor de produccion  (composer install --no-dev)
  3. Empaqueta TODO en un ZIP listo para descomprimir en el servidor:
       codigo + vendor + .env (desde .env.production) + esqueleto storage/
  4. Valida que el ZIP tenga los archivos criticos
  5. Prueba end-to-end basica con PHP

.PREREQUISITOS
  - PHP en PATH
  - Composer en PATH
  - Node/npm en PATH
  - Archivo .env.production completo (copialo de .env.example y rellena los datos)

.EJEMPLO
  powershell -ExecutionPolicy Bypass -File build-deploy-zip.ps1
#>

$ErrorActionPreference = 'Stop'
Set-Location -Path $PSScriptRoot

function Write-Step($n, $total, $msg) {
    Write-Host ""
    Write-Host "── $n/$total  $msg" -ForegroundColor Cyan
}

# ─────────────────────────────────────────────────────────────────────────────
# 0. Validar que existe .env.production
# ─────────────────────────────────────────────────────────────────────────────
if (-not (Test-Path '.env.production')) {
    Write-Host ""
    Write-Host "ERROR: Falta el archivo .env.production" -ForegroundColor Red
    Write-Host "  1. Copia .env.example  →  .env.production" -ForegroundColor Yellow
    Write-Host "  2. Rellena todos los valores (APP_URL, DB_*, MAIL_*, etc.)" -ForegroundColor Yellow
    exit 1
}

$envContent = Get-Content '.env.production' -Raw

if ($envContent -notmatch '(?m)^APP_DEBUG\s*=\s*false') {
    throw ".env.production debe tener  APP_DEBUG=false  antes de hacer deploy."
}
if ($envContent -notmatch '(?m)^APP_ENV\s*=\s*production') {
    throw ".env.production debe tener  APP_ENV=production  antes de hacer deploy."
}
if ($envContent -match '(?m)^APP_URL\s*=\s*https?://localhost') {
    throw ".env.production tiene APP_URL apuntando a localhost. Cambialo al dominio real."
}

Write-Host "  .env.production: OK" -ForegroundColor Green

# ─────────────────────────────────────────────────────────────────────────────
# 1. Frontend
# ─────────────────────────────────────────────────────────────────────────────
Write-Step 1 4 "Compilando frontend (npm run build)"
$prev = $ErrorActionPreference; $ErrorActionPreference = 'Continue'
cmd /c "npm run build 2>&1" | ForEach-Object { Write-Host "  $_" }
$exit = $LASTEXITCODE
$ErrorActionPreference = $prev
if ($exit -ne 0) { throw "npm run build fallo (exit $exit)" }
if (-not (Test-Path 'public/build')) { throw "public/build no se genero. Revisa el error de Vite." }

# ─────────────────────────────────────────────────────────────────────────────
# 2. Composer
# ─────────────────────────────────────────────────────────────────────────────
Write-Step 2 4 "Instalando vendor de produccion"
$prev = $ErrorActionPreference; $ErrorActionPreference = 'Continue'
cmd /c "composer install --no-dev --optimize-autoloader --no-progress --no-interaction 2>&1" | ForEach-Object { Write-Host "  $_" }
$exit = $LASTEXITCODE
$ErrorActionPreference = $prev
if ($exit -ne 0) { throw "composer install fallo (exit $exit)" }

# ─────────────────────────────────────────────────────────────────────────────
# 3. Preparar staging
# ─────────────────────────────────────────────────────────────────────────────
Write-Step 3 4 "Preparando staging"
$staging = "_deploy_staging"
if (Test-Path $staging) { Remove-Item $staging -Recurse -Force }
New-Item -ItemType Directory -Path $staging | Out-Null

# Directorios de codigo
foreach ($d in @('app','bootstrap','config','database','public','resources','routes','vendor')) {
    Copy-Item -Path $d -Destination "$staging\$d" -Recurse -Force
}

# Archivos raiz
Copy-Item -Path '.htaccess','artisan','composer.json','composer.lock' -Destination $staging -Force

# .env desde .env.production
Set-Content -Path "$staging\.env" -Value $envContent -NoNewline -Encoding UTF8

# Esqueleto de storage/ (Laravel no arranca sin estas carpetas)
$storageDirs = @(
    'storage\app','storage\app\public','storage\app\private',
    'storage\framework\cache\data','storage\framework\sessions',
    'storage\framework\testing','storage\framework\views',
    'storage\logs'
)
foreach ($s in $storageDirs) {
    $full = Join-Path $staging $s
    New-Item -ItemType Directory -Path $full -Force | Out-Null
    # Gitignore placeholder para que el directorio no quede vacio
    "*`n!.gitignore`n" | Out-File -FilePath "$full\.gitignore" -Encoding ascii -NoNewline
}

# Limpiar cache de bootstrap (no debe existir en deploy limpio)
$bsCache = "$staging\bootstrap\cache"
if (Test-Path $bsCache) {
    Get-ChildItem $bsCache -File | Where-Object Name -ne '.gitignore' | Remove-Item -Force
}

# Copiar scripts de utilidad del servidor (si existen)
foreach ($s in @('_migrate.php','_reset.php','_diag.php')) {
    $src = "public\$s"
    if (Test-Path $src) {
        Copy-Item $src "$staging\public\$s" -Force
        Write-Host "  Incluido $s"
    }
}

$fileCount = (Get-ChildItem $staging -Recurse -File -Force).Count
Write-Host "  Staging listo: $fileCount archivos"

# ─────────────────────────────────────────────────────────────────────────────
# 4. Generar ZIP
# ─────────────────────────────────────────────────────────────────────────────
Write-Step 4 4 "Generando ZIP"
$zipName = 'buscape.zip'
if (Test-Path $zipName) { Remove-Item $zipName -Force }

Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem

$sourceRoot = (Resolve-Path $staging).Path
$zipPath    = Join-Path (Get-Location).Path $zipName

$fs      = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::CreateNew)
$archive = New-Object System.IO.Compression.ZipArchive($fs, [System.IO.Compression.ZipArchiveMode]::Create)
$count   = 0
try {
    Get-ChildItem -Path $sourceRoot -Recurse -Force -File | ForEach-Object {
        $rel   = $_.FullName.Substring($sourceRoot.Length + 1).Replace('\','/')
        $entry = $archive.CreateEntry($rel, [System.IO.Compression.CompressionLevel]::Optimal)
        $dst   = $entry.Open()
        $src   = [System.IO.File]::OpenRead($_.FullName)
        try { $src.CopyTo($dst) }
        finally { $src.Dispose(); $dst.Dispose() }
        $count++
    }
} finally {
    $archive.Dispose()
    $fs.Dispose()
}

Remove-Item $staging -Recurse -Force

# Validar contenido critico
$zip      = [System.IO.Compression.ZipFile]::OpenRead($zipPath)
$mustHave = @(
    '.env',
    '.htaccess',
    'public/.htaccess',
    'public/index.php',
    'vendor/autoload.php',
    'routes/web.php',
    'routes/api.php',
    'artisan'
)
$missing = @()
foreach ($f in $mustHave) {
    if (-not ($zip.Entries | Where-Object FullName -eq $f)) { $missing += $f }
}
$buildCount   = ($zip.Entries | Where-Object FullName -like 'public/build/*').Count
$storageCount = ($zip.Entries | Where-Object FullName -like 'storage/*').Count
$vendorCount  = ($zip.Entries | Where-Object FullName -like 'vendor/*').Count
$size         = (Get-Item $zipPath).Length
$zip.Dispose()

if ($missing.Count -gt 0) {
    Write-Host "  FALTAN archivos criticos:" -ForegroundColor Red
    $missing | ForEach-Object { Write-Host "    - $_" -ForegroundColor Red }
    Remove-Item $zipPath -Force
    exit 1
}

# ─────────────────────────────────────────────────────────────────────────────
# Prueba basica con PHP (verifica que Laravel arranca)
# ─────────────────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "  Verificando que Laravel arranca..." -ForegroundColor Gray

$testDir = "$env:TEMP\buscape_test_$([Guid]::NewGuid().ToString('N'))"
New-Item -ItemType Directory -Path $testDir | Out-Null
try {
    Expand-Archive -Path $zipPath -DestinationPath $testDir -Force

    $testScript = @'
<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

$tests = [
    '/'          => 200,   // SPA raiz
    '/api/v1/categories' => 200,   // API publica
];

$allOk = true;
foreach ($tests as $path => $expected) {
    $req    = \Illuminate\Http\Request::create($path, 'GET');
    $res    = $kernel->handle($req);
    $status = $res->getStatusCode();
    $ok     = $status === $expected;
    $allOk  = $allOk && $ok;
    echo str_pad($path, 30) . ' => HTTP ' . $status . ($ok ? '  OK' : '  FAIL') . PHP_EOL;
    $kernel->terminate($req, $res);
}
exit($allOk ? 0 : 1);
'@
    Set-Content -Path "$testDir\_test.php" -Value $testScript -Encoding ascii

    Push-Location $testDir
    try {
        $prev = $ErrorActionPreference; $ErrorActionPreference = 'Continue'
        $out  = cmd /c "php _test.php 2>&1"
        $ok   = $LASTEXITCODE -eq 0
        $ErrorActionPreference = $prev
        $out | ForEach-Object { Write-Host "    $_" }
        if (-not $ok) { throw "Prueba PHP fallo. Revisa el error de arriba." }
    } finally {
        Pop-Location
    }
} finally {
    Remove-Item $testDir -Recurse -Force -ErrorAction SilentlyContinue
}

# ─────────────────────────────────────────────────────────────────────────────
# Resumen
# ─────────────────────────────────────────────────────────────────────────────
Write-Host ""
Write-Host "══════════════════════════════════════════════════" -ForegroundColor Green
Write-Host "  ZIP listo:  $(Resolve-Path $zipName)" -ForegroundColor Green
Write-Host "  Tamano:     $('{0:N2} MB' -f ($size / 1MB))" -ForegroundColor Green
Write-Host "  Entradas:   $count  (build: $buildCount  storage: $storageCount  vendor: $vendorCount)" -ForegroundColor Green
Write-Host "══════════════════════════════════════════════════" -ForegroundColor Green
Write-Host ""
Write-Host "PASOS PARA SUBIR AL SERVIDOR:" -ForegroundColor Yellow
Write-Host "  1. Sube buscape.zip al servidor via FTP/SFTP" -ForegroundColor White
Write-Host "  2. Descomprime en la carpeta raiz del dominio" -ForegroundColor White
Write-Host "     unzip buscape.zip -d /var/www/tudominio.com" -ForegroundColor Gray
Write-Host "  3. El DocumentRoot de Apache/Nginx debe apuntar a /public" -ForegroundColor White
Write-Host "     DocumentRoot /var/www/tudominio.com/public" -ForegroundColor Gray
Write-Host "  4. Primera vez: php artisan migrate --force" -ForegroundColor White
Write-Host "  5. Primera vez: php artisan storage:link" -ForegroundColor White
Write-Host ""
