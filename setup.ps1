param(
    [string]$XamppPath = "",
    [switch]$NoImport = $false
)

# ==========================================
#  Center Domiciliation - Setup Automatique
#  Portable : fonctionne depuis n'importe
#  quel dossier, n'importe quel PC
# ==========================================

$ErrorActionPreference = "Continue"

# ----- 1. Chemins dynamiques -----
$ProjectRoot = $PSScriptRoot  # Le dossier où se trouve setup.ps1
$ProjectName = Split-Path -Leaf $ProjectRoot
$HtdocsLink  = ""  # sera défini après détection XAMPP

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Center Domiciliation - Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[1/9] Projet detecte : $ProjectRoot" -ForegroundColor Gray

# Verifier PHP dans PATH
$phpCheck = Get-Command "php" -ErrorAction SilentlyContinue
if (-not $phpCheck) {
    Write-Host "      [AVERTISSEMENT] PHP introuvable dans PATH" -ForegroundColor Yellow
    Write-Host "      Ajoute $XamppPath\php au PATH systeme, ou utilise le PHP de XAMPP" -ForegroundColor Gray
}

# Verifier que le projet et XAMPP sont sur le meme disque (pour le symlink)
$projectDrive = (Get-Item $ProjectRoot).PSDrive.Name
$xamppDrive   = (Get-Item $XamppPath).PSDrive.Name
if ($projectDrive -ne $xamppDrive) {
    Write-Host "      [INFO] Projet et XAMPP sont sur des disques differents ($projectDrive vs $xamppDrive)" -ForegroundColor Yellow
    Write-Host "      Les jonctions Windows ne fonctionnent pas entre disques." -ForegroundColor Yellow
    Write-Host "      Utilisation d'un symlink de dossier a la place..." -ForegroundColor Gray
}

# ----- 2. Détection XAMPP -----
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
    Write-Host "Installe XAMPP depuis https://www.apachefriends.org/" -ForegroundColor Yellow
    Write-Host "Ou relance avec : .\setup.ps1 -XamppPath ""C:\chemin\vers\xampp""" -ForegroundColor Yellow
    exit 1
}

Write-Host "[2/9] XAMPP detecte : $XamppPath" -ForegroundColor Green

$ApacheBin  = Join-Path $XamppPath "apache\bin\httpd.exe"
$MysqlBin   = Join-Path $XamppPath "mysql\bin\mysqld.exe"
$MysqlCli   = Join-Path $XamppPath "mysql\bin\mysql.exe"
$Htdocs     = Join-Path $XamppPath "htdocs"
$HtdocsBak  = Join-Path $XamppPath "htdocs_backup"
$HtdocsLink = Join-Path $Htdocs $ProjectName

# ----- 3. Création du dossier htdocs si manquant -----
if (-not (Test-Path $Htdocs -PathType Container)) {
    Write-Host "[3/9] Creation de htdocs..." -ForegroundColor Yellow
    if (Test-Path $HtdocsBak -PathType Container) {
        Rename-Item -Path $HtdocsBak -NewName "htdocs"
        Write-Host "      htdocs_backup -> htdocs" -ForegroundColor Green
    } else {
        New-Item -ItemType Directory -Path $Htdocs -Force | Out-Null
        Write-Host "      htdocs cree" -ForegroundColor Green
    }
} else {
    Write-Host "[3/9] htdocs existant" -ForegroundColor Green
}

# ----- 4. Création du lien symbolique -----
$linkExists = $false
if (Test-Path $HtdocsLink) {
    $linkTarget = (Get-Item $HtdocsLink).Target
    if ($linkTarget -and (Resolve-Path $linkTarget -ErrorAction SilentlyContinue).Path -eq $ProjectRoot) {
        $linkExists = $true
        Write-Host "[4/9] Lien deja actif : $ProjectName -> $ProjectRoot" -ForegroundColor Green
    } else {
        Write-Host "[4/9] Re-creation du lien..." -ForegroundColor Yellow
        Remove-Item -Path $HtdocsLink -Recurse -Force -ErrorAction SilentlyContinue
    }
}

