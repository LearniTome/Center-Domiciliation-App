<#
.SYNOPSIS
    Script d'installation et configuration XAMPP - Center Domiciliation

.DESCRIPTION
    Vérifie que XAMPP est installé (via winget si nécessaire),
    démarre Apache et MySQL, importe la base de données si absente,
    et vérifie la configuration PHP (extensions, connectivité PDO).

.PARAMETER SkipXampp
    Ignorer la vérification et l'installation de XAMPP.

.PARAMETER SkipDbImport
    Ignorer l'import de la base de données.

.PARAMETER Force
    Forcer la réimportation de la base de données (DROP + CREATE).

.EXAMPLE
    .\setup.ps1
    Exécution complète

.EXAMPLE
    .\setup.ps1 -SkipXampp
    Utilise XAMPP existant, démarre services, import DB si nécessaire

.EXAMPLE
    .\setup.ps1 -Force
    Force la réimportation de la base de données
#>

[CmdletBinding()]
param(
    [switch]$SkipXampp,
    [switch]$SkipDbImport,
    [switch]$Force
)

# ============================================================
# CONFIGURATION
# ============================================================
$ProjectRoot = $PSScriptRoot
$DbImportFile = Join-Path $ProjectRoot "database\import.sql"
$DbName = "center_domiciliation"
$MysqlPort = 3306
$ApachePort = 80
$XamppPaths = @(
    "C:\xampp",
    "D:\xampp",
    [Environment]::GetFolderPath("ProgramFiles") + "\xampp",
    [Environment]::GetFolderPath("ProgramFilesX86") + "\xampp"
)

# ============================================================
# FONCTIONS UTILITAIRES
# ============================================================

function Write-Step {
    param([string]$Label, [string]$Status, [string]$Detail = "")
    $icons = @{ OK = "OK"; WARN = "!!"; FAIL = "XX"; INFO = "--"; SKIP = ".." }
    $icon = if ($icons.ContainsKey($Status)) { $icons[$Status] } else { "--" }
    $timestamp = Get-Date -Format "HH:mm:ss"
    $msg = "[$timestamp] $icon $Label"
    if ($Detail) { $msg += " - $Detail" }
    switch ($Status) {
        "OK"   { Write-Host $msg -ForegroundColor Green }
        "WARN" { Write-Host $msg -ForegroundColor Yellow }
        "FAIL" { Write-Host $msg -ForegroundColor Red }
        "SKIP" { Write-Host $msg -ForegroundColor DarkGray }
        default { Write-Host $msg -ForegroundColor Cyan }
    }
}

function Test-Administrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Get-XamppPath {
    # 1. Chemins connus
    foreach ($p in $XamppPaths) {
        if ((Test-Path (Join-Path $p "apache\bin\httpd.exe")) -and
            (Test-Path (Join-Path $p "mysql\bin\mysqld.exe")) -and
            (Test-Path (Join-Path $p "php\php.exe"))) {
            return $p
        }
    }
    # 2. Registre Windows
    $regPaths = @(
        "HKLM:\SOFTWARE\Apache Friends",
        "HKLM:\SOFTWARE\WOW6432Node\Apache Friends"
    )
    foreach ($rp in $regPaths) {
        if (Test-Path $rp) {
            $installDir = (Get-ItemProperty -Path $rp -Name "InstallDir" -ErrorAction SilentlyContinue).InstallDir
            if ($installDir) {
                foreach ($p in @($installDir, $installDir + "\xampp")) {
                    if ((Test-Path (Join-Path $p "apache\bin\httpd.exe")) -and
                        (Test-Path (Join-Path $p "mysql\bin\mysqld.exe"))) {
                        return $p
                    }
                }
            }
        }
    }
    return $null
}

function Get-PhpIniPath {
    param([string]$XamppRoot)
    $paths = @(
        Join-Path $XamppRoot "php\php.ini"
        Join-Path $XamppRoot "php\php.ini-development"
        Join-Path $XamppRoot "php\php.ini-production"
    )
    foreach ($p in $paths) {
        if (Test-Path $p) { return $p }
    }
    return $null
}

function Enable-PhpExtension {
    param([string]$PhpIni, [string]$Extension)
    if (-not (Test-Path $PhpIni)) { return $false }
    $content = Get-Content $PhpIni -Raw
    # Déjà activée ?
    if ($content -match "(?m)^extension=$Extension\s*$") { return $true }
    # Commentée ?
    if ($content -match "(?m)^;extension=$Extension\s*$") {
        $newContent = $content -replace "(?m)^;extension=$Extension\s*$", "extension=$Extension"
        Set-Content -Path $PhpIni -Value $newContent -Encoding UTF8
        return $true
    }
    return $false
}

function Wait-ForPort {
    param([int]$Port, [int]$TimeoutSeconds = 30)
    $client = New-Object System.Net.Sockets.TcpClient
    $begin = Get-Date
    $connected = $false
    while ((-not $connected) -and ((Get-Date) - $begin).TotalSeconds -lt $TimeoutSeconds) {
        try {
            $client.Connect("127.0.0.1", $Port)
            $connected = $client.Connected
        } catch {
            Start-Sleep -Milliseconds 500
        }
    }
    $client.Close()
    return $connected
}

function Get-MySqlErrorLog {
    param([string]$XamppRoot)
    $logDirs = @(
        Join-Path $XamppRoot "mysql\data\*.err",
        Join-Path $XamppRoot "mysql\data\*.log"
    )
    foreach ($pattern in $logDirs) {
        $logs = Get-ChildItem -Path $pattern -ErrorAction SilentlyContinue | Sort-Object LastWriteTime -Descending
        if ($logs) {
            $content = Get-Content -Path $logs[0].FullName -Tail 20 -ErrorAction SilentlyContinue
            if ($content) { return $content }
        }
    }
    return $null
}

function Start-MySqlService {
    param([string]$XamppRoot, [int]$RetryCount = 2)
    $mysqld = Join-Path $XamppRoot "mysql\bin\mysqld.exe"

    # Essaye le service Windows
    $svc = Get-Service -Name "mysql" -ErrorAction SilentlyContinue
    if ($svc) {
        if ($svc.Status -eq "Running") { return $true }
        for ($i = 0; $i -lt $RetryCount; $i++) {
            Start-Service -Name "mysql" -ErrorAction SilentlyContinue
            Start-Sleep -Seconds 3
            $svc.Refresh()
            if ($svc.Status -eq "Running") { return $true }
        }
        return $false
    }

    # Vérifier si le binaire existe
    if (-not (Test-Path $mysqld)) { return $false }

    # Vérifier si déjà en cours
    $procs = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
    if ($procs) {
        # Verifier si le port répond
        $portOk = Wait-ForPort -Port 3306 -TimeoutSeconds 3
        if ($portOk) { return $true }
        # Processus fantôme - le tuer
        Write-Step -Label "MySQL" -Status "WARN" -Detail "Processus fantôme detecte - tentative de redemarrage"
        $procs | Stop-Process -Force -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 2
    }

    # Tentatives de démarrage
    for ($i = 0; $i -lt $RetryCount; $i++) {
        $p = Start-Process -FilePath $mysqld -WindowStyle Hidden -PassThru
        Start-Sleep -Seconds 2
        if ($p -and (-not $p.HasExited)) {
            $portOk = Wait-ForPort -Port 3306 -TimeoutSeconds 5
            if ($portOk) { return $true }
        }
        if ($i -lt ($RetryCount - 1)) {
            Write-Step -Label "MySQL" -Status "INFO" -Detail "Nouvelle tentative ($($i+2)/$RetryCount)..."
            Start-Sleep -Seconds 2
        }
    }

    return $false
}

