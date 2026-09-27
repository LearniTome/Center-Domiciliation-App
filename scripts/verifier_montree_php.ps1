param(
    [string]$PhpBin = "C:\xampp\php\php.exe",
    [string]$MinVersion = "8.3",
    [switch]$SkipTests
)

# ==========================================
#  Verification apres montee de version PHP
#  A executer APRES l'installation de la
#  nouvelle XAMPP, avant de decider de
#  travailler dessus.
#
#  Usage :
#    powershell -ExecutionPolicy Bypass -File .\scripts\verifier_montree_php.ps1
#    powershell -ExecutionPolicy Bypass -File .\scripts\verifier_montee_php.ps1 -PhpBin "C:\xampp83\php\php.exe"
#
#  Sortie : 0 si tout est vert, 1 sinon.
# ==========================================

$ErrorActionPreference = "Stop"
$racine = Split-Path -Parent $PSScriptRoot
$echecs = 0

function Verdict($nom, $ok, $detail) {
    $etat = if ($ok) { "OK  " } else { "ECHEC" }
    if (-not $ok) { $script:echecs++ }
    Write-Host ("[{0}] {1} : {2}" -f $etat, $nom, $detail) -ForegroundColor $(if ($ok) { "Green" } else { "Red" })
}

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Verification montee PHP ($MinVersion+)" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Binaire : $PhpBin" -ForegroundColor Gray
Write-Host "  Projet  : $racine" -ForegroundColor Gray
Write-Host ""

# ---------- 1. Binaire present ----------
if (-not (Test-Path $PhpBin -PathType Leaf)) {
    Write-Host "[ECHEC] php.exe introuvable : $PhpBin" -ForegroundColor Red
    Write-Host "        Specify the path with -PhpBin" -ForegroundColor Red
    exit 1
}

# ---------- 2. Version ----------
$version = (& $PhpBin -r "echo PHP_VERSION;") 2>$null
if ($LASTEXITCODE -ne 0) {
    Verdict "Version PHP" $false "binaire inexecutable"
    exit 1
}
$versionOk = ([version]($version -replace '[^0-9\.].*$', '')) -ge ([version]$MinVersion)
Verdict "Version PHP" $versionOk "$version (minimum $MinVersion)"

$sapi = (& $PhpBin -r "echo PHP_SAPI;") 2>$null
Write-Host "       SAPI : $sapi" -ForegroundColor Gray

# ---------- 3. Extensions critiques ----------
# zip      -> ZipArchive : lecture/ecriture des .docx (templates, generation)
# com_dotnet -> conversion DOCX -> PDF via Word (Windows)
# pdo_mysql  -> base center_domiciliation
# gd / fileinfo -> images de documents, detection de type MIME
$critiques = @{
    "zip"        = "ZipArchive (templates .docx)"
    "pdo_mysql"  = "base MySQL"
    "mbstring"   = "encodage UTF-8"
    "gd"         = "images"
    "fileinfo"   = "type MIME des fichiers"
    "openssl"    = "sessions / HTTPS"
}
foreach ($ext in $critiques.Keys) {
    $chargee = (& $PhpBin -r "echo extension_loaded('$ext') ? '1' : '0';") 2>$null
    Verdict "extension $ext" ($chargee -eq "1") $critiques[$ext]
}

# com_dotnet : pertinent seulement sous Apache/IIS, inutile en CLI
$comCharge = (& $PhpBin -r "echo class_exists('COM') ? '1' : '0';") 2>$null
if ($comCharge -eq "1") {
    Verdict "extension com_dotnet" $true "classe COM disponible (conversion PDF via Word)"
} else {
    Write-Host "[INFO] com_dotnet : non chargee en CLI." -ForegroundColor Yellow
    Write-Host "       Attendu si PHP tourne en CLI. A verifier sous Apache :" -ForegroundColor Yellow
    Write-Host "       phpinfo() > CGI/SAPI = apache2handler, ou la page Tools de l'app." -ForegroundColor Yellow
}

