@echo off
setlocal

REM --- Real push ---
"C:\Program Files\Git\bin\git.exe" push %*
if %ERRORLEVEL% neq 0 exit /b %ERRORLEVEL%

REM --- Auto-export DB ---
powershell.exe -ExecutionPolicy Bypass -NoProfile -File "%~dp0post-push-sync.ps1"
