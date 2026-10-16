#Requires -Version 7.2

[CmdletBinding()]
param()

Set-StrictMode -Version Latest
$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$themeRoot = Join-Path $projectRoot 'theme'
$outputRoot = Join-Path $projectRoot 'output'
$version = '1.1.4'
$slug = 'imperial-future'
$archivePath = Join-Path $outputRoot "$slug-$version.zip"
$fixedTime = [DateTimeOffset]::new(2020, 1, 1, 0, 0, 0, [TimeSpan]::Zero)
$files = [System.Collections.Generic.Dictionary[string, string]]::new([StringComparer]::Ordinal)
$rootFiles = @(
    'style.css', 'theme.json', 'readme.txt', 'LICENSE', 'THIRD-PARTY-NOTICES.md',
    'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
    'vite.config.js', 'screenshot.png'
)
$rootDirectories = @('assets', 'inc', 'parts', 'templates', 'src', 'vendor', 'languages', 'licenses', 'tools')
$excludedDirectories = @(
    'node_modules', 'screenshots', 'tests', 'test', '__tests__', 'test-results',
    'output', 'coverage', '.git', '.github', '.gitea', '.svn', '.idea', '.vscode',
    '.cache', 'cache', 'docker', '.docker', 'secrets'
)

function Assert-PlainPath {
    param([string] $Path)

    $item = Get-Item -LiteralPath $Path -Force
    if ($item.Attributes -band [IO.FileAttributes]::ReparsePoint) {
        throw "Ссылка или junction недопустимы в пакете: $Path"
    }
}

function Test-ExcludedFile {
    param([string] $Name)

    return $Name -match '^(\.env|\.npmrc$|\.pypirc$|auth\.json$|wp-config\.php$|credentials(?:\.|$)|secrets?(?:\.|$)|Dockerfile(?:\.|$)|\.dockerignore$|(?:docker-)?compose(?:\.[^.]+)?\.ya?ml$)' `
        -or $Name -match '\.(?:pem|key|p12|pfx|sql|sqlite3?|log|bak|tmp|zip|tar|gz|7z)$' `
        -or $Name -in @('current-goal.md', 'next-goal.md', 'previous-goal.md', 'important-notes.md', '.DS_Store', 'Thumbs.db')
}

function Add-PackageDirectory {
    param([string] $Directory, [string] $RelativePath)

    Assert-PlainPath $Directory
    foreach ($item in Get-ChildItem -LiteralPath $Directory -Force) {
        $relative = "$RelativePath/$($item.Name)"
        if ($item.PSIsContainer) {
            if ($item.Name -in $excludedDirectories) { continue }
            Add-PackageDirectory $item.FullName $relative
        } elseif (-not (Test-ExcludedFile $item.Name)) {
            Assert-PlainPath $item.FullName
            $files.Add($relative, $item.FullName)
        }
    }
}

function Assert-PackagedFile {
    param([string] $RelativePath)

    if (-not $files.ContainsKey($RelativePath)) {
        throw "Не готов обязательный файл пакета: theme/$RelativePath"
    }
    if ((Get-Item -LiteralPath $files[$RelativePath]).Length -eq 0) {
        throw "Пустой обязательный файл: theme/$RelativePath"
    }
}

function Assert-DistFile {
    param([string] $RelativePath)

    if ([string]::IsNullOrWhiteSpace($RelativePath) -or $RelativePath.StartsWith('/') `
        -or $RelativePath.Contains('\') -or $RelativePath.Split('/') -contains '..') {
        throw "Недопустимый путь в Vite manifest: $RelativePath"
    }
    Assert-PackagedFile "assets/dist/$RelativePath"
}

function Get-SourceHash {
    param([string] $RelativePath)

    Assert-PackagedFile $RelativePath
    return (Get-FileHash -LiteralPath $files[$RelativePath] -Algorithm SHA256).Hash.ToLowerInvariant()
}

function Assert-LicenseNotice {
    param([hashtable] $Notice)

    if ((Get-SourceHash $Notice.path) -ne $Notice.sha256) {
        throw "Изменился текст лицензии: $($Notice.path). Запусти collect-licenses.mjs."
    }
}

# Читаем только разрешённые каталоги темы, без node_modules и промежуточной копии.
Assert-PlainPath $themeRoot
foreach ($item in Get-ChildItem -LiteralPath $themeRoot -Force) {
    if ($item.PSIsContainer -and $item.Name -in $rootDirectories) {
        Add-PackageDirectory $item.FullName $item.Name
    } elseif (-not $item.PSIsContainer -and ($item.Name -in $rootFiles -or $item.Extension -eq '.php') `
        -and -not (Test-ExcludedFile $item.Name)) {
        Assert-PlainPath $item.FullName
        $files.Add($item.Name, $item.FullName)
    }
}

