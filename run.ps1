param(
    [string]$XamppPath = ""
)

# ==========================================
#  Center Domiciliation - XAMPP Launcher
#  Portable : auto-détection du chemin
# ==========================================

$ProjectRoot = $PSScriptRoot
$ProjectName = Split-Path -Leaf $ProjectRoot
$url = "http://localhost/$ProjectName/"

# ----- Détection XAMPP -----
if (-not $XamppPath) {
    $candidates = @(
        "C:\xampp",
        "D:\xampp",
        "E:\xampp",
        "$env:ProgramFiles\xampp",
        "${env:ProgramFiles(x86)}\xampp",
        "$env:LOCALAPPDATA\xampp"
    )

    foreach ($candidate in $candidates) {
        if (Test-Path "$candidate\apache\bin\httpd.exe" -PathType Leaf) {
            $XamppPath = $candidate
            break
        }
    }

    # Registry fallback
    if (-not $XamppPath) {
        $reg = Get-ItemProperty "HKLM:\SOFTWARE\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\XAMPP" -Name InstallDir -ErrorAction SilentlyContinue
        if ($reg -and $reg.InstallDir) {
            $XamppPath = $reg.InstallDir
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

# ----- Verification du lien symlink -----
$HtdocsLink = Join-Path (Join-Path $XamppPath "htdocs") $ProjectName
if (-not (Test-Path $HtdocsLink)) {
    Write-Host "[ATTENTION] Lien symbolique manquant dans htdocs" -ForegroundColor Yellow
    Write-Host "  Lance .\setup.ps1 pour recreer le lien" -ForegroundColor Gray
    Write-Host ""
}

# ----- Verification convertisseur DOCX->PDF -----
if (-not (Test-Path (Join-Path $ProjectRoot "vendor\autoload.php") -PathType Leaf)) {
    Write-Host "[ATTENTION] Dependances Composer manquantes (phpword/dompdf)" -ForegroundColor Yellow
    Write-Host "  Lance : cd $ProjectRoot && composer install" -ForegroundColor Gray
    Write-Host "  Ou : .\setup.ps1 pour tout configurer automatiquement" -ForegroundColor Gray
    Write-Host ""
}
$PhpIni = Join-Path $XamppPath "php\php.ini"
if (Test-Path $PhpIni -PathType Leaf) {
    $iniContent = Get-Content $PhpIni -Raw
    if ($iniContent -match ";extension=php_com_dotnet\.dll" -or $iniContent -notmatch "extension=php_com_dotnet\.dll") {
        Write-Host "[ATTENTION] extension=php_com_dotnet.dll desactivee" -ForegroundColor Yellow
        Write-Host "  Lance .\setup.ps1 pour l'activer automatiquement" -ForegroundColor Gray
        Write-Host ""
    }
}

# Detection LibreOffice
$librePaths = @(
    "${env:ProgramFiles}\LibreOffice\program\soffice.exe",
    "${env:ProgramFiles(x86)}\LibreOffice\program\soffice.exe"
)
$libreFound = $false
foreach ($lp in $librePaths) {
    if (Test-Path $lp -PathType Leaf) { $libreFound = $true; break }
}
if (-not $libreFound) {
    Write-Host "[INFO] LibreOffice non installe. Conversion PDF via fallback PHPWord/Dompdf." -ForegroundColor Gray
    Write-Host ""
}

Write-Host ""

# MySQL
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqlRunning) {
    Write-Host "[MySQL] Demarrage..." -ForegroundColor Yellow
    Start-Process -FilePath $MysqlBin -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Write-Host "[MySQL] Ok" -ForegroundColor Green
} else {
    Write-Host "[MySQL] Deja en cours" -ForegroundColor Green
}

# Apache
$apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if (-not $apacheRunning) {
    Write-Host "[Apache] Demarrage..." -ForegroundColor Yellow
    Start-Process -FilePath $ApacheBin -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Write-Host "[Apache] Ok" -ForegroundColor Green
} else {
    Write-Host "[Apache] Deja en cours" -ForegroundColor Green
}

Write-Host ""
Write-Host "Application ouverte : $url" -ForegroundColor Green
try {
    Start-Process $url
} catch {
    Write-Host "Ouvre ce lien : $url" -ForegroundColor White
}

Write-Host ""
Write-Host "Les services XAMPP tournent en arriere-plan." -ForegroundColor Cyan
Write-Host "Ferme cette fenetre sans souci, ils restent actifs." -ForegroundColor Cyan
Write-Host ""
Write-Host "Pour les arreter :" -ForegroundColor Yellow
Write-Host "  taskkill /f /im httpd.exe" -ForegroundColor White
Write-Host "  taskkill /f /im mysqld.exe" -ForegroundColor White
Write-Host ""
pause
