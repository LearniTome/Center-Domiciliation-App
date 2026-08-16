# ==========================================
#  Chargeur .env pour scripts PowerShell
#  Lecture des variables de .env / .env.local
#  Usage : . "$PSScriptRoot\_env.ps1"
# ==========================================

if (-not $ProjectRoot) {
    $ProjectRoot = Split-Path $PSScriptRoot -Parent
}

function Read-EnvValue {
    param([string]$Key, [string]$Default = "")

    $existing = Get-ChildItem Env: -ErrorAction SilentlyContinue | Where-Object { $_.Name -eq $Key }
    if ($existing -and $existing.Value -ne "") { return $existing.Value }

    foreach ($file in @((Join-Path $ProjectRoot ".env.local"), (Join-Path $ProjectRoot ".env"))) {
        if (-not (Test-Path $file)) { continue }
        $line = Get-Content $file -Raw -ErrorAction SilentlyContinue
        if ($line) {
            foreach ($l in ($line -split "`n")) {
                $l = $l.Trim()
                if ($l -eq "" -or $l.StartsWith("#")) { continue }
                $idx = $l.IndexOf("=")
                if ($idx -le 0) { continue }
                $k = $l.Substring(0, $idx).Trim()
                if ($k -ne $Key) { continue }
                $v = $l.Substring($idx + 1).Trim()
                $v = $v.Trim('"').Trim("'")
                return $v
            }
        }
    }
    return $Default
}

$env:DB_HOST = Read-EnvValue "DB_HOST" "127.0.0.1"
$env:DB_PORT = Read-EnvValue "DB_PORT" "3306"
$env:DB_NAME = Read-EnvValue "DB_NAME" "center_domiciliation"
$env:DB_USERNAME = Read-EnvValue "DB_USERNAME" "root"
$env:DB_PASSWORD = Read-EnvValue "DB_PASSWORD" ""
$env:DB_CHARSET = Read-EnvValue "DB_CHARSET" "utf8mb4"
$env:ANTHROPIC_API_KEY = Read-EnvValue "ANTHROPIC_API_KEY" ""