function Start-ApacheService {
    param([string]$XamppRoot)
    # Essaye le service Windows
    $svc = Get-Service -Name "apache2.4" -ErrorAction SilentlyContinue
    if ($svc) {
        if ($svc.Status -eq "Running") { return $true }
        Start-Service -Name "apache2.4" -ErrorAction SilentlyContinue
        return $true
    }
    # Démarrage direct du binaire
    $httpd = Join-Path $XamppRoot "apache\bin\httpd.exe"
    if (-not (Test-Path $httpd)) { return $false }
    $procs = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
    if (-not $procs) {
        $p = Start-Process -FilePath $httpd -WindowStyle Hidden -PassThru
        if (-not $p) { return $false }
    }
    return $true
}

function Invoke-MySqlQuery {
    param([string]$Query, [string]$XamppRoot)
    $mysql = Join-Path $XamppRoot "mysql\bin\mysql.exe"
    if (-not (Test-Path $mysql)) { return $null }
    $result = & $mysql -u root -h 127.0.0.1 -N -e $Query 2>&1
    return $result
}

function Test-DatabaseExists {
    param([string]$XamppRoot)
    $result = Invoke-MySqlQuery -Query "SHOW DATABASES LIKE '$DbName'" -XamppRoot $XamppRoot
    return ($result -match $DbName)
}

function Get-DatabaseTables {
    param([string]$XamppRoot)
    $result = Invoke-MySqlQuery -Query "USE $DbName; SHOW TABLES" -XamppRoot $XamppRoot
    if (-not $result) { return @() }
    return $result -split "`n" | ForEach-Object { $_.Trim() } | Where-Object { $_ }
}

# ============================================================
# FONCTIONS D'INTERFACE UTILISATEUR
# ============================================================

function Write-Section {
    param([int]$Number, [string]$Title, [string]$Description)
    Write-Host ""
    Write-Host "==================================================" -ForegroundColor Magenta
    Write-Host "  ETAPE $Number - $Title" -ForegroundColor Magenta
    Write-Host "==================================================" -ForegroundColor Magenta
    Write-Host "  -> $Description" -ForegroundColor DarkGray
    Write-Host ""
}

function Write-SystemInfo {
    Write-Host "================================================" -ForegroundColor Cyan
    Write-Host "  CENTER DOMICILIATION - Configuration XAMPP" -ForegroundColor Cyan
    Write-Host "================================================" -ForegroundColor Cyan
    Write-Host ""
    try {
        $os = Get-CimInstance -ClassName Win32_OperatingSystem -ErrorAction SilentlyContinue
        $osInfo = if ($os) { "$($os.Caption) (build $($os.BuildNumber))" } else { "Windows" }
        $arch = [Environment]::GetEnvironmentVariable("PROCESSOR_ARCHITECTURE")
        $psVer = $PSVersionTable.PSVersion.ToString()
        Write-Host "  Systeme      : $osInfo" -ForegroundColor DarkGray
        Write-Host "  Architecture : $arch" -ForegroundColor DarkGray
        Write-Host "  PowerShell   : $psVer" -ForegroundColor DarkGray
        Write-Host "  Projet       : $ProjectRoot" -ForegroundColor DarkGray
        Write-Host "  Script       : setup.ps1 (idempotent - peut etre relance sans risque)" -ForegroundColor DarkGray
    } catch {
        Write-Host "  Projet : $ProjectRoot" -ForegroundColor DarkGray
    }
    Write-Host ""
}