# ---------- 4. Test fonctionnel ZipArchive (aller-retour .docx) ----------
$zipTest = (& $PhpBin -r @'
if (!class_exists('ZipArchive')) { echo 'NO_ZIPARCHIVE'; exit; }
require 'vendor/autoload.php';
$src = null;
foreach (glob('templates/*/*.docx') as $f) { $src = $f; break; }
if ($src === null) { echo 'NO_TEMPLATE'; exit; }
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'verifier_' . getmypid() . '.docx';
copy($src, $tmp);
$zip = new ZipArchive();
if ($zip->open($tmp) !== true) { echo 'OPEN_FAIL'; exit; }
$xml = $zip->getFromName('word/document.xml');
$zip->close();
$ok = is_string($xml) && strlen($xml) > 1000;
@unlink($tmp);
echo $ok ? 'OK ' . basename($src) : 'XML_EMPTY';
'@) 2>&1 | Select-Object -Last 1
if ($zipTest -eq "NO_TEMPLATE") {
    Write-Host "[INFO] Aucun .docx dans templates/ : test ZipArchive ignore." -ForegroundColor Yellow
} elseif ($zipTest -eq "NO_ZIPARCHIVE") {
    Verdict "ZipArchive" $false "classe absente"
} else {
    Verdict "ZipArchive" ($zipTest -like "OK*") $zipTest
}

# ---------- 5. Dependances Composer ----------
$composer = Join-Path $racine "composer.phar"
if (-not (Test-Path $composer)) { $composer = "composer" }
try {
    $reqs = & $composer check-platform-reqs --no-interaction 2>&1 | Out-String
    $reqsOk = $LASTEXITCODE -eq 0
    $manquants = ($reqs -split "`n" | Where-Object { $_ -match 'missing|failed' }) -join ' | '
    Verdict "composer check-platform-reqs" $reqsOk $(if ($reqsOk) { "toutes les extensions requises sont presentes" } else { $manquants })
} catch {
    Write-Host "[INFO] composer indisponible, verification des dependances ignoree." -ForegroundColor Yellow
}

# ---------- 6. Suite PHPUnit ----------
if ($SkipTests) {
    Write-Host "[INFO] Suite PHPUnit ignoree (-SkipTests)." -ForegroundColor Yellow
} else {
    Push-Location $racine
    try {
        $out = & $PhpBin "vendor\bin\phpunit" 2>&1 | Out-String
        $testsOk = $LASTEXITCODE -eq 0
        $resume = ($out -split "`n" | Where-Object { $_ -match '^(OK|Tests:|FAILURES|ERRORS)' }) -join ' | '
        Verdict "Suite PHPUnit" $testsOk $resume.Trim()
        if (-not $testsOk) { Write-Host $out -ForegroundColor DarkGray }
    } finally {
        Pop-Location
    }
}

# ---------- 7. Absence de nouvelles deprecations ----------
Write-Host ""
Write-Host "--- Recherche de deprecations dans les journaux ---" -ForegroundColor Cyan
$journaux = @(
    @{ Nom = "php_error_log"; Chemin = "C:\xampp\php\logs\php_error_log" },
    @{ Nom = "apache error.log"; Chemin = "C:\xampp\apache\logs\error.log" }
)
$trouve = 0
foreach ($j in $journaux) {
    if (-not (Test-Path $j.Chemin)) { continue }
    $lignes = Get-Content $j.Chemin -Tail 2000 -ErrorAction SilentlyContinue
    $dep = @($lignes | Select-String -Pattern "Deprecated:")
    Write-Host ("       {0} : {1} deprecation(s) sur les 2000 dernieres lignes" -f $j.Nom, $dep.Count) -ForegroundColor Gray
    if ($dep.Count -gt 0) {
        $trouve += $dep.Count
        $dep | Select-Object -Last 5 | ForEach-Object { Write-Host ("         " + $_.Line.Trim()) -ForegroundColor DarkYellow }
    }
}
if ($trouve -eq 0) {
    Write-Host "       Aucune deprecation detectee." -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
if ($echecs -eq 0) {
    Write-Host "  RESULTAT : tout est vert." -ForegroundColor Green
    Write-Host "  Rappeler dans TODO.md / docs/ROADMAP.md puis committer." -ForegroundColor Green
} else {
    Write-Host "  RESULTAT : $echecs verification(s) en echec." -ForegroundColor Red
    Write-Host "  Ne pas utiliser cette version : corriger ou revenir a l'ancienne." -ForegroundColor Red
}
Write-Host "========================================" -ForegroundColor Cyan
exit $echecs
