$url = "http://localhost/Center-Domiciliation-App/"
$apache = "C:\xampp\apache\bin\httpd.exe"
$mysql = "C:\xampp\mysql\bin\mysqld.exe"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Center Domiciliation - XAMPP Launcher" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# MySQL
$mysqlRunning = Get-Process -Name "mysqld" -ErrorAction SilentlyContinue
if (-not $mysqlRunning) {
    Write-Host "[MySQL] Demarrage..." -ForegroundColor Yellow
    Start-Process -FilePath $mysql -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Write-Host "[MySQL] Ok" -ForegroundColor Green
} else {
    Write-Host "[MySQL] Deja en cours" -ForegroundColor Green
}

# Apache
$apacheRunning = Get-Process -Name "httpd" -ErrorAction SilentlyContinue
if (-not $apacheRunning) {
    Write-Host "[Apache] Demarrage..." -ForegroundColor Yellow
    Start-Process -FilePath $apache -WindowStyle Hidden
    Start-Sleep -Seconds 3
    Write-Host "[Apache] Ok" -ForegroundColor Green
} else {
    Write-Host "[Apache] Deja en cours" -ForegroundColor Green
}

Write-Host ""
Write-Host "Application ouverte : $url" -ForegroundColor Green
Start-Process $url

Write-Host ""
Write-Host "Les services XAMPP tournent en arriere-plan." -ForegroundColor Cyan
Write-Host "Ferme cette fenetre sans souci, ils restent actifs." -ForegroundColor Cyan
Write-Host ""
Write-Host "Pour les arreter :" -ForegroundColor Yellow
Write-Host "  taskkill /f /im httpd.exe" -ForegroundColor White
Write-Host "  taskkill /f /im mysqld.exe" -ForegroundColor White
Write-Host ""
pause
