# Nettoie toutes les instances opencode (app desktop + CLI serveur) et les serveurs MCP dupliques.
# A lancer depuis un terminal PowerShell/Windows Terminal HORS opencode (sinon le script se tue lui-meme a la fin).
# Usage :
#   powershell -ExecutionPolicy Bypass -File .\scripts\cleanup-opencode.ps1
# Ensuite : relancer opencode dans VSCode (palette -> "OpenCode: Restart") ou l'app desktop.

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Continue'

# Patterns de commandes des serveurs MCP (node) appartenant a opencode
$mcpPatterns = @(
    'chrome-devtools-mcp',
    '@modelcontextprotocol',
    'server-memory',
    '@berthojoris',
    'mysql-mcp',
    'opencode'
)

function Get-ProcessCmdLine {
    param([int]$ProcessId)
    try {
        return (Get-CimInstance Win32_Process -Filter "ProcessId=$ProcessId").CommandLine
    } catch {
        return $null
    }
}

Write-Host "== Nettoyage des instances opencode ==" -ForegroundColor Cyan

# 1) Recuperer les processus cibles
$all = Get-Process -ErrorAction SilentlyContinue
$targets = @()

foreach ($p in $all) {
    if ($p.ProcessName -eq 'OpenCode' -or $p.ProcessName -eq 'opencode') {
        $targets += $p
    }
    elseif ($p.ProcessName -eq 'node') {
        $cmd = Get-ProcessCmdLine -ProcessId $p.Id
        if ($cmd -and ($mcpPatterns | Where-Object { $cmd -match $_ })) {
            $targets += $p
        }
    }
}

$targets = $targets | Sort-Object Id -Unique

if ($targets.Count -eq 0) {
    Write-Host "[OK] Aucun processus opencode/MCP actif, rien a tuer." -ForegroundColor Green
    exit 0
}

Write-Host "Processus trouves ($($targets.Count)) :" -ForegroundColor Yellow
foreach ($t in $targets) {
    $cmd = Get-ProcessCmdLine -ProcessId $t.Id
    $label = if ($cmd) { $cmd.Substring(0, [Math]::Min(110, $cmd.Length)) } else { $t.ProcessName }
    Write-Host ("  PID {0,-6} {1,-9} {2}" -f $t.Id, $t.ProcessName, $label)
}

# 2) Arret en 3 temps : MCP node d'abord, CLI opencode ensuite, app desktop en dernier
Write-Host "`nArret des serveurs MCP (node)..." -ForegroundColor Yellow
$targets | Where-Object { $_.ProcessName -eq 'node' } | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Milliseconds 500

Write-Host "Arret des serveurs CLI opencode..." -ForegroundColor Yellow
$targets | Where-Object { $_.ProcessName -eq 'opencode' } | Stop-Process -Force -ErrorAction SilentlyContinue
Start-Sleep -Milliseconds 500

Write-Host "Arret de l'app desktop OpenCode..." -ForegroundColor Yellow
$targets | Where-Object { $_.ProcessName -eq 'OpenCode' } | Stop-Process -Force -ErrorAction SilentlyContinue

Start-Sleep -Seconds 2

# 3) Verification
Write-Host "`nVerification..." -ForegroundColor Cyan
$remaining = @(Get-Process -ErrorAction SilentlyContinue | Where-Object {
    $_.ProcessName -eq 'OpenCode' -or $_.ProcessName -eq 'opencode' -or
    ($_.ProcessName -eq 'node' -and (Get-ProcessCmdLine -ProcessId $_.Id) -match 'opencode|mysql-mcp|berthojoris|chrome-devtools|server-memory')
})
Write-Host "Instances restantes : $($remaining.Count)" -ForegroundColor $(if ($remaining.Count -gt 0) { 'Red' } else { 'Green' })
if ($remaining.Count -gt 0) {
    $remaining | ForEach-Object { Write-Host ("  PID {0} {1}" -f $_.Id, $_.ProcessName) }
    Write-Host "-> Tuez-les manuellement si besoin (Task Manager)." -ForegroundColor Yellow
} else {
    Write-Host "[OK] Environnement propre." -ForegroundColor Green
}

# 4) Renseignement de relance (n'utilisez qu'UNE seule interface a la fois)
Write-Host "`nRelance (une seule interface a la fois) :" -ForegroundColor Cyan
Write-Host "  - VSCode   : palette de commandes -> \"OpenCode: Restart\"  (redemarre aussi les serveurs MCP)"
Write-Host "  - Desktop  : relancez l'app OpenCode desktop"