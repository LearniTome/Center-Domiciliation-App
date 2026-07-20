param(
    [string]$XamppPath = ""
)

# ==========================================
#  Center Domiciliation - XAMPP Launcher
#  Portable : auto-détection du chemin
# ==========================================

$ProjectRoot = $PSScriptRoot
$ProjectName = Split-Path -Leaf $ProjectRoot
$url = "http://localhost/$ProjectName/?autologin=1"

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
    Write-Host "[XAMPP] Introuvable. Installation via winget..." -ForegroundColor Yellow
    try {
        $proc = Start-Process -FilePath "winget" -ArgumentList "install --id ApacheFriends.Xampp.8.2 --silent --accept-package-agreements --accept-source-agreements" -NoNewWindow -Wait -PassThru
        if ($proc.ExitCode -eq 0) {
            Write-Host "[XAMPP] Installation reussie. Detection du chemin..." -ForegroundColor Green
            $candidates = @(
                "C:\xampp", "D:\xampp", "E:\xampp",
                "$env:ProgramFiles\xampp", "${env:ProgramFiles(x86)}\xampp",
                "$env:LOCALAPPDATA\xampp"
            )
            foreach ($candidate in $candidates) {
                if (Test-Path "$candidate\apache\bin\httpd.exe" -PathType Leaf) {
                    $XamppPath = $candidate
                    break
                }
            }
        }
    } catch {
        Write-Host "[ERREUR] Echec installation XAMPP : $_" -ForegroundColor Red
    }
    if (-not $XamppPath) {
        Write-Host "[ERREUR] XAMPP introuvable meme apres installation." -ForegroundColor Red
        Write-Host "Installe XAMPP manuellement depuis https://www.apachefriends.org/" -ForegroundColor Yellow
        Write-Host "Ou relance avec : .\run.ps1 -XamppPath ""C:\chemin\vers\xampp""" -ForegroundColor Yellow
        pause
        exit 1
    }
}

$ApacheBin = Join-Path $XamppPath "apache\bin\httpd.exe"
$MysqlBin  = Join-Path $XamppPath "mysql\bin\mysqld.exe"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Center Domiciliation - XAMPP Launcher" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  XAMPP : $XamppPath" -ForegroundColor Gray
Write-Host "  App   : $ProjectRoot" -ForegroundColor Gray
Write-Host ""

# ----- Synchronisation Git (pull --rebase avant de travailler) -----
$gitCheck = Get-Command "git" -ErrorAction SilentlyContinue
if ($gitCheck) {
    $gitDir = Join-Path $ProjectRoot ".git"
    if (Test-Path $gitDir -PathType Container) {
        Write-Host "[Git] Synchronisation avec le depot distant..." -ForegroundColor Yellow
        Push-Location $ProjectRoot
        try {
            # Stash les modifications locales si presentes
            $hasChanges = & git status --porcelain 2>&1
            $stashed = $false
            if ($hasChanges) {
                & git stash push -m "auto-stash run.ps1 $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" 2>&1 | Out-Null
                $stashed = $true
                Write-Host "      Modifications locales mises de cote (stash)" -ForegroundColor Gray
            }
            # Pull --rebase
            & git pull --rebase 2>&1 | Out-Null
            if ($LASTEXITCODE -eq 0) {
                Write-Host "      Synchronise avec success" -ForegroundColor Green
            } else {
                Write-Host "      [ATTENTION] Echec pull --rebase. Verifie ta connexion ou les conflits." -ForegroundColor Yellow
            }
            # Restore stash
            if ($stashed) {
                & git stash pop 2>&1 | Out-Null
                Write-Host "      Modifications locales restaurees" -ForegroundColor Gray
            }
        } catch {
            Write-Host "      [ATTENTION] Erreur Git : $_" -ForegroundColor Yellow
        }
        Pop-Location
    }
} else {
    Write-Host "[Git] Git non trouve, synchronisation ignoree" -ForegroundColor Gray
}

# ----- Sync DB (import latest dump from exports/) -----
$DbName = "center_domiciliation"
$ExportDir = Join-Path $ProjectRoot "database\exports"
$MysqldumpPath = Join-Path $XamppPath "mysql\bin\mysqldump.exe"
$MysqlPath = Join-Path $XamppPath "mysql\bin\mysql.exe"

