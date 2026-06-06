# Lance Chrome avec le remote debugging pour chrome-devtools-mcp
$chrome = @(
    "${env:ProgramFiles}\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
    "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe"
)

$chromePath = $chrome | Where-Object { Test-Path $_ } | Select-Object -First 1

if (-not $chromePath) {
    Write-Host "[ERREUR] Chrome introuvable" -ForegroundColor Red
    exit 1
}

$userData = Join-Path $env:TEMP "chrome-debug-profile"

Write-Host "Demarrage de Chrome (debug port 9222)..." -ForegroundColor Yellow
Start-Process -FilePath $chromePath -ArgumentList @(
    "--remote-debugging-port=9222",
    "--user-data-dir=`"$userData`""
) -WindowStyle Normal

Write-Host "Chrome lance sur ws://localhost:9222" -ForegroundColor Green
