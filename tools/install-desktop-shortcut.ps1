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
$stopScript  = Join-Path $projectRoot 'tools\stop-app.ps1'

if (-not (Test-Path $iconPath)) {
    Write-Error "Icon not found at $iconPath"
    exit 1
}
if (-not (Test-Path $runScript)) {
    Write-Error "run-app.ps1 not found at $runScript"
    exit 1
}

function New-AppShortcut {
    param(
        [string]$Name,
        [string]$ScriptPath,
        [string]$Description
    )

    $lnkPath = Join-Path $desktop "$Name.lnk"
    $shell   = New-Object -ComObject WScript.Shell
    $sc      = $shell.CreateShortcut($lnkPath)

    $sc.TargetPath       = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
    $sc.Arguments        = "-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File `"$ScriptPath`""
    $sc.IconLocation     = $iconPath
    $sc.WorkingDirectory = $projectRoot
    $sc.Description      = $Description
    $sc.Save()

    # Mark the .lnk as "Run as user" only (no admin elevation).
    # The default WScript.Shell shortcut already does this - nothing extra.

    Write-Host "  [ok] $lnkPath"
}

Write-Host "Creating desktop shortcuts for Gfonts Browser..."
New-AppShortcut -Name 'Gfonts Browser' `
                -ScriptPath $runScript `
                -Description 'Gfonts Browser - open in browser'

New-AppShortcut -Name 'Gfonts Browser (stop)' `
                -ScriptPath $stopScript `
                -Description 'Stop the Gfonts Browser server'

Write-Host ""
Write-Host "Done. Two shortcuts placed on the Desktop:"
Write-Host "  - Gfonts Browser         starts the server and opens http://localhost:8000"
Write-Host "  - Gfonts Browser (stop)  stops the server"
Write-Host ""
Write-Host "Icon: $iconPath"
