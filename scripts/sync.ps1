param(
    [string]$Action = "",
    [string]$XamppPath = ""
)

$ProjectRoot = Split-Path $PSScriptRoot -Parent
. "$PSScriptRoot\_env.ps1"
$DbName = $env:DB_NAME
$ExportDir = Join-Path $ProjectRoot "database\exports"

# ----- Detection XAMPP -----
if (-not $XamppPath) {
    $candidates = @("C:\xampp", "D:\xampp", "E:\xampp",
        "$env:ProgramFiles\xampp", "${env:ProgramFiles(x886)}\xampp",
        "$env:LOCALAPPDATA\xampp")
    foreach ($c in $candidates) {
        if (Test-Path "$c\mysql\bin\mysqld.exe" -PathType Leaf) {
            $XamppPath = $c; break
        }
    }
}
$Mysqldump = Join-Path $XamppPath "mysql\bin\mysqldump.exe"
$Mysql = Join-Path $XamppPath "mysql\bin\mysql.exe"

if (-not (Test-Path $Mysqldump)) {
    Write-Host "[ERREUR] mysqldump introuvable dans $XamppPath" -ForegroundColor Red
    exit 1
}

# ----- Menu if no action -----
if (-not $Action) {
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  Sync DB — Center Domiciliation" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "  1. Exporter (push)" -ForegroundColor Yellow
    Write-Host "     Dump DB → commit → push" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  2. Importer (pull)" -ForegroundColor Yellow
    Write-Host "     Pull → restore DB" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  3. Exporter seulement" -ForegroundColor Yellow
    Write-Host "     Dump DB dans database/exports/" -ForegroundColor Gray
    Write-Host ""
    Write-Host "  4. Importer un dump" -ForegroundColor Yellow
    Write-Host "     Choisir un fichier dans database/exports/" -ForegroundColor Gray
    Write-Host ""
    $choice = Read-Host "  Choix (1-4)"
    switch ($choice) {
        "1" { $Action = "push" }
        "2" { $Action = "pull" }
        "3" { $Action = "export" }
        "4" { $Action = "import" }
        default { Write-Host "Annule." -ForegroundColor Red; exit 0 }
    }
}

# ----- EXPORT -----
function Export-Database {
    if (-not (Test-Path $ExportDir)) { New-Item -ItemType Directory -Path $ExportDir -Force | Out-Null }
    $date = Get-Date -Format "yyyy-MM-dd_HHmmss"
    $file = Join-Path $ExportDir "${DbName}_${date}.sql"

    Write-Host "[Export] Dump de $DbName..." -ForegroundColor Yellow
    & $Mysqldump -u $env:DB_USERNAME $(if ($env:DB_PASSWORD) { "--password=$env:DB_PASSWORD" }) --no-create-info --complete-insert --skip-extended-insert $DbName 2>&1 | Out-File -FilePath $file -Encoding UTF8

    if ($LASTEXITCODE -ne 0) {
        Write-Host "[ERREUR] Echec du dump." -ForegroundColor Red
        return $null
    }

    $size = [math]::Round((Get-Item $file).Length / 1KB, 1)
    Write-Host "      $file ($size KB)" -ForegroundColor Green
    return $file
}

# ----- IMPORT -----
function Import-Database {
    param([string]$FilePath)

    if (-not $FilePath) {
        $dumps = Get-ChildItem $ExportDir -Filter "*.sql" -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending
        if ($dumps.Count -eq 0) {
            Write-Host "[ERREUR] Aucun dump dans $ExportDir" -ForegroundColor Red
            return
        }
        Write-Host ""
        Write-Host "  Dumps disponibles :" -ForegroundColor Cyan
        for ($i = 0; $i -lt $dumps.Count; $i++) {
            $size = [math]::Round($dumps[$i].Length / 1KB, 1)
            $date = $dumps[$i].LastWriteTime.ToString("dd/MM HH:mm")
            Write-Host "  $($i+1). $($dumps[$i].Name)  ($size KB, $date)" -ForegroundColor White
        }
        Write-Host ""
        $idx = Read-Host "  Numero du dump a importer"
        if (-not $idx -match '^\d+$' -or [int]$idx -lt 1 -or [int]$idx -gt $dumps.Count) {
            Write-Host "  Annule." -ForegroundColor Red; return
        }
        $FilePath = $dumps[[int]$idx - 1].FullName
    }

    Write-Host ""
    Write-Host "[ATTENTION] Ceci va ECRASER la base $DbName actuelle." -ForegroundColor Red
    $confirm = Read-Host "  Confirmer ? (o/N)"
    if ($confirm -ne "o" -and $confirm -ne "O") {
        Write-Host "  Annule." -ForegroundColor Yellow; return
    }

    Write-Host "[Import] Restauration depuis $FilePath..." -ForegroundColor Yellow

    & $Mysql -u $env:DB_USERNAME $(if ($env:DB_PASSWORD) { "--password=$env:DB_PASSWORD" }) -e "DROP DATABASE IF EXISTS ``$DbName``; CREATE DATABASE ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>&1 | Out-Null
    & $Mysql -u $env:DB_USERNAME $(if ($env:DB_PASSWORD) { "--password=$env:DB_PASSWORD" }) $DbName < $FilePath 2>&1 | Out-Null

    if ($LASTEXITCODE -eq 0) {
        Write-Host "      Import reussi !" -ForegroundColor Green
    } else {
        Write-Host "      [ERREUR] Echec de l'import." -ForegroundColor Red
    }
}

# ----- ACTIONS -----
switch ($Action) {
    "export" {
        Export-Database | Out-Null
    }
    "import" {
        Import-Database
    }
    "push" {
        $file = Export-Database
        if ($file) {
            Write-Host ""
            Write-Host "[Git] Commit + push..." -ForegroundColor Yellow
            Push-Location $ProjectRoot
            & git add "database/exports/"
            & git commit -m "sync: DB dump $(Split-Path $file -Leaf)"
            & git push
            Pop-Location
            Write-Host "      Push termine !" -ForegroundColor Green
        }
    }
    "pull" {
        Write-Host "[Git] Pull..." -ForegroundColor Yellow
        Push-Location $ProjectRoot
        & git pull --rebase
        Pop-Location

        Import-Database
    }
}

Write-Host ""
pause
