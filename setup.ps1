param(
    [string]$XamppPath = "",
    [switch]$NoImport = $false
)

# ==========================================
#  Center Domiciliation - Setup Automatique
#  Portable : fonctionne depuis n'importe
#  quel dossier, n'importe quel PC
# ==========================================

$ErrorActionPreference = "Stop"

# ----- 1. Chemins dynamiques -----
$ProjectRoot = $PSScriptRoot  # Le dossier où se trouve setup.ps1
$ProjectName = Split-Path -Leaf $ProjectRoot
$HtdocsLink  = ""  # sera défini après détection XAMPP

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Center Domiciliation - Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "[1/7] Projet detecte : $ProjectRoot" -ForegroundColor Gray

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

Write-Host "[2/7] XAMPP detecte : $XamppPath" -ForegroundColor Green

$ApacheBin  = Join-Path $XamppPath "apache\bin\httpd.exe"
$MysqlBin   = Join-Path $XamppPath "mysql\bin\mysqld.exe"
$MysqlCli   = Join-Path $XamppPath "mysql\bin\mysql.exe"
$Htdocs     = Join-Path $XamppPath "htdocs"
$HtdocsBak  = Join-Path $XamppPath "htdocs_backup"
$HtdocsLink = Join-Path $Htdocs $ProjectName

# ----- 3. Création du dossier htdocs si manquant -----
if (-not (Test-Path $Htdocs -PathType Container)) {
    Write-Host "[3/7] Creation de htdocs..." -ForegroundColor Yellow
    if (Test-Path $HtdocsBak -PathType Container) {
        Rename-Item -Path $HtdocsBak -NewName "htdocs"
        Write-Host "      htdocs_backup -> htdocs" -ForegroundColor Green
    } else {
        New-Item -ItemType Directory -Path $Htdocs -Force | Out-Null
        Write-Host "      htdocs cree" -ForegroundColor Green
    }
} else {
    Write-Host "[3/7] htdocs existant" -ForegroundColor Green
}

# ----- 4. Création du lien symbolique -----
$linkExists = $false
if (Test-Path $HtdocsLink) {
    $linkTarget = (Get-Item $HtdocsLink).Target
    if ($linkTarget -and (Resolve-Path $linkTarget -ErrorAction SilentlyContinue).Path -eq $ProjectRoot) {
        $linkExists = $true
        Write-Host "[4/7] Lien deja actif : $ProjectName -> $ProjectRoot" -ForegroundColor Green
    } else {
        Write-Host "[4/7] Re-creation du lien..." -ForegroundColor Yellow
        Remove-Item -Path $HtdocsLink -Recurse -Force -ErrorAction SilentlyContinue
    }
}

if (-not $linkExists) {
    Write-Host "[4/7] Creation du lien symbolique..." -ForegroundColor Yellow
    try {
        New-Item -ItemType Junction -Path $HtdocsLink -Target $ProjectRoot -Force | Out-Null
        Write-Host "      $HtdocsLink" -ForegroundColor Green
        Write-Host "           -> $ProjectRoot" -ForegroundColor Green
    } catch {
        Write-Host "[ERREUR] Impossible de creer le lien." -ForegroundColor Red
        Write-Host "Essaie en mode Administrateur, ou active le mode Developpeur dans Windows." -ForegroundColor Yellow
        Write-Host ""
        Write-Host "Alternative : copie le dossier dans $Htdocs" -ForegroundColor Yellow
        exit 1
    }
}

# ----- 5. Base de donnees -----
if (-not $NoImport) {
    Write-Host "[5/7] Import de la base de donnees..." -ForegroundColor Yellow

    # Vérifier si MySQL est en cours
    $mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
    if (-not $mysqlRunning) {
        Write-Host "      Demarrage de MySQL..." -ForegroundColor Gray
        Start-Process -FilePath $MysqlBin -WindowStyle Hidden
        Start-Sleep -Seconds 4
    }

    $SchemaFile  = Join-Path $ProjectRoot "database\schema.sql"
    $SeedFile    = Join-Path $ProjectRoot "database\seed.sql"
    $ImportFile  = Join-Path $ProjectRoot "database\import.sql"

    function Import-SqlFile($file) {
        if (-not (Test-Path $file -PathType Leaf)) { return }
        try {
            Get-Content $file -Raw | & $MysqlCli -u root --default-character-set=utf8mb4 2>&1 | Out-Null
            return $true
        } catch {
            return $false
        }
    }

    if (Import-SqlFile $ImportFile) {
        Write-Host "      Base importee avec succes" -ForegroundColor Green
    } elseif (Test-Path $ImportFile) {
        Write-Host "[ERREUR] Echec import" -ForegroundColor Red
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
    Write-Host "[5/7] Import ignore (-NoImport)" -ForegroundColor Gray
}

# ----- 6. Creation dossiers utiles -----
Write-Host "[6/7] Verification des dossiers..." -ForegroundColor Yellow
$dirs = @("uploads", "output")
foreach ($d in $dirs) {
    $p = Join-Path $ProjectRoot $d
    if (-not (Test-Path $p -PathType Container)) {
        New-Item -ItemType Directory -Path $p -Force | Out-Null
        Write-Host "      $d/ cree" -ForegroundColor Green
    }
}
Write-Host "      OK" -ForegroundColor Green

# ----- 7. Demarrage Apache + MySQL -----
Write-Host "[7/7] Demarrage des services..." -ForegroundColor Yellow

# MySQL
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqlRunning) {
    Start-Process -FilePath $MysqlBin -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Write-Host "      [MySQL] Demarre" -ForegroundColor Green
} else {
    Write-Host "      [MySQL] Deja en cours" -ForegroundColor Green
}

# Apache
$apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if (-not $apacheRunning) {
    Start-Process -FilePath $ApacheBin -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Write-Host "      [Apache] Demarre" -ForegroundColor Green
} else {
    Write-Host "      [Apache] Deja en cours" -ForegroundColor Green
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Setup termine avec succes !" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
$url = "http://localhost/$ProjectName/"
Write-Host "Application :  $url" -ForegroundColor Green
Write-Host "phpMyAdmin :   http://localhost/phpmyadmin" -ForegroundColor Green
Write-Host ""

Start-Process $url
