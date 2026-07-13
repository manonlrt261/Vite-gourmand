$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $projectRoot

$phpPath = "C:\laragon\bin\php\php-8.4.12-nts-Win32-vs17-x64\php.exe"
if (-not (Test-Path $phpPath)) {
    $phpPath = "php"
}

try {
    & $phpPath -v | Out-Null
} catch {
    Write-Host "ERREUR : PHP est introuvable. Ouvre Laragon puis relance ce fichier." -ForegroundColor Red
    exit 1
}

$headerPath = Join-Path $projectRoot "templates\partials\_header.html.twig"
if (-not (Test-Path $headerPath)) {
    Write-Host "ERREUR : le fichier templates\partials\_header.html.twig est introuvable." -ForegroundColor Red
    exit 1
}

$cachePath = Join-Path $projectRoot "var\cache\dev"
$resolvedProject = (Resolve-Path $projectRoot).Path
if (Test-Path $cachePath) {
    $resolvedCache = (Resolve-Path $cachePath).Path
    if ($resolvedCache.StartsWith($resolvedProject)) {
        Write-Host "Nettoyage du cache Symfony..." -ForegroundColor Yellow
        Remove-Item -LiteralPath $resolvedCache -Recurse -Force
    }
}

Write-Host "Liberation du port 8005 si un ancien serveur est lance..." -ForegroundColor Yellow
$phpServers = Get-CimInstance Win32_Process |
    Where-Object {
        $_.Name -like "php*" -and
        ($_.CommandLine -like "*127.0.0.1:8005*" -or $_.CommandLine -like "*public/router.php*")
    }

foreach ($server in $phpServers) {
    Stop-Process -Id $server.ProcessId -Force -ErrorAction SilentlyContinue
}

Write-Host ""
Write-Host "Site lance sur : http://127.0.0.1:8005" -ForegroundColor Green
Write-Host "Pour arreter le site : CTRL + C dans ce terminal." -ForegroundColor Cyan
Write-Host ""

& $phpPath -S 127.0.0.1:8005 -t public public/router.php
