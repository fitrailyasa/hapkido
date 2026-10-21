#Requires -Version 5.1
<#
.SYNOPSIS
  Package the Laravel 11 app into a portable Windows folder + optional installer EXE.

.DESCRIPTION
  1. Builds frontend assets (Vite)
  2. Downloads PHP 8.3 NTS x64 (cached)
  3. Stages a production copy (composer --no-dev, SQLite-ready)
  4. Compiles Hapkido.exe launcher
  5. Optionally builds Setup-Hapkido.exe with Inno Setup 6

.EXAMPLE
  .\tools\build-exe\package.ps1

.EXAMPLE
  .\tools\build-exe\package.ps1 -SkipInstaller
#>
[CmdletBinding()]
param(
    [switch] $SkipNpm,
    [switch] $SkipInstaller,
    [switch] $SkipPhpDownload,
    [string] $InnoSetupCompiler = ""
)

$ErrorActionPreference = "Stop"

function Write-Step([string] $Message) {
    Write-Host ""
    Write-Host "==> $Message" -ForegroundColor Cyan
}

function Assert-Command([string] $Name) {
    if (-not (Get-Command $Name -ErrorAction SilentlyContinue)) {
        throw "Perintah '$Name' tidak ditemukan di PATH. Install dulu, lalu jalankan ulang."
    }
}

function Invoke-Native([string] $File, [string[]] $Arguments, [string] $WorkingDirectory = "") {
    $argsText = if ($Arguments) { $Arguments -join " " } else { "" }
    Write-Host "    $File $argsText"

    $previous = Get-Location
    if ($WorkingDirectory) {
        Set-Location $WorkingDirectory
    }

    try {
        & $File @Arguments
        if ($LASTEXITCODE -ne 0 -and $null -ne $LASTEXITCODE) {
            throw "Perintah gagal ($LASTEXITCODE): $File $argsText"
        }
    }
    finally {
        Set-Location $previous
    }
}

function Get-RepoRoot {
    return (Resolve-Path (Join-Path $PSScriptRoot "..\..")).Path
}

function Ensure-Dir([string] $Path) {
    if (-not (Test-Path $Path)) {
        New-Item -ItemType Directory -Path $Path | Out-Null
    }
}

function Clear-Dir([string] $Path) {
    if (Test-Path $Path) {
        Remove-Item -Recurse -Force $Path
    }
    Ensure-Dir $Path
}

function Copy-AppFiles([string] $Source, [string] $Destination) {
    $excludeDirs = @(
        ".git", ".github", ".idea", ".vscode", ".fleet",
        "node_modules", "vendor", "tests", "docker",
        "tools\build-exe\cache", "tools\build-exe\dist",
        "storage\logs", "storage\framework\cache", "storage\framework\sessions",
        "storage\framework\views", "bootstrap\cache"
    )

    $excludeFiles = @(
        ".env", ".env.backup", ".env.docker", ".env.production",
        "phpunit.xml", "phpunit.xml.dist",
        "auth.json", "Homestead.json", "Homestead.yaml",
        "npm-debug.log", "yarn-error.log", "composer.lock.bak"
    )

    Ensure-Dir $Destination

    # /E copy subdirs including empty; /XD /XF exclude; /NFL /NDL quieter; /NJH /NJS no header/summary noise optional
    $xd = @()
    foreach ($dir in $excludeDirs) {
        $xd += $dir
    }

    $xf = @("*.sqlite", "*.log") + $excludeFiles

    $robocopyArgs = @(
        $Source, $Destination, "/E", "/R:1", "/W:1",
        "/NFL", "/NDL", "/NJH", "/NJS", "/NP",
        "/XD"
    ) + $xd + @("/XF") + $xf

    & robocopy @robocopyArgs | Out-Null
    $code = $LASTEXITCODE
    # Robocopy: 0-7 = success with various copy states; >=8 = failure
    if ($code -ge 8) {
        throw "Robocopy gagal (exit $code) saat menyalin aplikasi ke staging."
    }
}