function Invoke-WithSpinner {
    param(
        [string]$Message,
        [ScriptBlock]$ScriptBlock,
        [int]$TimeoutSeconds = 0
    )
    $spinner = @('|', '/', '-', '\')
    $job = Start-Job -ScriptBlock $ScriptBlock
    $i = 0
    $begin = Get-Date
    while ($job.State -eq 'Running') {
        $elapsed = [math]::Round(((Get-Date) - $begin).TotalSeconds, 1)
        $spinnerChar = $spinner[$i % $spinner.Length]
        Write-Host "`r  $spinnerChar $Message ($elapsed s)" -NoNewline
        $i++
        if ($TimeoutSeconds -gt 0 -and $elapsed -ge $TimeoutSeconds) {
            Stop-Job $job -ErrorAction SilentlyContinue
            Write-Host "`r  X $Message (TIMEOUT)" -ForegroundColor Red
            return $null
        }
        Start-Sleep -Milliseconds 200
    }
    Write-Host "`r  > $Message (termine)" -ForegroundColor Green
    $result = Receive-Job $job -ErrorAction SilentlyContinue
    Remove-Job $job -ErrorAction SilentlyContinue
    return $result
}

function Write-Elapsed {
    param([System.Diagnostics.Stopwatch]$Stopwatch, [string]$Label)
    $elapsed = $Stopwatch.Elapsed.TotalSeconds
    if ($elapsed -lt 60) {
        $msg = "$($elapsed.ToString('0.1')) secondes"
    } else {
        $minutes = [math]::Floor($elapsed / 60)
        $seconds = [math]::Round($elapsed % 60, 1)
        $msg = "${minutes}m${seconds}s"
    }
    Write-Step -Label $Label -Status "INFO" -Detail "$msg"
}

function Enable-PhpExtension {
    param([string]$PhpIni, [string]$Extension)
    if (-not (Test-Path $PhpIni)) { return "not_found" }
    $content = Get-Content $PhpIni -Raw
    if ($content -match "(?m)^extension=$Extension\s*$") { return "already_enabled" }
    if ($content -match "(?m)^;extension=$Extension\s*$") {
        $newContent = $content -replace "(?m)^;extension=$Extension\s*$", "extension=$Extension"
        Set-Content -Path $PhpIni -Value $newContent -Encoding UTF8
        return "activated"
    }
    if ($content -match "(?m)^;?\s*extension=$Extension") {
        $newContent = $content -replace "(?m)^;?\s*(extension=$Extension)\s*$", '$1'
        Set-Content -Path $PhpIni -Value $newContent -Encoding UTF8
        return "activated"
    }
    return "not_found"
}

function Set-ApacheDocumentRoot {
    param([string]$XamppRoot, [string]$ProjectRoot)
    $confFile = Join-Path $XamppRoot "apache\conf\httpd.conf"
    $backupDir = Join-Path $XamppRoot "htdocs_backup"
    $htdocs = Join-Path $XamppRoot "htdocs"
    $projectFwd = $ProjectRoot -replace '\\', '/'
    $changes = @()

    if (-not (Test-Path $confFile)) {
        return @{ Success = $false; Error = "httpd.conf introuvable : $confFile" }
    }

    $conf = Get-Content $confFile -Raw

    # 1. Backup htdocs -> htdocs_backup
    if ((Test-Path $htdocs) -and -not (Test-Path $backupDir)) {
        Move-Item $htdocs $backupDir
        $changes += "htdocs -> htdocs_backup"
    }

    # 2. Definir SRVROOT et XAMPPROOT si absents
    if (-not ($conf -match "Define XAMPPROOT")) {
        $conf = $conf -replace '(?m)^Define SRVROOT.*$', "Define SRVROOT `"C:/xampp/apache`"`r`nDefine XAMPPROOT `"C:/xampp`""
        $changes += "SRVROOT/XAMPPROOT definis"
    }

    # 3. Changer DocumentRoot
    if ($conf -match 'DocumentRoot\s+"[^"]*htdocs"') {
        $conf = $conf -replace '(?m)^DocumentRoot\s+"[^"]*"', "DocumentRoot `"$projectFwd`""
        $changes += "DocumentRoot pointe vers le projet"
    }
    if ($conf -match '<Directory\s+"[^"]*htdocs">') {
        $conf = $conf -replace '(?m)^<Directory\s+"[^"]*">', "<Directory `"$projectFwd`">"
        $changes += "Directory pointe vers le projet"
    }

    # 4. Ajouter PHP module si absent
    if (-not ($conf -match "php_module")) {
        $phpModule = "`r`n# PHP`r`nPHPIniDir `"C:/xampp/php`"`r`nLoadModule php_module `"C:/xampp/php/php8apache2_4.dll`"`r`n<FilesMatch `"\.php$`">`r`n    SetHandler application/x-httpd-php`r`n</FilesMatch>`r`n"
        $conf = $conf -replace '(?m)#LoadModule watchdog_module modules/mod_watchdog.so', "`$0$phpModule"
        $changes += "Module PHP ajoute"
    }

    # 5. Ajouter DirectoryIndex index.php
    if ($conf -match 'DirectoryIndex\s+index\.html\s*$') {
        $conf = $conf -replace '(?m)^(\s*DirectoryIndex\s+)index\.html\s*$', '$1index.php index.html'
        $changes += "index.php ajoute au DirectoryIndex"
    }

    # 6. Ajouter les alias XAMPP si absents
    if (-not ($conf -match 'Alias /xampp')) {
        $backupFwd = $backupDir -replace '\\', '/'
        $aliases = @"
`r`n    Alias /xampp "$backupFwd/xampp"
    Alias /dashboard "$backupFwd/dashboard"
    Alias /phpmyadmin "C:/xampp/phpMyAdmin"
    Alias /img "$backupFwd/img"
    Alias /webalizer "$backupFwd/webalizer"
"@
        # Ajouter apres ScriptAlias /cgi-bin/
        $conf = $conf -replace '(?m)^(\s*ScriptAlias /cgi-bin/.*)$', "`$1$aliases"
        $changes += "Alias XAMPP ajoutes"
    }

    # 7. Ajouter les Directory blocks pour les alias si absents
    if (-not ($conf -match 'Directory "C:/xampp/htdocs_backup/xampp"')) {
        $dirBlocks = @"
`r`n<Directory "C:/xampp/htdocs_backup/xampp">
    Options Indexes FollowSymLinks ExecCGI Includes
    AllowOverride All
    Require all granted
</Directory>

<Directory "C:/xampp/htdocs_backup/dashboard">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

<Directory "C:/xampp/phpMyAdmin">
    Options Indexes FollowSymLinks ExecCGI
    AllowOverride All
    Require all granted
</Directory>

<Directory "C:/xampp/htdocs_backup/img">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>

<Directory "C:/xampp/htdocs_backup/webalizer">
    Options Indexes FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
"@
        $conf = $conf -replace '(?m)^(</Directory>\s*)$', "`$1$dirBlocks"
        $changes += "Directory blocks pour alias ajoutes"
    }

    Set-Content -Path $confFile -Value $conf -Encoding ASCII
    return @{ Success = $true; Changes = $changes }
}

function Find-ProcessOnPort {
    param([int]$Port)
    try {
        $connections = netstat -ano | Select-String ":$Port\s"
        foreach ($conn in $connections) {
            if ($conn -match "\s+(\d+)\s*$") {
                $pid = $matches[1]
                $proc = Get-Process -Id $pid -ErrorAction SilentlyContinue
                if ($proc) {
                    return [PSCustomObject]@{ PID = $pid; Name = $proc.ProcessName; Path = $proc.Path }
                }
            }
        }
    } catch {}
    return $null
}

# ============================================================
# DÉBUT DU SCRIPT
# ============================================================
$isAdmin = Test-Administrator
$exitCode = 0
$results = @()
$xamppRoot = $null
$mysqlOk = $false
$portReady = $false
$dbOk = $false
$globalSw = [System.Diagnostics.Stopwatch]::StartNew()

Clear-Host
Write-SystemInfo

if (-not $isAdmin) {
    Write-Step -Label "Privileges" -Status "WARN" -Detail "Droits limités - certaines operations (installation, services) necessitent une elevation"
    Write-Host "  -> Relancez en tant qu'administrateur si vous voulez installer XAMPP automatiquement" -ForegroundColor DarkGray
}

Write-Host ""

# ============================================================
# ÉTAPE 1 — Environnement
# ============================================================
Write-Section -Number 1 -Title "Environnement" -Description "Verification de winget et recherche de XAMPP"
$sw = [System.Diagnostics.Stopwatch]::StartNew()

Write-Step -Label "Recherche de XAMPP..." -Status "INFO"
Write-Host "    -> Chemins verifies : $($XamppPaths -join ', ')" -ForegroundColor DarkGray
Write-Host "    -> Registre Windows : HKLM\SOFTWARE\Apache Friends" -ForegroundColor DarkGray
$xamppRoot = Get-XamppPath

if ($xamppRoot) {
    Write-Step -Label "XAMPP" -Status "OK" -Detail "Trouve dans $xamppRoot"
    Write-Host "    -> Apache       : $(Test-Path (Join-Path $xamppRoot 'apache\bin\httpd.exe'))" -ForegroundColor DarkGray
    Write-Host "    -> MySQL        : $(Test-Path (Join-Path $xamppRoot 'mysql\bin\mysqld.exe'))" -ForegroundColor DarkGray
    Write-Host "    -> PHP          : $(Test-Path (Join-Path $xamppRoot 'php\php.exe'))" -ForegroundColor DarkGray
    $results += [PSCustomObject]@{ Etape = "XAMPP installe"; Statut = "OK" }
} else {
    Write-Step -Label "XAMPP" -Status "INFO" -Detail "Non trouve"
    Write-Host "    -> Aucune installation XAMPP detectee" -ForegroundColor DarkGray
    Write-Host "    -> Verification de winget pour installation automatique..." -ForegroundColor DarkGray
    Write-Step -Label "Verification de winget..." -Status "INFO"
    $wingetAvailable = $null -ne (Get-Command "winget" -ErrorAction SilentlyContinue)

    if ($SkipXampp) {
        Write-Step -Label "XAMPP" -Status "SKIP" -Detail "Option -SkipXampp activee"
        $results += [PSCustomObject]@{ Etape = "XAMPP installe"; Statut = "SKIP" }
    } elseif (-not $wingetAvailable) {
        Write-Step -Label "winget" -Status "FAIL" -Detail "Indisponible - installation manuelle requise"
        Write-Host "    -> Telechargez XAMPP depuis : https://www.apachefriends.org/fr/download.html" -ForegroundColor Yellow
        $results += [PSCustomObject]@{ Etape = "XAMPP installe"; Statut = "FAIL" }
        $exitCode = 1
    } elseif (-not $isAdmin) {
        Write-Step -Label "winget" -Status "OK" -Detail "Gestionnaire de paquets disponible"
        Write-Step -Label "Installation" -Status "FAIL" -Detail "Necessite des droits administrateur"
        Write-Host "    -> Relancez en tant qu'administrateur pour installer XAMPP automatiquement" -ForegroundColor Yellow
        $results += [PSCustomObject]@{ Etape = "XAMPP installe"; Statut = "FAIL" }
        $exitCode = 1
    } else {
        Write-Step -Label "winget" -Status "OK" -Detail "Gestionnaire de paquets disponible"
        Write-Step -Label "Installation" -Status "INFO" -Detail "Telechargement et installation de XAMPP 8.2..."
        Write-Host "    -> Package : ApacheFriends.Xampp.8.2" -ForegroundColor DarkGray
        Write-Host "    -> Destination : C:\xampp" -ForegroundColor DarkGray
        Write-Host "    -> Ceci peut prendre 2 a 5 minutes selon votre connexion" -ForegroundColor DarkGray
        try {
            $installResult = Invoke-WithSpinner -Message "Installation de XAMPP via winget..." -ScriptBlock {
                & winget install ApacheFriends.Xampp.8.2 --accept-source-agreements --accept-package-agreements --disable-interactivity 2>&1
            }
            $exitCodeInstall = $LASTEXITCODE
            if ($exitCodeInstall -eq 0) {
                Write-Step -Label "XAMPP" -Status "OK" -Detail "Installe avec succes"
                $xamppRoot = Get-XamppPath
                $results += [PSCustomObject]@{ Etape = "XAMPP installe"; Statut = "OK" }
            } else {
                Write-Step -Label "XAMPP" -Status "FAIL" -Detail "Echec winget (code $exitCodeInstall)"
                if ($installResult) { Write-Host "    -> $installResult" -ForegroundColor Red }
                $results += [PSCustomObject]@{ Etape = "XAMPP installe"; Statut = "FAIL" }
                $exitCode = 1
            }
        } catch {
            Write-Step -Label "XAMPP" -Status "FAIL" -Detail "Erreur : $_"
            $results += [PSCustomObject]@{ Etape = "XAMPP installe"; Statut = "FAIL" }
            $exitCode = 1
        }
    }
}

$sw.Stop()
Write-Elapsed -Stopwatch $sw -Label "Etape 1"

# ============================================================
# ÉTAPE 2 — MySQL
# ============================================================
Write-Section -Number 2 -Title "MySQL" -Description "Demarrage du serveur MySQL et verification du port"
$sw = [System.Diagnostics.Stopwatch]::StartNew()

$mysqlOk = $false
$portReady = $false
if ($xamppRoot) {
    # 0. Verifier les permissions d'ecriture sur le repertoire data MySQL
    $mysqlDataDir = Join-Path $xamppRoot "mysql\data"
    $testFile = Join-Path $mysqlDataDir "test_write_perms.tmp"
    try {
        [System.IO.File]::WriteAllText($testFile, "test")
        Remove-Item -LiteralPath $testFile -Force -ErrorAction SilentlyContinue
    } catch {
        Write-Step -Label "MySQL" -Status "WARN" -Detail "Permissions insuffisantes sur $mysqlDataDir"
        Write-Host "    -> MySQL ne pourra pas ecrire ses fichiers de donnees (ibdata1, etc.)" -ForegroundColor Yellow
        Write-Host "    -> Lancez PowerShell en Administrateur et executez :" -ForegroundColor Yellow
        Write-Host "       icacls `"$mysqlDataDir`" /grant `"Utilisateurs:(OI)(CI)M`" /T /Q" -ForegroundColor White
        Write-Host "    -> Sinon, lancez XAMPP Control Panel en Administrateur" -ForegroundColor Yellow
    }

    # 1. Verifier si le port est bloque par un autre processus
    Write-Step -Label "Verification du port $MysqlPort..." -Status "INFO"
    $mysqlBlocker = Find-ProcessOnPort -Port $MysqlPort
    if ($mysqlBlocker -and $mysqlBlocker.Name -ne "mysqld" -and $mysqlBlocker.Name -ne "mysqld-nt") {
        Write-Step -Label "Port $MysqlPort" -Status "WARN" -Detail "Occupe par $($mysqlBlocker.Name) (PID $($mysqlBlocker.PID))"
        Write-Host "    -> Ce nest pas MySQL. Arretez-le : Stop-Process -Id $($mysqlBlocker.PID) -Force" -ForegroundColor Yellow
    } elseif ($mysqlBlocker) {
        Write-Step -Label "Port $MysqlPort" -Status "OK" -Detail "Deja occupe par MySQL (PID $($mysqlBlocker.PID))"
        $mysqlOk = $true
        $portReady = $true
        $results += [PSCustomObject]@{ Etape = "MySQL demarre"; Statut = "OK" }
    }

    # 2. Demarrer MySQL si pas encore actif
    if (-not $mysqlOk) {
        Write-Step -Label "Recherche du service Windows 'mysql'..." -Status "INFO"
        $svc = Get-Service -Name "mysql" -ErrorAction SilentlyContinue
        if ($svc) {
            if ($svc.Status -eq "Running") {
                Write-Step -Label "MySQL" -Status "OK" -Detail "Service deja en cours d'execution"
                $mysqlOk = $true
            } else {
                Write-Step -Label "MySQL" -Status "INFO" -Detail "Service trouve, demarrage..."
                for ($i = 0; $i -lt 2; $i++) {
                    Start-Service -Name "mysql" -ErrorAction SilentlyContinue
                    Start-Sleep -Seconds 3
                    $svc.Refresh()
                    if ($svc.Status -eq "Running") { $mysqlOk = $true; break }
                    if ($i -eq 0) { Write-Step -Label "MySQL" -Status "INFO" -Detail "Nouvelle tentative..." }
                }
            }
        } else {
            Write-Step -Label "MySQL" -Status "INFO" -Detail "Service non trouve, demarrage direct de mysqld.exe..."
            $mysqld = Join-Path $xamppRoot "mysql\bin\mysqld.exe"
            if (-not (Test-Path $mysqld)) {
                Write-Step -Label "MySQL" -Status "FAIL" -Detail "Binaire introuvable : $mysqld"
            } else {
                # Verifier les logs d'erreur MySQL avant de demarrer
                $mysqlErrorLogs = Get-MySqlErrorLog -XamppRoot $xamppRoot
                if ($mysqlErrorLogs) {
                    $lastErrors = $mysqlErrorLogs | Select-Object -Last 5
                    $hasCrash = $lastErrors -match "Aborting|crash|InnoDB.*error|Can't start"
                    if ($hasCrash) {
                        Write-Step -Label "MySQL" -Status "WARN" -Detail "Des erreurs ont ete detectees dans les logs"
                        foreach ($err in ($lastErrors | Select-Object -First 3)) {
                            Write-Host "    -> $err" -ForegroundColor Yellow
                        }
                    }
                }

                Write-Step -Label "MySQL" -Status "INFO" -Detail "Lancement de mysqld.exe (2 tentatives)..."
                Write-Host "    -> Tentative 1/2..." -ForegroundColor DarkGray
                $mysqlOk = Start-MySqlService -XamppRoot $xamppRoot -RetryCount 2
                if ($mysqlOk) {
                    Write-Step -Label "MySQL" -Status "OK" -Detail "Processus lance avec succes"
                } else {
                    Write-Step -Label "MySQL" -Status "FAIL" -Detail "Demarrage echoue apres 2 tentatives"
                    $logs = Get-MySqlErrorLog -XamppRoot $xamppRoot
                    if ($logs) {
                        Write-Host "    -> Dernieres lignes du log MySQL :" -ForegroundColor Yellow
                        foreach ($l in ($logs | Select-Object -Last 5)) {
                            Write-Host "      $l" -ForegroundColor Yellow
                        }
                        if ($logs -join "`n" -match "ibdata1.*must be writable") {
                            Write-Host "" -ForegroundColor Yellow
                            Write-Host "    !!! PERMISSIONS INSUFFISANTES DETECTEES !!!" -ForegroundColor Red
                            Write-Host "    -> MySQL ne peut pas ecrire dans ibdata1 (fichier de donnees InnoDB)" -ForegroundColor Yellow
                            Write-Host "    -> Lancez PowerShell en Administrateur et executez :" -ForegroundColor Yellow
                            Write-Host "       icacls `"C:\xampp\mysql\data`" /grant `"Utilisateurs:(OI)(CI)M`" /T /Q" -ForegroundColor White
                            Write-Host "" -ForegroundColor Yellow
                        }
                    }
                }
            }
        }

        # 3. Attente du port si MySQL a demarre
        if ($mysqlOk) {
            Write-Step -Label "Attente du port $MysqlPort..." -Status "INFO"
            Write-Host "    -> Verification de la disponibilite de MySQL..." -ForegroundColor DarkGray
            $portReady = Wait-ForPort -Port $MysqlPort -TimeoutSeconds 30
            if ($portReady) {
                Write-Step -Label "Port MySQL" -Status "OK" -Detail "Port $MysqlPort accessible"
                $results += [PSCustomObject]@{ Etape = "MySQL demarre"; Statut = "OK" }
            } else {
                Write-Step -Label "Port MySQL" -Status "WARN" -Detail "Timeout - le port $MysqlPort n'est pas ouvert"
                Write-Host "    -> Verifiez les logs : C:\xampp\mysql\data\*.err" -ForegroundColor Yellow
                $results += [PSCustomObject]@{ Etape = "MySQL demarre"; Statut = "WARN" }
            }
        } else {
            $results += [PSCustomObject]@{ Etape = "MySQL demarre"; Statut = "FAIL" }
        }
    }
} else {
    Write-Step -Label "MySQL" -Status "SKIP" -Detail "XAMPP non disponible"
    $results += [PSCustomObject]@{ Etape = "MySQL demarre"; Statut = "SKIP" }
}

$sw.Stop()
Write-Elapsed -Stopwatch $sw -Label "Etape 2"

# ============================================================
# ÉTAPE 3 — Apache
# ============================================================
Write-Section -Number 3 -Title "Apache" -Description "Demarrage du serveur Apache"
$sw = [System.Diagnostics.Stopwatch]::StartNew()

if ($xamppRoot) {
    # Verifier d'abord si les ports Apache sont libres
    $blockedPorts = @()
    foreach ($apPort in @(80, 443)) {
        $blocker = Find-ProcessOnPort -Port $apPort
        if ($blocker -and $blocker.Name -ne "httpd") {
            $blockedPorts += [PSCustomObject]@{ Port = $apPort; Blocker = $blocker }
        }
    }

    if ($blockedPorts.Count -gt 0) {
        Write-Step -Label "Ports Apache" -Status "WARN" -Detail "Ports bloques"
        foreach ($bp in $blockedPorts) {
            Write-Host "    -> Port $($bp.Port) utilise par $($bp.Blocker.Name) (PID $($bp.Blocker.PID))" -ForegroundColor Yellow
            if ($bp.Blocker.Path) {
                Write-Host "    -> Chemin : $($bp.Blocker.Path)" -ForegroundColor DarkGray
            }
        }
        Write-Host "    -> Solutions :" -ForegroundColor Yellow
        Write-Host "       a) Arreter le processus bloquant : Stop-Process -Id $($blockedPorts[0].Blocker.PID) -Force" -ForegroundColor Yellow
        Write-Host "       b) Configurer Apache sur un autre port (C:\xampp\apache\conf\httpd.conf)" -ForegroundColor Yellow
        Write-Host "       c) Ignorer ce message si Apache fonctionne deja" -ForegroundColor Yellow
    }

    Write-Step -Label "Recherche du service Windows 'apache2.4'..." -Status "INFO"
    $svc = Get-Service -Name "apache2.4" -ErrorAction SilentlyContinue
    if ($svc) {
        if ($svc.Status -eq "Running") {
            Write-Step -Label "Apache" -Status "OK" -Detail "Service deja en cours d'execution"
            $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "OK" }
        } elseif ($blockedPorts.Count -gt 0) {
            Write-Step -Label "Apache" -Status "FAIL" -Detail "Ports bloques - impossible de demarrer"
            $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "FAIL" }
        } else {
            Write-Step -Label "Apache" -Status "INFO" -Detail "Service trouve, demarrage..."
            Start-Service -Name "apache2.4" -ErrorAction SilentlyContinue
            $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "OK" }
        }
    } else {
        Write-Step -Label "Apache" -Status "INFO" -Detail "Service non trouve, demarrage direct de httpd.exe..."
        $httpd = Join-Path $xamppRoot "apache\bin\httpd.exe"
        $procs = Get-Process -Name "httpd" -ErrorAction SilentlyContinue

        if ($procs) {
            if ($blockedPorts.Count -gt 0) {
                Write-Step -Label "Apache" -Status "WARN" -Detail "Processus httpd.exe trouve mais ports bloques - Apache peut ne pas repondre"
                $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "WARN" }
            } else {
                Write-Step -Label "Apache" -Status "OK" -Detail "Processus deja en cours d'execution"
                $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "OK" }
            }
        } elseif (Test-Path $httpd) {
            if ($blockedPorts.Count -gt 0) {
                Write-Step -Label "Apache" -Status "FAIL" -Detail "Ports $($blockedPorts.Port -join ', ') bloques - demarrage impossible"
                $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "FAIL" }
            } else {
                Write-Step -Label "Apache" -Status "INFO" -Detail "Lancement de httpd.exe..."
                $p = Start-Process -FilePath $httpd -WindowStyle Hidden -PassThru
                if ($p) {
                    Write-Step -Label "Apache" -Status "OK" -Detail "Processus lance"
                    $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "OK" }
                } else {
                    Write-Step -Label "Apache" -Status "FAIL" -Detail "Echec du lancement"
                    $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "FAIL" }
                }
            }
        } else {
            Write-Step -Label "Apache" -Status "WARN" -Detail "Binaire introuvable : $httpd"
            $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "WARN" }
        }
    }
} else {
    Write-Step -Label "Apache" -Status "SKIP" -Detail "XAMPP non disponible"
    $results += [PSCustomObject]@{ Etape = "Apache demarre"; Statut = "SKIP" }
}

