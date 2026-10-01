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
$prevus = 0

function Verdict($nom, $ok, $detail) {
    $etat = if ($ok) { "OK  " } else { "ECHEC" }
    if (-not $ok) { $script:echecs++ }
    Write-Host ("[{0}] {1} : {2}" -f $etat, $nom, $detail) -ForegroundColor $(if ($ok) { "Green" } else { "Red" })
}

# Un controle non execute n'est PAS un controle reussi. Il est compte a part
# et remonte dans le verdict final : sans cela, un rapport "tout est vert" peut
# masquer une verification qui n'a jamais tourne.
function Prevus($nom, $raison) {
    $script:prevus++
    Write-Host ("[SAUT] {0} : {1}" -f $nom, $raison) -ForegroundColor Yellow
}

# Les journaux doivent suivre le binaire teste, pas un chemin code en dur :
# avec -PhpBin C:\xampp83\php\php.exe on lirait sinon les journaux de l'ancienne
# installation et on validerait la mauvaise version.
$phpRacine = Split-Path -Parent (Split-Path -Parent $PhpBin)

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
# Le binaire est cherche explicitement. "Non trouve" doit produire un SAUT
# visible, jamais un vert silencieux : c'est le controle qui garantit que les
# extensions exigees par composer.json (vendor/) sont bien presentes.
$candidats = @(
    @{ Fichier = (Join-Path $racine "composer.phar"); Via = "php" },
    @{ Fichier = (Join-Path $racine "composer.bat"); Via = "direct" }
)
$composerCmd = $null
$composerArgs = @()
$composerSource = $null
foreach ($c in $candidats) {
    if (Test-Path $c.Fichier -PathType Leaf) {
        if ($c.Via -eq "php") {
            # Un .phar n'est pas executable par le shell : il passe par le
            # binaire PHP que l'on est en train de valider.
            $composerCmd = $PhpBin
            $composerArgs = @($c.Fichier)
        } else {
            $composerCmd = $c.Fichier
            $composerArgs = @()
        }
        $composerSource = $c.Fichier
        break
    }
}
if (-not $composerCmd) {
    $dansPath = Get-Command composer -ErrorAction SilentlyContinue
    if ($dansPath) {
        $composerCmd = $dansPath.Source
        $composerSource = $dansPath.Source
    }
}

Push-Location $racine
# composer ecrit des avertissements sur stderr ; avec ErrorActionPreference=Stop
# Windows PowerShell transforme cela en exception et le controle bascule a tort
# en "NON EXECUTE". On assouplit le temps de l'appel natif, puis on juge sur le
# code de sortie reel.
$ErrorActionPreference = "Continue"
try {
    if (-not $composerCmd) {
        Prevus "composer check-platform-reqs" "aucun binaire composer trouve (ni composer.phar, ni composer.bat, ni composer dans le PATH). Control NON EXECUTE."
    } else {
        $reqs = & $composerCmd @composerArgs check-platform-reqs --no-interaction 2>&1 | Out-String
        $reqsOk = ($LASTEXITCODE -eq 0)
        $manquants = ($reqs -split "`n" | Where-Object { $_ -match 'missing|failed' -and $_ -notmatch 'success' }) -join ' | '
        Verdict "composer check-platform-reqs" $reqsOk $(if ($reqsOk) { "toutes les extensions requises sont presentes ($composerSource)" } else { $manquants })
    }
} catch {
    Prevus "composer check-platform-reqs" "erreur d'execution : $($_.Exception.Message). Control NON EXECUTE."
} finally {
    Pop-Location
    $ErrorActionPreference = "Stop"
}

# ---------- 6. Suite PHPUnit ----------
# La sortie est conservee : c'est la source de deprecations la plus fiable,
# presente quel que soit le php.ini (voir section 7).
$sortiePhpunit = $null
if ($SkipTests) {
    Write-Host "[INFO] Suite PHPUnit ignoree (-SkipTests)." -ForegroundColor Yellow
} else {
    Push-Location $racine
    try {
        $sortiePhpunit = & $PhpBin "-d" "error_reporting=E_ALL" "-d" "display_errors=1" "vendor\bin\phpunit" 2>&1 | Out-String
        $testsOk = $LASTEXITCODE -eq 0
        $resume = ($sortiePhpunit -split "`n" | Where-Object { $_ -match '^(OK|Tests:|FAILURES|ERRORS)' }) -join ' | '
        Verdict "Suite PHPUnit" $testsOk $resume.Trim()
        if (-not $testsOk) { Write-Host $sortiePhpunit -ForegroundColor DarkGray }
    } finally {
        Pop-Location
    }
}