$requiredFiles = @(
    'style.css', 'index.php', 'functions.php', 'theme.json', 'readme.txt', 'LICENSE',
    'THIRD-PARTY-NOTICES.md', 'composer.json', 'composer.lock', 'package.json',
    'package-lock.json', 'vite.config.js', 'src/js/main.js', 'src/scss/main.scss',
    'assets/dist/.vite/manifest.json', 'assets/dist/icons.svg',
    'assets/dist/css/critical-inline.css', 'assets/dist/css/fonts-inline.json',
    'assets/dist/css/fonts-all.css', 'vendor/autoload.php',
    'vendor/composer/installed.json', 'vendor/composer/LICENSE',
    'languages/imperial-future.pot', 'licenses/index.json', 'licenses/README.md',
    'tools/collect-licenses.mjs', 'screenshot.png'
)
foreach ($relative in $requiredFiles) { Assert-PackagedFile $relative }
$style = Get-Content -LiteralPath $files['style.css'] -Raw
if ($style -notmatch "(?m)^Version:\s*$([regex]::Escape($version))\s*$") {
    throw "Версия style.css не совпадает с версией архива $version."
}

# Проверяем все ресурсы manifest, в том числе ленивые модули и локальные шрифты.
$manifest = Get-Content -LiteralPath $files['assets/dist/.vite/manifest.json'] -Raw | ConvertFrom-Json -AsHashtable
if (-not $manifest.ContainsKey('src/js/main.js') -or -not $manifest['src/js/main.js'].isEntry) {
    throw 'Vite manifest не содержит основной entrypoint src/js/main.js.'
}
foreach ($entry in $manifest.Values) {
    Assert-DistFile $entry.file
    foreach ($field in @('css', 'assets')) {
        if ($entry.ContainsKey($field)) {
            foreach ($asset in $entry[$field]) { Assert-DistFile $asset }
        }
    }
    foreach ($field in @('imports', 'dynamicImports')) {
        if ($entry.ContainsKey($field)) {
            foreach ($reference in $entry[$field]) {
                if (-not $manifest.ContainsKey($reference)) {
                    throw "В manifest отсутствует модуль: $reference"
                }
            }
        }
    }
}
if (-not (@($files.Keys | Where-Object { $_ -like 'assets/images/*' }).Count)) {
    throw 'Не готовы изображения assets/images.'
}