$sw.Stop()
Write-Elapsed -Stopwatch $sw -Label "Etape 3"

# ============================================================
# ÉTAPE 4 — Configuration Apache
# ============================================================
Write-Section -Number 4 -Title "Configuration Apache" -Description "Liaison du projet dans le serveur web"
$sw = [System.Diagnostics.Stopwatch]::StartNew()

if ($xamppRoot) {
    Write-Step -Label "Configuration de httpd.conf..." -Status "INFO"
    Write-Host "    -> Backup de htdocs vers htdocs_backup" -ForegroundColor DarkGray
    Write-Host "    -> DocumentRoot = $ProjectRoot" -ForegroundColor DarkGray
    Write-Host "    -> Ajout du module PHP" -ForegroundColor DarkGray
    Write-Host "    -> Alias pour /xampp, /dashboard, /phpmyadmin" -ForegroundColor DarkGray

    $result = Set-ApacheDocumentRoot -XamppRoot $xamppRoot -ProjectRoot $ProjectRoot

    if ($result.Success) {
        if ($result.Changes.Count -gt 0) {
            Write-Step -Label "Apache" -Status "OK" -Detail "Configuration mise a jour"
            foreach ($c in $result.Changes) {
                Write-Host "    -> $c" -ForegroundColor Green
            }
            Write-Host "    -> Redemarrage d'Apache pour appliquer les modifications..." -ForegroundColor DarkGray
            Stop-Process -Name "httpd" -Force -ErrorAction SilentlyContinue
            Start-Sleep 1
            $p = Start-Process -FilePath (Join-Path $xamppRoot "apache\bin\httpd.exe") -WindowStyle Hidden -PassThru
            if ($p) {
                Write-Step -Label "Apache" -Status "OK" -Detail "Redemarre avec la nouvelle configuration"
            } else {
                Write-Step -Label "Apache" -Status "WARN" -Detail "Echec du redemarrage - faites-le manuellement"
            }

            # Mettre a jour base_url dans config/app.php
            $configFile = Join-Path $ProjectRoot "config\app.php"
            if (Test-Path $configFile) {
                $configContent = Get-Content $configFile -Raw
                $oldBaseUrl = "/Center-Domiciliation-App/index.php"
                $newBaseUrl = "/index.php"
                if ($configContent -match [regex]::Escape($oldBaseUrl)) {
                    $configContent = $configContent -replace [regex]::Escape($oldBaseUrl), $newBaseUrl
                    Set-Content -Path $configFile -Value $configContent -NoNewline
                    Write-Step -Label "Config" -Status "OK" -Detail "base_url mis a jour dans config/app.php"
                } elseif ($configContent -match [regex]::Escape($newBaseUrl)) {
                    Write-Step -Label "Config" -Status "OK" -Detail "base_url deja a jour dans config/app.php"
                } else {
                    Write-Step -Label "Config" -Status "WARN" -Detail "base_url inattendu dans config/app.php"
                }
            }
        } else {
            Write-Step -Label "Apache" -Status "OK" -Detail "Configuration deja a jour"
        }
    } else {
        Write-Step -Label "Apache" -Status "FAIL" -Detail "Erreur : $($result.Error)"
    }
} else {
    Write-Step -Label "Apache" -Status "SKIP" -Detail "XAMPP non disponible"
}