# ---------- 7. Absence de nouvelles deprecations ----------
# Strategie : la source principale est la sortie de PHPUnit (toujours disponible,
# independante du php.ini). Les journaux sur disque ne servent qu'a corroborer,
# et leur absence ne doit pas faire échouer la verification puisque la sortie de
# PHPUnit couvre deja le code applicatif. Chemins derives du binaire teste.
Write-Host ""
Write-Host "--- Recherche de deprecations ---" -ForegroundColor Cyan

$depPrincipales = @()
if ($null -ne $sortiePhpunit) {
    $depPrincipales = @($sortiePhpunit -split "`n" | Where-Object { $_ -match 'Deprecated:' })
    Write-Host ("       sortie PHPUnit : {0} deprecation(s)" -f $depPrincipales.Count) -ForegroundColor Gray
    $depPrincipales | Select-Object -Last 5 | ForEach-Object { Write-Host ("         " + $_.Trim()) -ForegroundColor DarkYellow }
} else {
    Prevus "deprecations (sortie PHPUnit)" "suite ignoree (-SkipTests) : aucune capture de sortie a analyser"
}

$racinePhp = $phpRacine
$journaux = @(
    @{ Nom = "php_error_log"; Chemin = (Join-Path $racinePhp "php\logs\php_error_log") },
    @{ Nom = "apache error.log"; Chemin = (Join-Path $racinePhp "apache\logs\error.log") }
)
Write-Host "       (journaux de $racinePhp, corroboration)" -ForegroundColor Gray
$depJournaux = 0
foreach ($j in $journaux) {
    if (-not (Test-Path $j.Chemin -PathType Leaf)) {
        Write-Host ("       {0} : absent, non analyse" -f $j.Nom) -ForegroundColor DarkGray
        continue
    }
    $lignes = @(Get-Content $j.Chemin -Tail 2000 -ErrorAction SilentlyContinue)
    if ($lignes.Count -eq 0) {
        Write-Host ("       {0} : vide, non analyse" -f $j.Nom) -ForegroundColor DarkGray
        continue
    }
    $dep = @($lignes | Select-String -Pattern "Deprecated:")
    $depJournaux += $dep.Count
    Write-Host ("       {0} : {1} deprecation(s) sur {2} lignes" -f $j.Nom, $dep.Count, $lignes.Count) -ForegroundColor Gray
    $dep | Select-Object -Last 3 | ForEach-Object { Write-Host ("         " + $_.Line.Trim()) -ForegroundColor DarkYellow }
}

# Une deprecation remontee par la sortie de PHPUnit vient forcement du couple
# (code, version) que l'on valide : elle bloque. Une deprecation lue dans un
# journal peut dater de l'installation precedente : elle alerte sans bloquer,
# sinon les journaux de l'ancienne version condamneraient la nouvelle.
if ($depPrincipales.Count -gt 0) {
    Verdict "Deprecations" $false "$($depPrincipales.Count) deprecation(s) dans la sortie PHPUnit - a corriger avant migration"
} else {
    Write-Host "       Aucune deprecation dans la sortie PHPUnit." -ForegroundColor Green
}
if ($depJournaux -gt 0) {
    Write-Host ("       ATTENTION : $depJournaux deprecation(s) dans les journaux (a confirmer sur leur date).") -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
if ($echecs -gt 0) {
    Write-Host "  RESULTAT : $echecs verification(s) en echec." -ForegroundColor Red
    Write-Host "  Ne pas utiliser cette version : corriger ou revenir a l'ancienne." -ForegroundColor Red
    $code = 1
} elseif ($prevus -gt 0) {
    Write-Host "  RESULTAT : aucun echec, mais $prevus verification(s) NON EXECUTEES." -ForegroundColor Yellow
    Write-Host "  Le controle est incomplet : traiter les [SAUT] ci-dessus avant" -ForegroundColor Yellow
    Write-Host "  de considerer cette version comme validee." -ForegroundColor Yellow
    $code = 2
} else {
    Write-Host "  RESULTAT : tout est vert, aucun controle ignore." -ForegroundColor Green
    Write-Host "  Rappeler dans docs/ROADMAP.md puis committer." -ForegroundColor Green
    $code = 0
}
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Code de sortie : $code" -ForegroundColor Gray
exit $code
