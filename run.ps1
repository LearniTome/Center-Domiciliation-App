param(
    [string]$XamppPath = ""
)

# ==========================================
#  Center Domiciliation - XAMPP Launcher
#  Portable : auto-détection du chemin
#  Support : Windows (XAMPP) + macOS (Homebrew)
# ==========================================

$ProjectRoot = $PSScriptRoot
$ProjectName = Split-Path -Leaf $ProjectRoot
$isMacOS     = $IsMacOS -or ([System.Runtime.InteropServices.RuntimeInformation]::OSDescription -match "darwin")
$isWindows   = (-not $isMacOS) -and ($IsWindows -or ($PSVersionTable.PSVersion.Major -ge 5))

if ($isMacOS) {
    # ====================================================================
    #  BRANCHE macOS (Homebrew)
    # ====================================================================
    $phpPort = 8080
    $url = "http://localhost:$phpPort/"

    Write-Host "========================================" -ForegroundColor Cyan
    Write-Host "  Center Domiciliation - Launcher macOS" -ForegroundColor Cyan
    Write-Host "========================================" -ForegroundColor Cyan
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
                $hasChanges = & git status --porcelain 2>&1
                $stashed = $false
                if ($hasChanges) {
                    & git stash push -m "auto-stash run.ps1 $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" 2>&1 | Out-Null
                    $stashed = $true
                    Write-Host "      Modifications locales mises de cote (stash)" -ForegroundColor Gray
                }
                & git pull --rebase 2>&1 | Out-Null
                if ($LASTEXITCODE -eq 0) {
                    Write-Host "      Synchronise avec success" -ForegroundColor Green
                } else {
                    Write-Host "      [ATTENTION] Echec pull --rebase. Verifie ta connexion ou les conflits." -ForegroundColor Yellow
                }
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
    Write-Host ""

    # ----- Verification des prerequis -----
    Write-Host "[Prerequis] Verification..." -ForegroundColor Yellow

    # Homebrew
    $brewCheck = Get-Command "brew" -ErrorAction SilentlyContinue
    if (-not $brewCheck) {
        Write-Host "      Homebrew manquant. Lance d'abord : ./setup.ps1" -ForegroundColor Red
        exit 1
    }
    Write-Host "      Homebrew : present" -ForegroundColor Green

    # PHP
    $phpCheck = Get-Command "php" -ErrorAction SilentlyContinue
    if (-not $phpCheck) {
        Write-Host "      PHP manquant. Lance d'abord : ./setup.ps1" -ForegroundColor Red
        exit 1
    }
    $phpVer = & php -v 2>&1 | Select-Object -First 1
    Write-Host "      PHP : $phpVer" -ForegroundColor Green

    # MySQL
    $mysqlCheck = Get-Command "mysql" -ErrorAction SilentlyContinue
    if (-not $mysqlCheck) {
        Write-Host "      MySQL manquant. Lance d'abord : ./setup.ps1" -ForegroundColor Red
        exit 1
    }
    Write-Host "      MySQL : present" -ForegroundColor Green

    # Node.js
    $nodeCheck = Get-Command "node" -ErrorAction SilentlyContinue
    if ($nodeCheck) {
        Write-Host "      Node.js : $(node --version)" -ForegroundColor Green
    } else {
        Write-Host "      Node.js : non trouve (optionnel)" -ForegroundColor Gray
    }
    Write-Host ""

    # ----- MySQL -----
    Write-Host "[MySQL] Demarrage du service..." -ForegroundColor Yellow
    $mysqlRunning = & brew services list 2>&1 | Select-String "mysql.*started"
    if (-not $mysqlRunning) {
        & brew services start mysql 2>&1 | Out-Null
        Start-Sleep -Seconds 3
        $retry = 0
        while ($retry -lt 10) {
            $test = & mysql -u root -e "SELECT 1" 2>&1
            if ($LASTEXITCODE -eq 0) { break }
            $retry++
            Start-Sleep -Seconds 2
        }
        if ($LASTEXITCODE -eq 0) {
            Write-Host "      [MySQL] Demarre" -ForegroundColor Green
        } else {
            Write-Host "      [MySQL] Echec demarrage. Lance manuellement : brew services start mysql" -ForegroundColor Red
        }
    } else {
        Write-Host "      [MySQL] Deja en cours" -ForegroundColor Green
    }

    # ----- Verification Composer -----
    $vendorDir = Join-Path $ProjectRoot "vendor"
    if (-not (Test-Path $vendorDir -PathType Container)) {
        Write-Host "[Composer] Installation des dependances..." -ForegroundColor Yellow
        try {
            $composerCheck = Get-Command "composer" -ErrorAction SilentlyContinue
            if ($composerCheck) {
                Push-Location $ProjectRoot
                & composer install --no-interaction 2>&1 | Out-Null
                Pop-Location
            }
            if (Test-Path $vendorDir -PathType Container) {
                Write-Host "      Dependances installees" -ForegroundColor Green
            }
        } catch {
            Write-Host "[ATTENTION] Echec composer install. Lance manuellement : cd $ProjectRoot && composer install" -ForegroundColor Yellow
        }
        Write-Host ""
    }

    # ----- LibreOffice (optionnel) -----
    $libreCheck = Get-Command "soffice" -ErrorAction SilentlyContinue
    if (-not $libreCheck) {
        $librePaths = @(
            "/Applications/LibreOffice.app/Contents/MacOS/soffice",
            "$env:HOME/Applications/LibreOffice.app/Contents/MacOS/soffice"
        )
        foreach ($lp in $librePaths) {
            if (Test-Path $lp -PathType Leaf) { $libreCheck = $true; break }
        }
    }
    if (-not $libreCheck) {
        Write-Host "[LibreOffice] Recommande pour la conversion DOCX->PDF de qualite." -ForegroundColor Cyan
        Write-Host "  Installation : brew install --cask libreoffice" -ForegroundColor Gray
        Write-Host "  Sinon, conversion via PHPWord/Dompdf (fallback)." -ForegroundColor Gray
        Write-Host ""
    }

    # ----- Demarrage PHP Server -----
    Write-Host "[PHP] Demarrage du serveur..." -ForegroundColor Yellow

    # Tuer un eventuel processus existant sur le port
    $existingPhp = & lsof -ti:$phpPort 2>&1
    if ($existingPhp) {
        & kill -9 $existingPhp 2>&1 | Out-Null
        Start-Sleep -Seconds 1
    }

    $phpLog = Join-Path $ProjectRoot "php-server.log"
    $phpServer = Start-Process -NoNewWindow -PassThru -FilePath "php" -ArgumentList "-S localhost:$phpPort -t `"$ProjectRoot`"" -RedirectStandardOutput $phpLog -RedirectStandardError $phpLog
    Start-Sleep -Seconds 2

    Write-Host "      Serveur PHP lance sur le port $phpPort" -ForegroundColor Green
    Write-Host ""

    Write-Host "Application ouverte : $url" -ForegroundColor Green
    try {
        open $url
    } catch {
        Write-Host "Ouvre ce lien : $url" -ForegroundColor White
    }

    Write-Host ""
    Write-Host "Le serveur PHP tourne en arriere-plan." -ForegroundColor Cyan
    Write-Host "Logs : $phpLog" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Pour arreter :" -ForegroundColor Yellow
    Write-Host "  lsof -ti:$phpPort | xargs kill -9" -ForegroundColor White
    Write-Host "  brew services stop mysql" -ForegroundColor White
    Write-Host ""
    Write-Host "--- Apres avoir travaille, pousse tes changements :" -ForegroundColor Cyan
    Write-Host "  git add -A" -ForegroundColor White
    Write-Host "  git commit -m \"description du changement\"" -ForegroundColor White
    Write-Host "  git push" -ForegroundColor White
    Write-Host ""

    pause
    return
}

# ====================================================================
#  BRANCHE Windows (XAMPP)
# ====================================================================

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
            $hasChanges = & git status --porcelain 2>&1
            $stashed = $false
            if ($hasChanges) {
                & git stash push -m "auto-stash run.ps1 $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" 2>&1 | Out-Null
                $stashed = $true
                Write-Host "      Modifications locales mises de cote (stash)" -ForegroundColor Gray
            }
            & git pull --rebase 2>&1 | Out-Null
            if ($LASTEXITCODE -eq 0) {
                Write-Host "      Synchronise avec success" -ForegroundColor Green
            } else {
                Write-Host "      [ATTENTION] Echec pull --rebase. Verifie ta connexion ou les conflits." -ForegroundColor Yellow
            }
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
Write-Host "--- Apres avoir travaille, pousse tes changements :" -ForegroundColor Cyan
Write-Host "  git add -A" -ForegroundColor White
Write-Host "  git commit -m ""description du changement""" -ForegroundColor White
Write-Host "  git push" -ForegroundColor White
Write-Host ""
pause