$sw.Stop()
Write-Elapsed -Stopwatch $sw -Label "Etape 4"

# ============================================================
# ÉTAPE 5 — Base de donnees
# ============================================================
Write-Section -Number 5 -Title "Base de donnees" -Description "Verification et import de la base $DbName"
$sw = [System.Diagnostics.Stopwatch]::StartNew()

$dbOk = $false
if ($mysqlOk -and $portReady) {
    Write-Step -Label "Connexion a MySQL..." -Status "INFO"
    Write-Host "    -> Host : 127.0.0.1:$MysqlPort, User : root" -ForegroundColor DarkGray

    $dbExists = Test-DatabaseExists -XamppRoot $xamppRoot

    if ($dbExists -and $Force) {
        Write-Step -Label "Base de donnees" -Status "INFO" -Detail "Option -Force : suppression et reimportation de '$DbName'..."
        Invoke-MySqlQuery -Query "DROP DATABASE IF EXISTS $DbName" -XamppRoot $xamppRoot | Out-Null
        Write-Step -Label "Base de donnees" -Status "INFO" -Detail "Base '$DbName' supprimee"
        $dbExists = $false
    }

    if ($dbExists) {
        $tables = Get-DatabaseTables -XamppRoot $xamppRoot
        $tableList = if ($tables.Count -gt 0) { "$($tables.Count) tables" } else { "0 table" }
        Write-Step -Label "Base '$DbName'" -Status "OK" -Detail "Existe deja ($tableList)"
        $results += [PSCustomObject]@{ Etape = "Base de donnees"; Statut = "OK" }
        $dbOk = $true
    } elseif ($SkipDbImport) {
        Write-Step -Label "Base de donnees" -Status "SKIP" -Detail "Option -SkipDbImport activee"
        $results += [PSCustomObject]@{ Etape = "Base de donnees"; Statut = "SKIP" }
    } else {
        $importFile = $DbImportFile
        Write-Step -Label "Base '$DbName'" -Status "INFO" -Detail "Introuvable - importation en cours..."
        Write-Host "    -> Fichier source : $importFile" -ForegroundColor DarkGray
        if (-not (Test-Path $importFile)) {
            Write-Step -Label "Import" -Status "FAIL" -Detail "Fichier introuvable : $importFile"
            $results += [PSCustomObject]@{ Etape = "Base de donnees"; Statut = "FAIL" }
        } else {
            $fileSize = (Get-Item $importFile).Length / 1KB
            Write-Host "    -> Taille : $($fileSize.ToString('0.0')) Ko" -ForegroundColor DarkGray
            Write-Host "    -> Import en cours... (cela peut prendre quelques secondes)" -ForegroundColor DarkGray
            $mysql = Join-Path $xamppRoot "mysql\bin\mysql.exe"
            $importEscaped = "`"$mysql`""
            $fileEscaped = "`"$importFile`""
            $importResult = cmd /c "$importEscaped -u root -h 127.0.0.1 < $fileEscaped 2>&1"
            $importExitCode = $LASTEXITCODE

            if ($importExitCode -eq 0) {
                $tables = Get-DatabaseTables -XamppRoot $xamppRoot
                Write-Step -Label "Base '$DbName'" -Status "OK" -Detail "Importation reussie ($($tables.Count) tables creees)"
                $results += [PSCustomObject]@{ Etape = "Base de donnees"; Statut = "OK" }
                $dbOk = $true
            } else {
                Write-Step -Label "Base '$DbName'" -Status "FAIL" -Detail "Erreur d'import"
                if ($importResult) { Write-Host "    -> $importResult" -ForegroundColor Red }
                Write-Host "    -> Verifiez que le fichier $importFile est valide" -ForegroundColor Yellow
                $results += [PSCustomObject]@{ Etape = "Base de donnees"; Statut = "FAIL" }
            }
        }
    }
} else {
    Write-Step -Label "Base de donnees" -Status "SKIP" -Detail "MySQL non disponible"
    $results += [PSCustomObject]@{ Etape = "Base de donnees"; Statut = "SKIP" }
}