if (-not $linkExists) {
    Write-Host "[4/9] Creation du lien symbolique..." -ForegroundColor Yellow
    try {
        # Utiliser Junction si meme disque, sinon Directory symlink
        $projectDrive = (Get-Item $ProjectRoot).PSDrive.Name
        $xamppDrive   = (Get-Item $XamppPath).PSDrive.Name
        if ($projectDrive -eq $xamppDrive) {
            New-Item -ItemType Junction -Path $HtdocsLink -Target $ProjectRoot -Force | Out-Null
        } else {
            # Symlink de dossier (necessite Admin ou mode Developpeur)
            New-Item -ItemType SymbolicLink -Path $HtdocsLink -Target $ProjectRoot -Force | Out-Null
        }
        Write-Host "      $HtdocsLink" -ForegroundColor Green
        Write-Host "           -> $ProjectRoot" -ForegroundColor Green
    } catch {
        Write-Host "[ERREUR] Impossible de creer le lien." -ForegroundColor Red
        Write-Host "Essaie en mode Administrateur, ou active le mode Developpeur dans Windows." -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Alternative : copie le dossier dans $Htdocs" -ForegroundColor Yellow
        Write-Host "  Copy-Item -Recurse '$ProjectRoot' '$Htdocs'" -ForegroundColor White
        exit 1
    }
}

    # ----- 5. Base de donnees -----
    if (-not $NoImport) {
        Write-Host "[5/9] Import de la base de donnees..." -ForegroundColor Yellow

        # Vérifier si MySQL est en cours
        $mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
        if (-not $mysqlRunning) {
            Write-Host "      Demarrage de MySQL..." -ForegroundColor Gray
            Start-Process -FilePath $MysqlBin -WindowStyle Hidden
            Start-Sleep -Seconds 4
            # Verifier que MySQL a bien demarre
            $retry = 0
            while ($retry -lt 5) {
                $test = & $MysqlCli -u root -e "SELECT 1" 2>&1
                if ($LASTEXITCODE -eq 0) { break }
                $retry++
                Start-Sleep -Seconds 2
            }
            if ($LASTEXITCODE -ne 0) {
                Write-Host "[ERREUR] MySQL n'a pas demarre. Verifie $XamppPath\mysql\data\*.err" -ForegroundColor Red
                exit 1
            }
        }

        $SchemaFile  = Join-Path $ProjectRoot "database\schema.sql"
        $SeedFile    = Join-Path $ProjectRoot "database\seed.sql"
        $ImportFile  = Join-Path $ProjectRoot "database\import.sql"

        function Import-SqlFile($file) {
            if (-not (Test-Path $file -PathType Leaf)) { return $false }
            $output = Get-Content $file -Raw | & $MysqlCli -u root --default-character-set=utf8mb4 2>&1
            if ($LASTEXITCODE -eq 0) { return $true }
            Write-Host "      Erreur SQL : $output" -ForegroundColor Red
            return $false
        }

        if (Import-SqlFile $ImportFile) {
            Write-Host "      Base importee avec succes" -ForegroundColor Green
        } elseif (Test-Path $ImportFile) {
            Write-Host "[ERREUR] Echec import (verifie que MySQL est accessible)" -ForegroundColor Red
        } else {
            $schemaOk = Import-SqlFile $SchemaFile
            $seedOk   = Import-SqlFile $SeedFile
            if ($schemaOk) { Write-Host "      Schema OK" -ForegroundColor Green }
            else           { Write-Host "[ERREUR] Echec schema" -ForegroundColor Red }
            if ($seedOk)   { Write-Host "      Seed OK" -ForegroundColor Green }
            else           { Write-Host "[ERREUR] Echec seed" -ForegroundColor Red }
        }

        # Migrations
        $migrations = @(
            "migration_add_tribunal_type.sql",
            "migration_rename_columns.sql",
            "migration_rbac.sql"
        )
        foreach ($m in $migrations) {
            $mPath = Join-Path $ProjectRoot "database\$m"
            if (Test-Path $mPath -PathType Leaf) {
                Write-Host "      Migration : $m..." -ForegroundColor Gray
                if (Import-SqlFile $mPath) {
                    Write-Host "        OK" -ForegroundColor Green
                } else {
                    Write-Host "        Ignoree (deja appliquee?)" -ForegroundColor Gray
                }
            }
        }
    } else {
        Write-Host "[5/9] Import ignore (-NoImport)" -ForegroundColor Gray
    }

# ----- 6. Creation dossiers utiles -----
Write-Host "[6/9] Verification des dossiers..." -ForegroundColor Yellow
$dirs = @("uploads", "output", "dossiers_dom", "templates")
foreach ($d in $dirs) {
    $p = Join-Path $ProjectRoot $d
    if (-not (Test-Path $p -PathType Container)) {
        New-Item -ItemType Directory -Path $p -Force | Out-Null
        Write-Host "      $d/ cree" -ForegroundColor Green
    }
}
Write-Host "      OK" -ForegroundColor Green

# ----- 7. Convertisseur DOCX -> PDF -----
Write-Host "[7/9] Configuration du convertisseur DOCX->PDF..." -ForegroundColor Yellow

