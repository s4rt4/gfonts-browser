# Creates a desktop shortcut that launches Gfonts Browser
# (server + browser) with the app icon.
#
# Usage: powershell -File tools\install-desktop-shortcut.ps1
#        or right-click in File Explorer -> Run with PowerShell

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$desktop     = [Environment]::GetFolderPath('Desktop')
$iconPath    = Join-Path $projectRoot 'public\img\google-fonts-logo.ico'
$runScript   = Join-Path $projectRoot 'tools\run-app.ps1'
$lnkPath     = Join-Path $desktop 'Gfonts Browser.lnk'
$staleStopLnk = Join-Path $desktop 'Gfonts Browser (stop).lnk'

if (-not (Test-Path $iconPath))  { Write-Error "Icon not found at $iconPath";    exit 1 }
if (-not (Test-Path $runScript)) { Write-Error "run-app.ps1 not found at $runScript"; exit 1 }

# Clean up legacy "stop" shortcut from earlier installer versions
if (Test-Path $staleStopLnk) {
    Remove-Item $staleStopLnk -Force
    Write-Host "  [removed] stale 'Gfonts Browser (stop)' shortcut"
}

$shell = New-Object -ComObject WScript.Shell
$sc    = $shell.CreateShortcut($lnkPath)

$sc.TargetPath       = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
$sc.Arguments        = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$runScript`""
$sc.IconLocation     = $iconPath
$sc.WorkingDirectory = $projectRoot
$sc.Description      = 'Gfonts Browser - open in browser'
$sc.Save()

Write-Host "  [ok] $lnkPath"
Write-Host ""
Write-Host "Done. Shortcut placed on the Desktop:"
Write-Host "  Gfonts Browser  - starts the server and opens http://localhost:9000"
Write-Host ""
Write-Host "To stop the server later (rare):"
Write-Host "  powershell -File tools\stop-app.ps1"
Write-Host ""
Write-Host "Icon: $iconPath"
