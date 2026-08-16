param(
    [string]$XamppPath = ""
)

# ==========================================
#  Center Domiciliation - XAMPP Launcher
# ==========================================

$ProjectRoot = $PSScriptRoot
$ProjectName = Split-Path -Leaf $ProjectRoot
$url = "http://localhost/$ProjectName/?autologin=1"

# ----- Detection XAMPP -----
if (-not $XamppPath) {
    $candidates = @("C:\xampp", "D:\xampp", "E:\xampp")
    foreach ($candidate in $candidates) {
        if (Test-Path "$candidate\apache\bin\httpd.exe" -PathType Leaf) {
            $XamppPath = $candidate
            break
        }
    }
}

if (-not $XamppPath) {
    Write-Host "[ERREUR] XAMPP introuvable." -ForegroundColor Red
    Write-Host "Relance avec : .\run.ps1 -XamppPath ""C:\chemin\vers\xampp""" -ForegroundColor Yellow
    pause
    exit 1
}

$ApacheBin = Join-Path $XamppPath "apache\bin\httpd.exe"
$MysqlBin  = Join-Path $XamppPath "mysql\bin\mysqld.exe"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Center Domiciliation - XAMPP Launcher" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  XAMPP : $XamppPath" -ForegroundColor Gray
Write-Host "  App   : $ProjectRoot" -ForegroundColor Gray
Write-Host ""

# ----- Symlink htdocs -----
$HtdocsLink = Join-Path (Join-Path $XamppPath "htdocs") $ProjectName
if (-not (Test-Path $HtdocsLink)) {
    Write-Host "[htdocs] Creation du lien symbolique..." -ForegroundColor Yellow
    $Htdocs = Join-Path $XamppPath "htdocs"
    if (-not (Test-Path $Htdocs -PathType Container)) {
        New-Item -ItemType Directory -Path $Htdocs -Force | Out-Null
    }
    try {
        $projectDrive = (Get-Item $ProjectRoot).PSDrive.Name
        $xamppDrive   = (Get-Item $XamppPath).PSDrive.Name
        if ($projectDrive -eq $xamppDrive) {
            New-Item -ItemType Junction -Path $HtdocsLink -Target $ProjectRoot -Force | Out-Null
        } else {
            New-Item -ItemType SymbolicLink -Path $HtdocsLink -Target $ProjectRoot -Force | Out-Null
        }
        Write-Host "      $HtdocsLink -> $ProjectRoot" -ForegroundColor Green
    } catch {
        Write-Host "[ATTENTION] Impossible de creer le lien. Lance .\scripts\setup.ps1 en Admin." -ForegroundColor Yellow
    }
    Write-Host ""
}

# ----- Composer -----
if (-not (Test-Path (Join-Path $ProjectRoot "vendor\autoload.php") -PathType Leaf)) {
    Write-Host "[Composer] Installation des dependances..." -ForegroundColor Yellow
    $composer = Get-Command "composer" -ErrorAction SilentlyContinue
    if ($composer) {
        & composer install --no-interaction --working-dir=$ProjectRoot 2>&1 | Out-Null
        Write-Host "      Dependances installees" -ForegroundColor Green
    } else {
        Write-Host "      Composer introuvable, ignore" -ForegroundColor Yellow
    }
    Write-Host ""
}

# ----- MySQL -----
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqlRunning) {
    Write-Host "[MySQL] Demarrage..." -ForegroundColor Yellow
    Start-Process -FilePath $MysqlBin -ArgumentList "--console" -WindowStyle Hidden
    Start-Sleep -Seconds 5
    $mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
    if ($mysqlRunning) {
        Write-Host "[MySQL] Ok" -ForegroundColor Green
    } else {
        Write-Host "[MySQL] Echec demarrage. Verifie C:\xampp\mysql\data\*.err" -ForegroundColor Red
    }
} else {
    Write-Host "[MySQL] Deja en cours" -ForegroundColor Green
}

# ----- Apache -----
$apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if (-not $apacheRunning) {
    Write-Host "[Apache] Demarrage..." -ForegroundColor Yellow
    # Mode console direct (sans -k run) : fonctionne meme sans service Apache2.4 installe
    Start-Process -FilePath $ApacheBin -WindowStyle Hidden
    Start-Sleep -Seconds 3
    $apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
    if ($apacheRunning) {
        Write-Host "[Apache] Ok" -ForegroundColor Green
    } else {
        Write-Host "[Apache] Echec demarrage. Verifie C:\xampp\apache\logs\error.log" -ForegroundColor Red
    }
} else {
    Write-Host "[Apache] Deja en cours" -ForegroundColor Green
}

Write-Host ""
Write-Host "Application : $url" -ForegroundColor Green

# ----- Ouverture Chrome -----
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

Write-Host ""
Write-Host "Les services tournent en arriere-plan." -ForegroundColor Cyan
Write-Host "Ferme cette fenetre sans souci." -ForegroundColor Cyan
Write-Host ""