function Build-Launcher([string] $LauncherDir, [string] $OutputExe) {
    Write-Step "Compile launcher Hapkido.exe"

    Ensure-Dir (Split-Path -Parent $OutputExe)

    $cscCandidates = @(
        "${env:WINDIR}\Microsoft.NET\Framework64\v4.0.30319\csc.exe",
        "${env:WINDIR}\Microsoft.NET\Framework\v4.0.30319\csc.exe"
    )

    foreach ($csc in $cscCandidates) {
        if (Test-Path $csc) {
            $source = Join-Path $LauncherDir "Launcher.cs"
            Invoke-Native $csc @(
                "/nologo",
                "/target:exe",
                "/optimize+",
                "/platform:anycpu",
                "/out:$OutputExe",
                $source
            )
            return
        }
    }

    if (Get-Command "dotnet" -ErrorAction SilentlyContinue) {
        $project = Join-Path $LauncherDir "Hapkido.csproj"
        $outDir = Split-Path -Parent $OutputExe
        Invoke-Native "dotnet" @(
            "build", $project,
            "-c", "Release",
            "-o", $outDir,
            "--nologo"
        )
        return
    }

    throw "Tidak ada compiler C# (csc.exe .NET Framework 4.x atau dotnet SDK)."
}

function Find-InnoSetup {
    param([string] $Preferred)

    if ($Preferred -and (Test-Path $Preferred)) {
        return $Preferred
    }

    $candidates = @(
        "${env:LocalAppData}\Programs\Inno Setup 6\ISCC.exe",
        "${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe",
        "${env:ProgramFiles}\Inno Setup 6\ISCC.exe"
    )

    foreach ($path in $candidates) {
        if ($path -and (Test-Path $path)) {
            return $path
        }
    }

    $fromPath = Get-Command "ISCC.exe" -ErrorAction SilentlyContinue
    if ($fromPath) {
        return $fromPath.Source
    }

    return $null
}

# --- main -------------------------------------------------------------------

$repoRoot = Get-RepoRoot
$toolRoot = $PSScriptRoot
$cacheDir = Join-Path $toolRoot "cache"
$distDir = Join-Path $toolRoot "dist"
$stagingDir = Join-Path $distDir "app"
$launcherDir = Join-Path $toolRoot "launcher"

$phpZipName = "php-8.3-nts-Win32-vs16-x64-latest.zip"
$phpZipUrl = "https://windows.php.net/downloads/releases/latest/$phpZipName"
$cacertUrl = "https://curl.se/ca/cacert.pem"

Write-Host "Hapkido Championship - package Windows EXE"
Write-Host "Repo : $repoRoot"
Write-Host "Out  : $distDir"

Assert-Command "php"
Assert-Command "composer"
Assert-Command "robocopy"

if (-not $SkipNpm) {
    Assert-Command "npm"
}

Ensure-Dir $cacheDir
Clear-Dir $distDir
Ensure-Dir $stagingDir

# 1) Frontend assets ---------------------------------------------------------
if (-not $SkipNpm) {
    Write-Step "Build aset frontend (npm run build)"
    if (-not (Test-Path (Join-Path $repoRoot "node_modules"))) {
        Invoke-Native "npm" @("ci") $repoRoot
    }
    Invoke-Native "npm" @("run", "build") $repoRoot
}
else {
    Write-Step "Skip npm (pastikan public/build sudah ada)"
    if (-not (Test-Path (Join-Path $repoRoot "public\build"))) {
        throw 'Folder public/build belum ada. Jalankan tanpa -SkipNpm, atau npm run build dulu.'
    }
}

# 2) Stage application -------------------------------------------------------
Write-Step "Salin source ke staging"
Copy-AppFiles -Source $repoRoot -Destination $stagingDir

# Keep tools/build-exe templates inside the package root (not under tools/).
Copy-Item (Join-Path $toolRoot "env.production") (Join-Path $stagingDir "env.production") -Force
Copy-Item (Join-Path $toolRoot "server.txt") (Join-Path $stagingDir "server.txt") -Force

# Remove build tooling from the shipped tree (not needed at runtime).
$shipTools = Join-Path $stagingDir "tools"
if (Test-Path $shipTools) {
    Remove-Item -Recurse -Force $shipTools
}

# Writable skeletons before composer (package:discover writes bootstrap/cache).
$storageDirs = @(
    "storage\app\public",
    "storage\framework\cache\data",
    "storage\framework\sessions",
    "storage\framework\views",
    "storage\logs",
    "bootstrap\cache",
    "database"
)
foreach ($rel in $storageDirs) {
    Ensure-Dir (Join-Path $stagingDir $rel)
}

Write-Step "composer install --no-dev (staging)"
Invoke-Native "composer" @(
    "install",
    "--no-dev",
    "--optimize-autoloader",
    "--no-interaction",
    "--prefer-dist"
) $stagingDir