# Composer install
$ComposerJson = Join-Path $ProjectRoot "composer.json"
if (Test-Path $ComposerJson -PathType Leaf) {
    $VendorDir = Join-Path $ProjectRoot "vendor"
    if (-not (Test-Path $VendorDir -PathType Container)) {
        Write-Host "      Installation des dependances Composer..." -ForegroundColor Yellow
        try {
            $composer = Get-Command "composer" -ErrorAction SilentlyContinue
            if (-not $composer) {
                # Fallback : composer.phar local
                $composerPhar = Join-Path $ProjectRoot "composer.phar"
                if (-not (Test-Path $composerPhar)) {
                    Write-Host "      Telechargement de Composer..." -ForegroundColor Gray
                    Invoke-WebRequest -Uri "https://getcomposer.org/composer.phar" -OutFile $composerPhar -UseBasicParsing
                }
                & php $composerPhar install --no-interaction --working-dir=$ProjectRoot 2>&1 | Out-Null
            } else {
                & composer install --no-interaction --working-dir=$ProjectRoot 2>&1 | Out-Null
            }
            Write-Host "      Dependances installees (phpword + dompdf)" -ForegroundColor Green
        } catch {
            Write-Host "      [AVERTISSEMENT] Echec composer install" -ForegroundColor Yellow
            Write-Host "      Execute manuellement : cd $ProjectRoot && composer install" -ForegroundColor Gray
        }
    } else {
        Write-Host "      Dependances deja presentes" -ForegroundColor Green
    }
} else {
    Write-Host "      Pas de composer.json trouve" -ForegroundColor Gray
}

# php.ini : activer automatiquement les extensions COM et zip
$PhpIni = Join-Path $XamppPath "php\php.ini"
if (Test-Path $PhpIni -PathType Leaf) {
    $iniContent = Get-Content $PhpIni -Raw
    $modified = $false

    # Activer extension=zip si desactive
    if ($iniContent -match ";extension=zip") {
        $iniContent = $iniContent -replace ";extension=zip", "extension=zip"
        $modified = $true
        Write-Host "      extension=zip activee" -ForegroundColor Green
    } elseif ($iniContent -notmatch "extension=zip") {
        # Ajouter a la fin si absent
        $iniContent += "`r`nextension=zip"
        $modified = $true
        Write-Host "      extension=zip ajoutee" -ForegroundColor Green
    }

    # Activer extension=php_com_dotnet.dll si desactive
    if ($iniContent -match ";extension=php_com_dotnet\.dll") {
        $iniContent = $iniContent -replace ";extension=php_com_dotnet\.dll", "extension=php_com_dotnet.dll"
        $modified = $true
        Write-Host "      extension=php_com_dotnet.dll activee" -ForegroundColor Green
    } elseif ($iniContent -notmatch "extension=php_com_dotnet\.dll") {
        $iniContent += "`r`nextension=php_com_dotnet.dll"
        $modified = $true
        Write-Host "      extension=php_com_dotnet.dll ajoutee" -ForegroundColor Green
    }

    # Verifier aussi extension=php_zip.dll
    if ($iniContent -match ";extension=php_zip\.dll") {
        $iniContent = $iniContent -replace ";extension=php_zip\.dll", "extension=php_zip.dll"
        $modified = $true
        Write-Host "      extension=php_zip.dll activee" -ForegroundColor Green
    }

    if ($modified) {
        Set-Content -Path $PhpIni -Value $iniContent -Encoding Default
        Write-Host "      php.ini modifie" -ForegroundColor Yellow
    } else {
        Write-Host "      Extensions PHP OK (com_dotnet + zip)" -ForegroundColor Green
    }
} else {
    Write-Host "      php.ini introuvable (pas bloque)" -ForegroundColor Gray
}

# Detection LibreOffice
$libreofficePaths = @(
    "${env:ProgramFiles}\LibreOffice\program\soffice.exe",
    "${env:ProgramFiles(x86)}\LibreOffice\program\soffice.exe",
    "$env:LOCALAPPDATA\Programs\LibreOffice\program\soffice.exe"
)
$libreFound = $false
foreach ($lo in $libreofficePaths) {
    if (Test-Path $lo -PathType Leaf) {
        Write-Host "      LibreOffice detecte : $lo" -ForegroundColor Green
        $libreFound = $true
        break
    }
}
if (-not $libreFound) {
    $hasChoco = Get-Command "choco" -ErrorAction SilentlyContinue
    if ($hasChoco) {
        Write-Host "      [SUGGESTION] LibreOffice peut etre installe via :" -ForegroundColor Gray
        Write-Host "        choco install libreoffice" -ForegroundColor White
    } else {
        Write-Host "      [INFO] LibreOffice non trouve. Conversion PDF via PHPWord/Dompdf (fallback)." -ForegroundColor Gray
        Write-Host "      Pour un meilleur rendu PDF, installe LibreOffice :" -ForegroundColor Gray
        Write-Host "        https://www.libreoffice.org/download/" -ForegroundColor White
    }
}

