# Per-user font installer. No admin required.
# Copies the .ttf to %LOCALAPPDATA%\Microsoft\Windows\Fonts and registers it
# under HKCU so it appears in Word, Photoshop, etc.
#
# Usage: powershell -NoProfile -ExecutionPolicy Bypass -File install_font.ps1 -Path "C:\path\to\font.ttf"
# Output (stdout): one of OK | ALREADY  (anything else = error)
# Exit code: 0 on success, non-zero on failure.

param(
    [Parameter(Mandatory=$true)]
    [string]$Path
)

$ErrorActionPreference = 'Stop'

try {
    if (-not (Test-Path -LiteralPath $Path)) {
        Write-Error "Source file not found: $Path"
        exit 2
    }

    $file = Get-Item -LiteralPath $Path
    $filename = $file.Name
    $base = $file.BaseName

    $userFontsDir = Join-Path $env:LOCALAPPDATA 'Microsoft\Windows\Fonts'
    if (-not (Test-Path -LiteralPath $userFontsDir)) {
        New-Item -ItemType Directory -Path $userFontsDir -Force | Out-Null
    }

    $destPath = Join-Path $userFontsDir $filename

    if (Test-Path -LiteralPath $destPath) {
        Write-Output 'ALREADY'
        exit 0
    }

    Copy-Item -LiteralPath $Path -Destination $destPath -Force

    $regKey = 'HKCU:\SOFTWARE\Microsoft\Windows NT\CurrentVersion\Fonts'
    if (-not (Test-Path -LiteralPath $regKey)) {
        New-Item -Path $regKey -Force | Out-Null
    }
    $fontName = "$base (TrueType)"
    New-ItemProperty -Path $regKey -Name $fontName -Value $destPath -PropertyType String -Force | Out-Null

    # Make the font immediately available to running apps without a restart:
    # AddFontResource loads it into this session and WM_FONTCHANGE broadcasts
    # the change so other top-level windows refresh their font list.
    $sig = @'
[DllImport("gdi32.dll", CharSet=CharSet.Auto)]
public static extern int AddFontResource(string lpszFilename);
[DllImport("user32.dll", CharSet=CharSet.Auto)]
public static extern IntPtr SendMessageTimeout(IntPtr hWnd, uint Msg, IntPtr wParam, IntPtr lParam, uint fuFlags, uint uTimeout, out IntPtr lpdwResult);
'@
    $type = Add-Type -MemberDefinition $sig -Name 'GfontsFontHelper' -Namespace 'Win32' -PassThru -ErrorAction SilentlyContinue
    if ($type) {
        $null = $type::AddFontResource($destPath)
        $HWND_BROADCAST = [IntPtr]0xFFFF
        $WM_FONTCHANGE  = 0x001D
        $SMTO_ABORTIFHUNG = 0x0002
        $result = [IntPtr]::Zero
        $null = $type::SendMessageTimeout($HWND_BROADCAST, $WM_FONTCHANGE, [IntPtr]::Zero, [IntPtr]::Zero, $SMTO_ABORTIFHUNG, 1000, [ref]$result)
    }

    Write-Output 'OK'
    exit 0
}
catch {
    Write-Error $_.Exception.Message
    exit 1
}