# Placeholder so empty dirs survive packaging; real DB is created by the launcher.
$dbFile = Join-Path $stagingDir "database\database.sqlite"
if (Test-Path $dbFile) { Remove-Item -Force $dbFile }
New-Item -ItemType File -Path (Join-Path $stagingDir "database\.gitkeep") -Force | Out-Null

# 3) PHP runtime -------------------------------------------------------------
Write-Step "Siapkan runtime PHP 8.3 NTS x64"
$phpZip = Join-Path $cacheDir $phpZipName
$phpRuntime = Join-Path $stagingDir "runtime\php"

if (-not $SkipPhpDownload -or -not (Test-Path $phpZip)) {
    if (-not (Test-Path $phpZip)) {
        Write-Host "    Download $phpZipUrl"
        Invoke-WebRequest -Uri $phpZipUrl -OutFile $phpZip -UseBasicParsing
    }
    else {
        Write-Host "    Pakai cache: $phpZip"
    }
}
elseif (-not (Test-Path $phpZip)) {
    throw "PHP zip tidak ada di cache dan -SkipPhpDownload dipakai: $phpZip"
}

Clear-Dir $phpRuntime
Expand-Archive -Path $phpZip -DestinationPath $phpRuntime -Force

Copy-Item (Join-Path $toolRoot "php.ini") (Join-Path $phpRuntime "php.ini") -Force
Ensure-Dir (Join-Path $phpRuntime "logs")

$cacert = Join-Path $cacheDir "cacert.pem"
if (-not (Test-Path $cacert)) {
    Write-Host "    Download cacert.pem"
    Invoke-WebRequest -Uri $cacertUrl -OutFile $cacert -UseBasicParsing
}
Copy-Item $cacert (Join-Path $phpRuntime "cacert.pem") -Force

$phpExe = Join-Path $phpRuntime "php.exe"
$phpVersion = & $phpExe -v
Write-Host "    $($phpVersion[0])"

# Smoke-check extensions
& $phpExe -m | Out-Null
$modules = & $phpExe -m
foreach ($need in @("pdo_sqlite", "sqlite3", "openssl", "mbstring", "curl", "fileinfo", "gd", "zip", "intl")) {
    if ($modules -notcontains $need) {
        throw "Ekstensi PHP wajib tidak aktif: $need (periksa php.ini / DLL di runtime\php\ext)"
    }
}

# 4) Launcher ----------------------------------------------------------------
$launcherExe = Join-Path $stagingDir "Hapkido.exe"
Build-Launcher -LauncherDir $launcherDir -OutputExe $launcherExe

if (-not (Test-Path $launcherExe)) {
    throw "Hapkido.exe gagal dibuat."
}

# 5) Installer (Inno Setup) --------------------------------------------------
$installerScript = Join-Path $toolRoot "installer.iss"
$installerOut = Join-Path $distDir "Setup-Hapkido.exe"

if (-not $SkipInstaller) {
    Write-Step "Build installer EXE (Inno Setup)"
    $iscc = Find-InnoSetup -Preferred $InnoSetupCompiler
    if (-not $iscc) {
        Write-Host "    ISCC.exe tidak ditemukan. Staging tetap siap di: $stagingDir" -ForegroundColor Yellow
        Write-Host "    Install Inno Setup 6: https://jrsoftware.org/isdl.php" -ForegroundColor Yellow
        Write-Host "    Lalu jalankan ulang, atau: ISCC.exe `"$installerScript`"" -ForegroundColor Yellow
    }
    else {
        Invoke-Native $iscc @(
            "/O$distDir",
            "/FSetup-Hapkido",
            $installerScript
        )
        if (-not (Test-Path $installerOut)) {
            # Inno may emit Setup-Hapkido.exe depending on /F; also check default name
            $alt = Get-ChildItem $distDir -Filter "*.exe" | Where-Object { $_.Name -like "Setup*" } | Select-Object -First 1
            if ($alt) {
                $installerOut = $alt.FullName
            }
        }
    }
}
else {
    Write-Step "Skip installer (-SkipInstaller)"
}

Write-Step "Selesai"
Write-Host "Portable app : $stagingDir"
Write-Host "Jalankan uji : $launcherExe"
if (Test-Path $installerOut) {
    Write-Host "Installer    : $installerOut"
}
Write-Host ""
Write-Host "Login pertama (setelah install / first run):" -ForegroundColor Green
Write-Host "  admin@hapkido.local / password"
Write-Host ""