$sw.Stop()
    Write-Elapsed -Stopwatch $sw -Label "Etape 5"

# ============================================================
# ÉTAPE 6 — Verification des tables
# ============================================================
if ($dbOk) {
    Write-Section -Number 6 -Title "Tables" -Description "Verification de la structure de la base"
    $sw = [System.Diagnostics.Stopwatch]::StartNew()
    $tables = Get-DatabaseTables -XamppRoot $xamppRoot
    if ($tables.Count -gt 0) {
        Write-Step -Label "Tables" -Status "OK" -Detail "$($tables.Count) table(s) trouvee(s)"
        foreach ($tbl in $tables) {
            Write-Host "    -> $tbl" -ForegroundColor DarkGray
        }
        $results += [PSCustomObject]@{ Etape = "Tables verifiees"; Statut = "OK" }
    } else {
        Write-Step -Label "Tables" -Status "WARN" -Detail "Aucune table trouvee dans '$DbName'"
        $results += [PSCustomObject]@{ Etape = "Tables verifiees"; Statut = "WARN" }
    }
    $sw.Stop()
    Write-Elapsed -Stopwatch $sw -Label "Etape 6"
}

# ============================================================
# ÉTAPE 7 — PHP
# ============================================================
Write-Section -Number 7 -Title "PHP" -Description "Verification de la configuration PHP (extensions, version, connectivite)"
$sw = [System.Diagnostics.Stopwatch]::StartNew()