if (Test-Path $MysqlPath) {
    $latestDump = Get-ChildItem $ExportDir -Filter "*.sql" -ErrorAction SilentlyContinue |
        Where-Object { $_.Name -like "${DbName}_*" } |
        Sort-Object LastWriteTime -Descending |
        Select-Object -First 1

    if ($latestDump) {
        $dbExists = & $MysqlPath -u root -e "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME='$DbName'" 2>&1 | Select-String $DbName
        $importNeeded = $false

        if (-not $dbExists) {
            Write-Host "[Sync] Base $DbName introuvable, import necessaire..." -ForegroundColor Yellow
            $importNeeded = $true
        } else {
            $tableCount = & $MysqlPath -u root -N -e "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='$DbName'" 2>&1
            $tableCount = $tableCount.Trim()
            if ($tableCount -eq "0") {
                Write-Host "[Sync] Base $DbName vide, import necessaire..." -ForegroundColor Yellow
                $importNeeded = $true
            }
        }

        if ($importNeeded) {
            Write-Host "[Sync] Import du dump: $($latestDump.Name)..." -ForegroundColor Yellow
            & $MysqlPath -u root -e "CREATE DATABASE IF NOT EXISTS ``$DbName`` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci" 2>&1 | Out-Null
            cmd /c "`"$MysqlPath`" -u root `"$DbName`" < `"$($latestDump.FullName)`"" 2>&1 | Out-Null
            if ($LASTEXITCODE -eq 0) {
                Write-Host "      DB importee avec succes" -ForegroundColor Green
            } else {
                Write-Host "      [ATTENTION] Echec de l'import DB" -ForegroundColor Yellow
            }
        } else {
            Write-Host "[Sync] DB $DbName a jour ($tableCount tables)" -ForegroundColor Green
        }
    } else {
        Write-Host "[Sync] Aucun dump dans database/exports/" -ForegroundColor Gray
    }
} else {
    Write-Host "[Sync] mysql introuvable, sync DB ignoree" -ForegroundColor Gray
}
Write-Host ""

# ----- Verification du lien symlink -----
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
        Write-Host "[ATTENTION] Impossible de creer le lien. Lance .\setup.ps1 en Admin." -ForegroundColor Yellow
    }
    Write-Host ""
}

# ----- Verification convertisseur DOCX->PDF -----
if (-not (Test-Path (Join-Path $ProjectRoot "vendor\autoload.php") -PathType Leaf)) {
    Write-Host "[Composer] Installation des dependances (phpword/dompdf)..." -ForegroundColor Yellow
    try {
        $composer = Get-Command "composer" -ErrorAction SilentlyContinue
        if ($composer) {
            & composer install --no-interaction --working-dir=$ProjectRoot 2>&1 | Out-Null
        } else {
            $composerPhar = Join-Path $ProjectRoot "composer.phar"
            if (-not (Test-Path $composerPhar)) {
                Invoke-WebRequest -Uri "https://getcomposer.org/composer.phar" -OutFile $composerPhar -UseBasicParsing
            }
            & php $composerPhar install --no-interaction --working-dir=$ProjectRoot 2>&1 | Out-Null
        }
        if (Test-Path (Join-Path $ProjectRoot "vendor\autoload.php")) {
            Write-Host "      Dependances installees" -ForegroundColor Green
        }
    } catch {
        Write-Host "[ATTENTION] Echec composer install. Lance manuellement : cd $ProjectRoot && composer install" -ForegroundColor Yellow
    }
    Write-Host ""
}
# ----- Extension COM (optionnelle - conversion DOCX->PDF via Word) -----
$PhpIni = Join-Path $XamppPath "php\php.ini"
if (Test-Path $PhpIni -PathType Leaf) {
    $iniContent = Get-Content $PhpIni -Raw
    if ($iniContent -match ";extension=php_com_dotnet\.dll" -or $iniContent -notmatch "extension=php_com_dotnet\.dll") {
        Write-Host "[INFO] Extension PHP COM desactivee (php_com_dotnet.dll)" -ForegroundColor Yellow
        Write-Host "  Utile : permet la conversion DOCX->PDF via Microsoft Word (alternative plus fidele)" -ForegroundColor Gray
        Write-Host "  Optionnel : la conversion fonctionne deja via PHPWord/Dompdf (fallback)" -ForegroundColor Gray
        Write-Host "  Pour activer : lance .\setup.ps1 (ou edite php.ini manuellement)" -ForegroundColor Gray
        Write-Host "  Ignorer : aucune incidence sur le fonctionnement courant de l'app" -ForegroundColor Gray
        Write-Host ""
    }
}

