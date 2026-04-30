# Stops the Gfonts Browser dev server (kills the php artisan serve
# process we spawned via run-app.ps1).
#
# Usage: powershell -File tools\stop-app.ps1

$ErrorActionPreference = 'SilentlyContinue'

$projectRoot = Split-Path -Parent $PSScriptRoot
$pidFile     = Join-Path $projectRoot '.app-pid'

if (-not (Test-Path $pidFile)) {
    Write-Host "No running server tracked (no .app-pid file)."
    Write-Host "If a server is still up, find it manually:"
    Write-Host "  Get-Process php | Where-Object { `$_.MainWindowTitle -or `$_.CommandLine -match 'artisan serve' }"
    exit 0
}

$tracked = Get-Content $pidFile | Select-Object -First 1
if (-not $tracked) {
    Remove-Item $pidFile -Force
    exit 0
}

$proc = Get-Process -Id $tracked -ErrorAction SilentlyContinue
if ($proc) {
    Write-Host "Stopping server (PID $tracked)..."
    Stop-Process -Id $tracked -Force
    Write-Host "Stopped."
} else {
    Write-Host "Process $tracked is no longer running. Cleaning up pid file."
}

Remove-Item $pidFile -Force