$phpExtensionsOk = $false
$phpVersionOk = $false
if ($xamppRoot) {
    $phpExe = Join-Path $xamppRoot "php\php.exe"
    $phpIni = Get-PhpIniPath -XamppRoot $xamppRoot

    # Version PHP
    if (Test-Path $phpExe) {
        $allLines = & $phpExe -v 2>&1
        $versionLine = $allLines | Select-String "PHP \d" | Select-Object -First 1 -ExpandProperty Line
        if (-not $versionLine) { $versionLine = $allLines | Select-Object -First 1 }
        if ($versionLine -match "PHP\s+(\d+\.\d+\.\d+)") {
            $phpVer = $matches[1]
            Write-Step -Label "Version PHP" -Status "OK" -Detail "$phpVer"
            $phpVersionOk = $true
        } elseif ($versionLine -match "PHP\s+(\d+\.\d+)") {
            $phpVer = $matches[1]
            Write-Step -Label "Version PHP" -Status "OK" -Detail "$phpVer"
            $phpVersionOk = $true
        } else {
            Write-Step -Label "Version PHP" -Status "OK" -Detail "$($versionLine.Trim())"
            $phpVersionOk = $true
        }
        $results += [PSCustomObject]@{ Etape = "Version PHP"; Statut = "OK" }
    } else {
        Write-Step -Label "Version PHP" -Status "WARN" -Detail "php.exe introuvable dans $xamppRoot"
        $results += [PSCustomObject]@{ Etape = "Version PHP"; Statut = "WARN" }
    }

    # Extensions
    if ($phpIni) {
        Write-Step -Label "php.ini" -Status "OK" -Detail "Trouve : $phpIni"
        $requiredExts = @("zip", "pdo_mysql", "mbstring", "curl", "openssl", "gd")
        $modified = @()
        $notFound = @()
        foreach ($ext in $requiredExts) {
            $result = Enable-PhpExtension -PhpIni $phpIni -Extension $ext
            switch ($result) {
                "already_enabled" { Write-Step -Label "Extension $ext" -Status "OK" -Detail "Deja active" }
                "activated" {
                    Write-Step -Label "Extension $ext" -Status "WARN" -Detail "Activee (etait commentee)"
                    $modified += $ext
                }
                "not_found" {
                    Write-Step -Label "Extension $ext" -Status "WARN" -Detail "Introuvable dans php.ini"
                    $notFound += $ext
                }
            }
        }
        if ($modified.Count -gt 0) {
            Write-Host "    -> Modifications apportees a php.ini : $($modified -join ', ')" -ForegroundColor Yellow
            Write-Host "    -> Redemarrez Apache pour appliquer les changements" -ForegroundColor Yellow
        }
        if ($notFound.Count -gt 0) {
            Write-Host "    -> Extensions non trouvees dans php.ini : $($notFound -join ', ')" -ForegroundColor DarkGray
        }
        if ($modified.Count -eq 0 -and $notFound.Count -eq 0) {
            Write-Step -Label "Extensions PHP" -Status "OK" -Detail "Toutes les extensions necessaires sont actives"
            $phpExtensionsOk = $true
        }
        $results += [PSCustomObject]@{ Etape = "Extensions PHP"; Statut = $(if ($notFound.Count -eq 0) { "OK" } else { "WARN" }) }
    } else {
        Write-Step -Label "php.ini" -Status "WARN" -Detail "Introuvable (les extensions n'ont pas ete verifiees)"
        $results += [PSCustomObject]@{ Etape = "Extensions PHP"; Statut = "WARN" }
    }

    # Test PDO
    if ($dbOk -and (Test-Path $phpExe)) {
        Write-Step -Label "Test de connexion PDO..." -Status "INFO"
        Write-Host "    -> Test : PHP -> PDO -> MySQL (127.0.0.1:$MysqlPort, db=$DbName)" -ForegroundColor DarkGray
        $phpTestCode = @'
<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=center_domiciliation;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "OK";
} catch (Exception $e) {
    echo "FAIL:" . $e->getMessage();
}
'@
        $tempFile = Join-Path $env:TEMP "pdo_test_$(Get-Random).php"
        Set-Content -Path $tempFile -Value $phpTestCode -Encoding UTF8
        $phpResult = & $phpExe -d "error_reporting=0" -f $tempFile 2>&1
        Remove-Item $tempFile -Force -ErrorAction SilentlyContinue

        if ($phpResult -match "OK") {
            Write-Step -Label "Connexion PDO" -Status "OK" -Detail "Connexion a MySQL reussie"
            $results += [PSCustomObject]@{ Etape = "Connexion PDO"; Statut = "OK" }
        } else {
            Write-Step -Label "Connexion PDO" -Status "FAIL" -Detail "Echec : $phpResult"
            $results += [PSCustomObject]@{ Etape = "Connexion PDO"; Statut = "FAIL" }
        }
    } elseif ($dbOk) {
        Write-Step -Label "Connexion PDO" -Status "SKIP" -Detail "php.exe introuvable"
        $results += [PSCustomObject]@{ Etape = "Connexion PDO"; Statut = "SKIP" }
    }
} else {
    Write-Step -Label "PHP" -Status "SKIP" -Detail "XAMPP non disponible"
    $results += [PSCustomObject]@{ Etape = "Version PHP"; Statut = "SKIP" }
    $results += [PSCustomObject]@{ Etape = "Extensions PHP"; Statut = "SKIP" }
    $results += [PSCustomObject]@{ Etape = "Connexion PDO"; Statut = "SKIP" }
}

