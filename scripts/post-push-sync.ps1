$ProjectRoot = Split-Path $PSScriptRoot -Parent
. "$PSScriptRoot\_env.ps1"
$XamppPath = "C:\xampp"
$Db = $env:DB_NAME
$Dir = Join-Path $ProjectRoot "database\exports"
$Mysqldump = Join-Path $XamppPath "mysql\bin\mysqldump.exe"

if (-not (Test-Path $Dir)) { New-Item -ItemType Directory -Path $Dir -Force | Out-Null }
$f = Join-Path $Dir "${Db}_$(Get-Date -Format 'yyyy-MM-dd_HHmmss').sql"
& $Mysqldump -u $env:DB_USERNAME $(if ($env:DB_PASSWORD) { "--password=$env:DB_PASSWORD" }) --no-create-info --complete-insert --skip-extended-insert $Db 2>&1 | Out-File $f -Encoding UTF8

if ($LASTEXITCODE -eq 0 -and (Test-Path $f)) {
    $size = [math]::Round((Get-Item $f).Length / 1KB, 1)
    Write-Host "[Sync] DB exportee: $size KB" -ForegroundColor Green
    Push-Location $ProjectRoot
    & git add "database/exports/" 2>&1 | Out-Null
    & git commit -m "sync: DB dump $(Split-Path $f -Leaf)" 2>&1 | Out-Null
    Pop-Location
    Write-Host "[Sync] Commit OK" -ForegroundColor Green
} else {
    Write-Host "[Sync] Echec export" -ForegroundColor Red
}