# ----- 8. MCP Servers (OpenCode) -----
Write-Host "[8/9] Preparation des serveurs MCP..." -ForegroundColor Yellow

# Vérifier Node.js
$nodeCheck = Get-Command "node" -ErrorAction SilentlyContinue
if ($nodeCheck) {
    $nodeVersion = & node --version
    Write-Host "      Node.js detecte : $nodeVersion" -ForegroundColor Green

    # Pré-cache des packages MCP
    $NeedPrecache = $false
    $NpxCache = Join-Path $env:LOCALAPPDATA "npm-cache" -ErrorAction SilentlyContinue
    if (-not (Test-Path $NpxCache)) { $NeedPrecache = $true }

    if ($NeedPrecache) {
        Write-Host "      Pre-cache des packages MCP..." -ForegroundColor Yellow
        try {
            # Lancement silencieux pour forcer le téléchargement npm
            $null = & npx -y @modelcontextprotocol/server-memory --version 2>&1
            $null = & npx -y @berthojoris/mcp-mysql-server "mysql://root@127.0.0.1:3306/center_domiciliation" "list,read" --version 2>&1
            Write-Host "      Packages MCP pre-caches" -ForegroundColor Green
        } catch {
            Write-Host "      [INFO] Pre-cache non critique (les packages seront telecharges au 1er lancement)" -ForegroundColor Gray
        }
    } else {
        Write-Host "      Cache npm deja present" -ForegroundColor Green
    }
} else {
    Write-Host "      [AVERTISSEMENT] Node.js non trouve" -ForegroundColor Yellow
    Write-Host "      Installe Node.js >= 18 depuis https://nodejs.org/" -ForegroundColor Gray
    Write-Host "      Necessaire pour les serveurs MCP d'OpenCode" -ForegroundColor Gray
}

# ----- 9. Demarrage Apache + MySQL -----
Write-Host "[9/9] Demarrage des services..." -ForegroundColor Yellow

# MySQL
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqlRunning) {
    Start-Process -FilePath $MysqlBin -WindowStyle Hidden
    Start-Sleep -Seconds 3
    # Verifier
    $retry = 0
    while ($retry -lt 5) {
        $test = & $MysqlCli -u root -e "SELECT 1" 2>&1
        if ($LASTEXITCODE -eq 0) { break }
        $retry++
        Start-Sleep -Seconds 2
    }
    if ($LASTEXITCODE -eq 0) {
        Write-Host "      [MySQL] Demarre" -ForegroundColor Green
    } else {
        Write-Host "      [MySQL] Echec demarrage" -ForegroundColor Red
    }
} else {
    Write-Host "      [MySQL] Deja en cours" - ForegroundColor Green
}

# Apache
$apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if (-not $apacheRunning) {
    Start-Process -FilePath $ApacheBin -WindowStyle Hidden
    Start-Sleep -Seconds 3
    $retry = 0
    while ($retry -lt 5) {
        $apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
        if ($apacheRunning) { break }
        $retry++
        Start-Sleep -Seconds 2
    }
    if ($apacheRunning) {
        Write-Host "      [Apache] Demarre" -ForegroundColor Green
    } else {
        Write-Host "      [Apache] Echec demarrage" -ForegroundColor Red
    }
} else {
    Write-Host "      [Apache] Deja en cours" - ForegroundColor Green
}

# ----- Verification finale -----
Write-Host ""
Write-Host "--- Verification finale ---" -ForegroundColor Cyan
$url = "http://localhost/$ProjectName/"

# Test HTTP
try {
    $req = Invoke-WebRequest -Uri $url -UseBasicParsing -TimeoutSec 5
    if ($req.StatusCode -eq 200) {
        Write-Host "[HTTP] Application repond (200 OK)" -ForegroundColor Green
    } else {
        Write-Host "[HTTP] Reponse $($req.StatusCode)" -ForegroundColor Yellow
    }
} catch {
    Write-Host "[HTTP] Application non accessible. Apache peut encore demarrer..." -ForegroundColor Yellow
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Setup termine avec succes !" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Application :  $url" -ForegroundColor Green
Write-Host "phpMyAdmin :   http://localhost/phpmyadmin" -ForegroundColor Green
Write-Host ""
Write-Host "Prochaine etape :" -ForegroundColor Yellow
Write-Host "  1. Configure l'API Claude dans config/ai.local.php (optionnel)" -ForegroundColor White
Write-Host "  2. Connecte-toi avec les identifiants de seed.sql" -ForegroundColor White
Write-Host ""

try {
    Start-Process $url
} catch {}
