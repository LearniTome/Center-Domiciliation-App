param(
    [string]$Project = "",
    [int]$Port = 8000,
    [string]$XamppPath = "",
    [switch]$NoBrowser
)

# ==========================================
#  Dev Server multi-projets (PHP intégré)
#  Chaque projet sur son propre port, sans
#  toucher à la config Apache/XAMPP.
# ==========================================

# ----- Détection du projet -----
if (-not $Project) {
    $Project = Get-Location
} elseif (-not (Test-Path $Project -PathType Container)) {
    Write-Host "[ERREUR] Projet introuvable : $Project" -ForegroundColor Red
    exit 1
}
$Project = (Resolve-Path $Project).Path

# ----- Détection XAMPP -----
if (-not $XamppPath) {
    $candidates = @("C:\xampp", "D:\xampp", "E:\xampp")
    foreach ($candidate in $candidates) {
        if (Test-Path "$candidate\php\php.exe" -PathType Leaf) {
            $XamppPath = $candidate
            break
        }
    }
}

$PhpBin = Join-Path $XamppPath "php\php.exe"
if (-not (Test-Path $PhpBin -PathType Leaf)) {
    Write-Host "[ERREUR] php.exe introuvable. Lance avec : -XamppPath C:\chemin\xampp" -ForegroundColor Red
    exit 1
}

# ----- Document root (dossier public/ si présent) -----
$DocRoot = $Project
if (Test-Path (Join-Path $Project "public\index.php") -PathType Leaf) {
    $DocRoot = Join-Path $Project "public"
}

# ----- Port libre -----
$taken = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
while ($taken) {
    Write-Host "[PORT] $Port occupé, essai du port suivant..." -ForegroundColor Yellow
    $Port++
    $taken = Get-NetTCPConnection -LocalPort $Port -State Listen -ErrorAction SilentlyContinue
}

$url = "http://localhost:$Port/"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Dev Server - PHP intégré" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  PHP    : $PhpBin" -ForegroundColor Gray
Write-Host "  Projet : $Project" -ForegroundColor Gray
Write-Host "  Racine : $DocRoot" -ForegroundColor Gray
Write-Host "  URL    : $url" -ForegroundColor Green
Write-Host "  (Ctrl+C pour arrêter)" -ForegroundColor Cyan
Write-Host ""

# ----- Ouverture navigateur -----
if (-not $NoBrowser) {
    $chromePaths = @(
        "${env:ProgramFiles}\Google\Chrome\Application\chrome.exe",
        "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
        "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe"
    )
    $chromePath = $chromePaths | Where-Object { Test-Path $_ } | Select-Object -First 1
    if ($chromePath) {
        Start-Process -FilePath $chromePath -ArgumentList $url
    } else {
        Start-Process $url
    }
}

# ----- Lancement serveur (au premier plan) -----
& $PhpBin -S "localhost:$Port" -t $DocRoot
