# Starts the Gfonts Browser dev server and opens the browser.
# Safe to run twice - if server is already up, just opens the browser.
#
# Usage (from anywhere): powershell -File tools\run-app.ps1
# Or via desktop shortcut created by tools/install-desktop-shortcut.ps1

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$port        = 8000
$url         = "http://127.0.0.1:$port"
$pidFile     = Join-Path $projectRoot '.app-pid'

function Test-PortInUse {
    param([int]$Port)
    $client = New-Object System.Net.Sockets.TcpClient
    try {
        $async = $client.BeginConnect('127.0.0.1', $Port, $null, $null)
        $ok    = $async.AsyncWaitHandle.WaitOne(400)
        if ($ok -and $client.Connected) { $client.EndConnect($async); return $true }
        return $false
    } catch { return $false } finally { $client.Close() }
}

# 1. If already running, just open the browser
if (Test-PortInUse -Port $port) {
    Write-Host "Server already running at $url - opening browser."
    Start-Process $url
    exit 0
}

# 2. Sanity check - find php
$php = Get-Command php -ErrorAction SilentlyContinue
if (-not $php) {
    # Try common Laragon path
    $candidates = Get-ChildItem 'C:\laragon\bin\php\*\php.exe' -ErrorAction SilentlyContinue |
                  Sort-Object FullName -Descending
    if ($candidates) {
        $phpExe = $candidates[0].FullName
    } else {
        [System.Windows.Forms.MessageBox]::Show(
            "PHP not found on PATH or in C:\laragon\bin\php\.`nInstall Laragon (or add php.exe to PATH) and try again.",
            "Gfonts Browser", 0, 16
        ) | Out-Null
        exit 1
    }
} else {
    $phpExe = $php.Source
}

# 3. Start php artisan serve hidden
Push-Location $projectRoot
try {
    $proc = Start-Process -FilePath $phpExe `
        -ArgumentList @('artisan', 'serve', "--host=127.0.0.1", "--port=$port") `
        -WindowStyle Hidden `
        -PassThru
    $proc.Id | Out-File -FilePath $pidFile -Encoding ASCII -Force

    # 4. Wait for server to be reachable (max 15s)
    $waited = 0
    while ($waited -lt 15) {
        Start-Sleep -Milliseconds 400
        $waited += 0.4
        if (Test-PortInUse -Port $port) { break }
    }

    if (-not (Test-PortInUse -Port $port)) {
        [System.Reflection.Assembly]::LoadWithPartialName('System.Windows.Forms') | Out-Null
        [System.Windows.Forms.MessageBox]::Show(
            "Server didn't come up within 15 seconds.`n`nCheck the project folder, then try `tools\run-app.ps1` from a normal terminal to see the error.",
            "Gfonts Browser", 0, 48
        ) | Out-Null
        exit 1
    }

    # 5. Open the browser
    Start-Process $url
}
finally {
    Pop-Location
}
