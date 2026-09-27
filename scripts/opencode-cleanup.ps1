<#
    Nettoyage des serveurs opencode CLI orphelins ( lancés par l'extension VS Code ).

    L'extension sst-dev.opencode lance `opencode --port <aleatoire>` dans un terminal
    integre et ne tue jamais le process (deactivate() est vide). A la fermeture de
    VS Code, le process peut survivre avec son pool de sockets keep-alive mortes,
    ce qui provoque des erreurs "Cannot connect to API: The socket connection was
    closed unexpectedly" sur les sessions suivantes.

    Ce script ne touche QUE les binaires CLI (opencode-ai\bin\opencode.exe).
    L'app desktop (OpenCode.exe) n'est jamais concernee.

    Un serveur est considere orphelin si son port n'a plus aucune connexion
    client etablie : plus personne ne dialogue avec lui.

    Usage :
        powershell -ExecutionPolicy Bypass -File .\scripts\opencode-cleanup.ps1
        powershell -ExecutionPolicy Bypass -File .\scripts\opencode-cleanup.ps1 -Force
        powershell -ExecutionPolicy Bypass -File .\scripts\opencode-cleanup.ps1 -DryRun
#>

[CmdletBinding()]
param(
    # Tue tous les serveurs CLI, meme ceux qui ont un client connecte.
    [switch]$Force,

    # Affiche ce qui serait tue sans rien arreter.
    [switch]$DryRun
)

$ErrorActionPreference = 'Stop'

function Get-CliOpenCodeServers {
    $marker = 'opencode-ai' + [IO.Path]::DirectorySeparatorChar + 'bin'
    Get-CimInstance Win32_Process -Filter "Name='opencode.exe'" -ErrorAction SilentlyContinue |
        Where-Object {
            $_.ExecutablePath -and
            $_.ExecutablePath.IndexOf($marker, [StringComparison]::OrdinalIgnoreCase) -ge 0
        }
}

function Get-ServerPort {
    param([int]$ProcessId)

    $listener = Get-NetTCPConnection -State Listen -OwningProcess $ProcessId -ErrorAction SilentlyContinue |
        Where-Object { $_.LocalAddress -in @('127.0.0.1', '0.0.0.0', '::1', '::') } |
        Select-Object -First 1

    if ($null -eq $listener) { return $null }
    return $listener.LocalPort
}

function Test-ServerHasClient {
    param([int]$ProcessId, [int]$Port)

    $established = Get-NetTCPConnection -State Established -ErrorAction SilentlyContinue |
        Where-Object { $_.RemotePort -eq $Port -or $_.LocalPort -eq $Port }

    if ($null -eq $established) { return $false }
    return (@($established).Count -gt 0)
}

$servers = @(Get-CliOpenCodeServers)

if ($servers.Count -eq 0) {
    Write-Output '[opencode-cleanup] Aucun serveur opencode CLI en cours.'
    exit 0
}

$killed = 0
$kept   = 0

foreach ($server in $servers) {
    $port = Get-ServerPort -ProcessId $server.ProcessId
    $label = "PID $($server.ProcessId)"

    if ($null -ne $port) {
        $label += " port $port"
    } else {
        $label += ' (aucun port)'
    }

    $hasClient = ($null -ne $port) -and (Test-ServerHasClient -ProcessId $server.ProcessId -Port $port)

    if ($hasClient -and -not $Force) {
        Write-Output "[opencode-cleanup] Conserve  $label - client connecte"
        $kept++
        continue
    }

    $reason = if ($hasClient) { 'Force' } else { 'orphelin' }
    Write-Output "[opencode-cleanup] $(if ($DryRun) { 'A tuer    ' } else { 'Tue       ' }) $label - $reason"

    if (-not $DryRun) {
        try {
            Stop-Process -Id $server.ProcessId -Force -ErrorAction Stop
            $killed++
        } catch {
            Write-Warning "[opencode-cleanup] Echec sur $label : $($_.Exception.Message)"
        }
    } else {
        $killed++
    }
}

$action = if ($DryRun) { 'a tuer' } else { 'tues' }
Write-Output "[opencode-cleanup] Termine : $killed $action, $kept conserves."