$sw.Stop()
Write-Elapsed -Stopwatch $sw -Label "Etape 7"

# ============================================================
# ÉTAPE 8 — Structure du projet
# ============================================================
Write-Section -Number 8 -Title "Projet" -Description "Verification de la structure du projet"
$sw = [System.Diagnostics.Stopwatch]::StartNew()

$projectChecks = @(
    @{ Path = Join-Path $ProjectRoot "templates"; Name = "Dossier templates" },
    @{ Path = Join-Path $ProjectRoot "Outputs"; Name = "Dossier Outputs" },
    @{ Path = Join-Path $ProjectRoot "config\app.php"; Name = "config/app.php" },
    @{ Path = Join-Path $ProjectRoot "config\database.php"; Name = "config/database.php" },
    @{ Path = Join-Path $ProjectRoot "index.php"; Name = "index.php" },
    @{ Path = Join-Path $ProjectRoot "database\import.sql"; Name = "database/import.sql" }
)

$allProjectOk = $true
foreach ($check in $projectChecks) {
    $exists = Test-Path $check.Path
    if ($exists) {
        Write-Step -Label $check.Name -Status "OK"
    } else {
        Write-Step -Label $check.Name -Status "WARN" -Detail "Introuvable"
        $allProjectOk = $false
    }
    $results += [PSCustomObject]@{ Etape = $check.Name; Statut = $(if ($exists) { "OK" } else { "WARN" }) }
}

$sw.Stop()
Write-Elapsed -Stopwatch $sw -Label "Etape 8"

# ============================================================
# RAPPORT FINAL
# ============================================================
$globalSw.Stop()
$totalSeconds = [math]::Round($globalSw.Elapsed.TotalSeconds, 1)

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "              RAPPORT FINAL" -ForegroundColor Cyan
Write-Host "================================================" -ForegroundColor Cyan
Write-Host "  Temps d'execution total : $totalSeconds secondes" -ForegroundColor DarkGray
Write-Host ""

$totalOk = @($results | Where-Object { $_.Statut -eq "OK" }).Count
$totalWarn = @($results | Where-Object { $_.Statut -eq "WARN" }).Count
$totalFail = @($results | Where-Object { $_.Statut -eq "FAIL" }).Count
$totalSkip = @($results | Where-Object { $_.Statut -eq "SKIP" }).Count
$total = $results.Count

foreach ($r in $results) {
    $color = switch ($r.Statut) {
        "OK"   { "Green" }
        "WARN" { "Yellow" }
        "FAIL" { "Red" }
        "SKIP" { "DarkGray" }
    }
    Write-Host "  [$($r.Statut)] $($r.Etape)" -ForegroundColor $color
}

$hasFail = $totalFail -and ($totalFail -gt 0)
$hasWarn = $totalWarn -and ($totalWarn -gt 0)

Write-Host ""
Write-Host "  Bilan : $totalOk OK / $totalWarn alerte(s) / $totalFail erreur(s) / $totalSkip ignore(s)" -ForegroundColor $(if ($hasFail) { "Red" } elseif ($hasWarn) { "Yellow" } else { "Green" })
Write-Host ""

# Recommandations personnalisees
if ($hasFail) {
    Write-Host "  Recommandations :" -ForegroundColor Yellow
    $failSteps = @($results | Where-Object { $_.Statut -eq "FAIL" })
    foreach ($step in $failSteps) {
        switch -Wildcard ($step.Etape) {
            "XAMPP*" {
                Write-Host "    - XAMPP :" -ForegroundColor Yellow
                Write-Host "      * Installez-le manuellement : https://www.apachefriends.org/fr/download.html" -ForegroundColor Yellow
                Write-Host "      * Ou relancez le script en administrateur pour installation auto" -ForegroundColor Yellow
            }
            "MySQL*" {
                Write-Host "    - MySQL :" -ForegroundColor Yellow
                Write-Host "      * Verifiez le port 3306 : netstat -ano | findstr :3306" -ForegroundColor Yellow
                Write-Host "      * Si bloque par un autre processus :" -ForegroundColor Yellow
                Write-Host "        Stop-Process -Id (PID) -Force" -ForegroundColor Yellow
                Write-Host "      * Consultez les logs : C:\xampp\mysql\data\*.err" -ForegroundColor Yellow
            }
            "Apache*" {
                Write-Host "    - Apache :" -ForegroundColor Yellow
                Write-Host "      * Ports 80/443 bloques ? trouvez le processus :" -ForegroundColor Yellow
                Write-Host "        netstat -ano | findstr :80" -ForegroundColor Yellow
                Write-Host "        netstat -ano | findstr :443" -ForegroundColor Yellow
                Write-Host "      * Arreter le processus bloquant :" -ForegroundColor Yellow
                Write-Host "        Stop-Process -Id (PID) -Force" -ForegroundColor Yellow
                Write-Host "      * Ou changer le port Apache dans httpd.conf" -ForegroundColor Yellow
            }
            "Base*" {
                Write-Host "    - Base de donnees :" -ForegroundColor Yellow
                Write-Host "      * Verifiez que database\import.sql est present et valide" -ForegroundColor Yellow
                Write-Host "      * Import manuel : mysql -u root < database\import.sql" -ForegroundColor Yellow
            }
            "Connexion*" {
                Write-Host "    - Connexion PDO :" -ForegroundColor Yellow
                Write-Host "      * Verifiez que MySQL est en cours d'execution" -ForegroundColor Yellow
                Write-Host "      * Verifiez les identifiants dans config\database.php" -ForegroundColor Yellow
            }
            default {
                Write-Host "    - $($step.Etape) : corrigez l'erreur et relancez" -ForegroundColor Yellow
            }
        }
    }
    Write-Host ""
    Write-Host "  Le script est idempotent : vous pouvez le relancer sans risque apres correction." -ForegroundColor Yellow
    $exitCode = 1
} else {
    Write-Host "  Toutes les verifications sont passees." -ForegroundColor Green
    Write-Host "  -> Application prete : http://localhost/index.php?page=accueil" -ForegroundColor Green
    Write-Host "  -> Ouverture du navigateur..." -ForegroundColor Green
    $appUrl = "http://localhost/index.php?page=accueil"
    try {
        Start-Process $appUrl
    } catch {
        Write-Step -Label "Navigateur" -Status "WARN" -Detail "Impossible d'ouvrir le navigateur automatiquement"
    }
    Write-Host ""
    Write-Host "  Pour information :" -ForegroundColor DarkGray
    Write-Host "    - Page d'accueil : /index.php?page=accueil" -ForegroundColor DarkGray
    Write-Host "    - Dossiers clients : /index.php?page=societes" -ForegroundColor DarkGray
    Write-Host "    - Configuration : /index.php?page=configuration" -ForegroundColor DarkGray
    Write-Host "    - Templates DOCX : /index.php?page=analyse-couverture" -ForegroundColor DarkGray
}

Write-Host ""
Write-Host "================================================" -ForegroundColor Cyan

exit $exitCode