# ----- Detection LibreOffice (optionnel - conversion DOCX->PDF de qualite) -----
$librePaths = @(
    "${env:ProgramFiles}\LibreOffice\program\soffice.exe",
    "${env:ProgramFiles(x86)}\LibreOffice\program\soffice.exe"
)
$libreFound = $false
foreach ($lp in $librePaths) {
    if (Test-Path $lp -PathType Leaf) { $libreFound = $true; break }
}
if (-not $libreFound) {
    Write-Host "[LibreOffice] Recommande pour la conversion DOCX->PDF de qualite." -ForegroundColor Cyan
    Write-Host "  Taille : ~355 MB | Temps estimé : 2-5 min (selon le debit)" -ForegroundColor Gray
    Write-Host "  Si tu installes maintenant, le script reprendra automatiquement apres." -ForegroundColor Gray
    Write-Host "  Sinon, la conversion utilisera PHPWord/Dompdf (fallback) - suffisant dans la plupart des cas." -ForegroundColor Gray
    Write-Host ""
    $choice = Read-Host -Prompt "  Installer LibreOffice ? (o/N)"
    Write-Host ""
    if ($choice -eq "o" -or $choice -eq "O") {
        Write-Host "[LibreOffice] Installation en cours (cela peut prendre plusieurs minutes)..." -ForegroundColor Yellow
        try {
            $proc = Start-Process -FilePath "winget" -ArgumentList "install --id TheDocumentFoundation.LibreOffice --silent --accept-package-agreements --accept-source-agreements" -NoNewWindow -Wait -PassThru
            if ($proc.ExitCode -eq 0) {
                foreach ($lp in $librePaths) {
                    if (Test-Path $lp -PathType Leaf) { $libreFound = $true; break }
                }
                if ($libreFound) { Write-Host "      LibreOffice installe avec succes" -ForegroundColor Green }
            } else {
                Write-Host "      Echec de l'installation (code: $($proc.ExitCode))" -ForegroundColor Yellow
            }
        } catch {
            Write-Host "[INFO] LibreOffice non installe. Conversion PDF via PHPWord/Dompdf (fallback)." -ForegroundColor Gray
        }
    } else {
        Write-Host "[LibreOffice] Ignore. Conversion PDF via PHPWord/Dompdf." -ForegroundColor Gray
    }
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
# ----- Ouverture Chrome automatique -----
$chromePaths = @(
    "${env:ProgramFiles}\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
    "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe"
)
$chromePath = $chromePaths | Where-Object { Test-Path $_ } | Select-Object -First 1
if ($chromePath) {
    Start-Process -FilePath $chromePath -ArgumentList $url
    Write-Host "Chrome ouvert avec le profil par defaut (super admin)." -ForegroundColor Green
} else {
    Write-Host "[ATTENTION] Chrome introuvable. Ouverture du navigateur par defaut." -ForegroundColor Yellow
    try {
        Start-Process $url
    } catch {
        Write-Host "Ouvre ce lien manuellement : $url" -ForegroundColor White
    }
}

Write-Host ""
Write-Host "Les services XAMPP tournent en arriere-plan." -ForegroundColor Cyan
Write-Host "Ferme cette fenetre sans souci, ils restent actifs." -ForegroundColor Cyan
Write-Host ""
Write-Host "Pour les arreter :" -ForegroundColor Yellow
Write-Host "  taskkill /f /im httpd.exe" -ForegroundColor White
Write-Host "  taskkill /f /im mysqld.exe" -ForegroundColor White
Write-Host ""
Write-Host "--- Quand tu as termine, appuie sur Entree pour exporter et pousser." -ForegroundColor Cyan
Write-Host "    Ou ferme simplement la fenetre." -ForegroundColor Gray
Write-Host ""

$null = Read-Host

# ----- Auto-export DB + commit + push -----
Write-Host ""
Write-Host "[Sync] Export de la base $DbName..." -ForegroundColor Yellow
$dumpFile = Join-Path $ExportDir "$(Get-Date -Format 'yyyy-MM-dd_HHmmss').sql"
if (Test-Path $MysqldumpPath) {
    & $MysqldumpPath -u root --no-create-info --complete-insert --skip-extended-insert $DbName 2>&1 | Out-File -FilePath $dumpFile -Encoding UTF8
    if ($LASTEXITCODE -eq 0 -and (Test-Path $dumpFile)) {
        $size = [math]::Round((Get-Item $dumpFile).Length / 1KB, 1)
        Write-Host "      Export: $size KB" -ForegroundColor Green
    } else {
        Write-Host "      [ATTENTION] Echec de l'export" -ForegroundColor Yellow
        $dumpFile = $null
    }
} else {
    Write-Host "      mysqldump introuvable, export ignore" -ForegroundColor Yellow
    $dumpFile = $null
}

if ($dumpFile) {
    Write-Host "[Sync] Git commit + push..." -ForegroundColor Yellow
    Push-Location $ProjectRoot
    & git add "database/exports/" 2>&1 | Out-Null
    & git commit -m "sync: DB dump $(Split-Path $dumpFile -Leaf)" 2>&1 | Out-Null
    & git push 2>&1 | Out-Null
    Pop-Location
    if ($LASTEXITCODE -eq 0) {
        Write-Host "      Push termine !" -ForegroundColor Green
    } else {
        Write-Host "      [ATTENTION] Echec du push" -ForegroundColor Yellow
    }
}
Write-Host ""