# Версии и тексты лицензий должны соответствовать обоим lock-файлам.
$licenses = Get-Content -LiteralPath $files['licenses/index.json'] -Raw | ConvertFrom-Json -AsHashtable
if ($licenses.schemaVersion -ne 1 -or $licenses.packageLockSha256 -ne (Get-SourceHash 'package-lock.json') `
    -or $licenses.composerLockSha256 -ne (Get-SourceHash 'composer.lock')) {
    throw 'Реестр лицензий устарел. Запусти docker compose exec -T node node tools/collect-licenses.mjs.'
}
$package = Get-Content -LiteralPath $files['package.json'] -Raw | ConvertFrom-Json -AsHashtable
foreach ($section in @('dependencies', 'devDependencies')) {
    foreach ($name in $package[$section].Keys) {
        if (-not ($licenses.npm | Where-Object { $_.name -eq $name -and $_.installedPath -eq "node_modules/$name" })) {
            throw "Нет лицензии прямой npm-зависимости: $name"
        }
    }
}
foreach ($dependency in $licenses.npm) {
    if (-not $dependency.notices.Count) { throw "Нет текста лицензии $($dependency.name)" }
    foreach ($notice in $dependency.notices) { Assert-LicenseNotice $notice }
}
$composerLock = Get-Content -LiteralPath $files['composer.lock'] -Raw | ConvertFrom-Json -AsHashtable
$composerInstalled = Get-Content -LiteralPath $files['vendor/composer/installed.json'] -Raw | ConvertFrom-Json -AsHashtable
foreach ($dependency in $composerLock.packages) {
    $installed = @($composerInstalled.packages | Where-Object { $_.name -eq $dependency.name -and $_.version -eq $dependency.version })
    $licensed = @($licenses.composer | Where-Object { $_.name -eq $dependency.name -and $_.version -eq $dependency.version })
    if ($installed.Count -ne 1 -or $licensed.Count -ne 1) {
        throw "Composer-пакет или его лицензия не соответствуют lock: $($dependency.name)"
    }
    if (-not $licensed[0].notices.Count) { throw "Нет текста лицензии $($dependency.name)" }
    foreach ($notice in $licensed[0].notices) { Assert-LicenseNotice $notice }
}
Assert-LicenseNotice $licenses.composerAutoloader

# Фиксируем порядок, время и права; Store исключает различия реализаций Deflate.
$names = [string[]] $files.Keys
[Array]::Sort($names, [StringComparer]::Ordinal)
$sourceHashes = [System.Collections.Generic.Dictionary[string, string]]::new([StringComparer]::Ordinal)
$buffer = [IO.MemoryStream]::new()
try {
    $zip = [IO.Compression.ZipArchive]::new($buffer, [IO.Compression.ZipArchiveMode]::Create, $true, [Text.Encoding]::UTF8)
    try {
        foreach ($name in $names) {
            $bytes = [IO.File]::ReadAllBytes($files[$name])
            $sourceHashes.Add($name, [Convert]::ToHexString([Security.Cryptography.SHA256]::HashData($bytes)).ToLowerInvariant())
            $entry = $zip.CreateEntry("$slug/$name", [IO.Compression.CompressionLevel]::NoCompression)
            $entry.LastWriteTime = $fixedTime
            $entry.ExternalAttributes = 0x81A40000
            $stream = $entry.Open()
            try { $stream.Write($bytes, 0, $bytes.Length) } finally { $stream.Dispose() }
        }
    } finally { $zip.Dispose() }

    # Не публикуем архив, если тема менялась во время чтения.
    foreach ($name in $names) {
        if ((Get-SourceHash $name) -ne $sourceHashes[$name]) {
            throw "Файл изменился во время упаковки: $name. Повтори после завершения сборки."
        }
    }
    $buffer.Position = 0
    $check = [IO.Compression.ZipArchive]::new($buffer, [IO.Compression.ZipArchiveMode]::Read, $true, [Text.Encoding]::UTF8)
    try {
        if ($check.Entries.Count -ne $names.Count) { throw 'Не совпало число файлов в ZIP.' }
        for ($index = 0; $index -lt $names.Count; $index++) {
            $entry = $check.Entries[$index]
            $name = $names[$index]
            if ($entry.FullName -cne "$slug/$name") { throw "Неверный путь в ZIP: $($entry.FullName)" }
            $stream = $entry.Open()
            $hasher = [Security.Cryptography.SHA256]::Create()
            try {
                $hash = [Convert]::ToHexString($hasher.ComputeHash($stream)).ToLowerInvariant()
                if ($hash -ne $sourceHashes[$name]) { throw "Не совпали данные ZIP: $name" }
            } finally { $hasher.Dispose(); $stream.Dispose() }
        }
    } finally { $check.Dispose() }

    [void] [IO.Directory]::CreateDirectory($outputRoot)
    Assert-PlainPath $outputRoot
    if (Test-Path -LiteralPath $archivePath) { Assert-PlainPath $archivePath }
    $buffer.Position = 0
    $destination = [IO.File]::Open($archivePath, [IO.FileMode]::Create, [IO.FileAccess]::Write, [IO.FileShare]::None)
    try { $buffer.CopyTo($destination) } finally { $destination.Dispose() }
} finally { $buffer.Dispose() }

$archiveHash = (Get-FileHash -LiteralPath $archivePath -Algorithm SHA256).Hash.ToLowerInvariant()
$checksumPath = "$archivePath.sha256"
if (Test-Path -LiteralPath $checksumPath) { Assert-PlainPath $checksumPath }
[IO.File]::WriteAllText($checksumPath, "$archiveHash  $slug-$version.zip`n", [Text.Encoding]::ASCII)
Write-Output "Архив: $archivePath"
Write-Output "Контрольная сумма: $checksumPath"
Write-Output "Файлов: $($names.Count)"
Write-Output "SHA-256: $archiveHash"
