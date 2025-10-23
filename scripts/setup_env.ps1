<#
Setup script for Windows PowerShell
Creates a venv in ./venv, activates it, upgrades pip and installs dependencies
Usage (from repo root):
  powershell -ExecutionPolicy Bypass -File .\scripts\setup_env.ps1
Or run the commands interactively inside PowerShell.
#>

param(
  [switch]$Recreate,
  [string]$Python = "python",
  [switch]$NoInstall
)

$ErrorActionPreference = 'Stop'

Write-Host "== Setup environment (Windows PowerShell) =="

# Detect python
try {
  & $Python --version | Out-Null
}
catch {
  Write-Error "Python not found. Please install Python 3.10+ and ensure it's on PATH or pass -Python 'C:\\path\\to\\python.exe'"
  exit 1
}

$venvPath = Join-Path -Path (Get-Location) -ChildPath 'venv'

if (Test-Path $venvPath) {
  if ($Recreate) {
    Write-Host "Removing existing venv..."
    Remove-Item -Recurse -Force $venvPath
  }
  else {
    Write-Host "Virtual environment already exists at $venvPath"
  }
}

if (-not (Test-Path $venvPath)) {
  Write-Host "Creating virtual environment..."
  & $Python -m venv venv
}

Write-Host "Activating virtual environment..."
$activate = Join-Path $venvPath 'Scripts\Activate.ps1'
Write-Host "To activate the venv in this shell run:`n  & $activate"

if ($NoInstall) {
  Write-Host "Skipping dependency installation (-NoInstall passed)."
  exit 0
}

Write-Host "Upgrading pip and installing dependencies..."
$pip = Join-Path $venvPath 'Scripts\pip.exe'
if (-not (Test-Path $pip)) {
  Write-Error "pip not found inside venv. Ensure venv was created correctly."
  exit 1
}

# prefer Windows requirements file when present
$reqFile = Join-Path (Get-Location) 'requirements-windows.txt'
if (-not (Test-Path $reqFile)) {
  $reqFile = Join-Path (Get-Location) 'requirements.txt'
}

Write-Host "Using requirements file: $reqFile"
& $pip install --upgrade pip
& $pip install -r $reqFile

Write-Host "Setup complete. Activate the venv with:`n  & $activate"
