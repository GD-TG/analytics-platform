# PowerShell скрипт для установки PHP 8.2 и Composer

# Проверка прав администратора (опционально)
$isAdmin = ([Security.Principal.WindowsPrincipal] [Security.Principal.WindowsIdentity]::GetCurrent()).IsInRole([Security.Principal.WindowsBuiltInRole] "Administrator")
if (-not $isAdmin) {
    Write-Host "WARNING: Скрипт работает без прав администратора. Может потребоваться перезагрузка PowerShell от администратора для добавления PHP в PATH." -ForegroundColor Yellow
}

# Папка для установки
$phpPath = "C:\php8.2"
$composerPath = "C:\composer"

Write-Host "========================================" -ForegroundColor Green
Write-Host "Установка PHP 8.2 и Composer"
Write-Host "========================================" -ForegroundColor Green

# Создание папок
if (-not (Test-Path $phpPath)) {
    New-Item -ItemType Directory -Path $phpPath | Out-Null
    Write-Host "✓ Создана папка $phpPath"
}

if (-not (Test-Path $composerPath)) {
    New-Item -ItemType Directory -Path $composerPath | Out-Null
    Write-Host "✓ Создана папка $composerPath"
}

# ========== Загрузка и установка PHP ==========
Write-Host ""
Write-Host "Загрузка PHP 8.2 (thread-safe)..." -ForegroundColor Cyan

$phpUrl = "https://windows.php.net/downloads/releases/php-8.2.13-Win32-vs16-x64.zip"
$phpZip = "$env:TEMP\php-8.2.13.zip"

try {
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072
    $client = New-Object System.Net.WebClient
    $client.DownloadFile($phpUrl, $phpZip)
    Write-Host "✓ PHP загружен"
} catch {
    Write-Host "✗ Ошибка загрузки PHP: $_" -ForegroundColor Red
    exit 1
}

Write-Host "Распаковка PHP в $phpPath..."
Expand-Archive -Path $phpZip -DestinationPath $phpPath -Force | Out-Null
Write-Host "✓ PHP распакован"

# Копирование php.ini
if (Test-Path "$phpPath\php.ini-production") {
    Copy-Item "$phpPath\php.ini-production" "$phpPath\php.ini" -Force
    Write-Host "✓ php.ini создан"
}

# Включение расширений в php.ini
Write-Host "Включение расширений в php.ini..."
$phpIni = "$phpPath\php.ini"

$extensions = @(
    "extension=pdo_mysql",
    "extension=redis",
    "extension=gd",
    "extension=zip",
    "extension=mbstring",
    "extension=intl",
    "extension=bcmath"
)

foreach ($ext in $extensions) {
    $extName = $ext.Split("=")[1]
    # Проверяем, есть ли закомментированная версия
    $commentedLine = ";$ext"
    if (Select-String -Path $phpIni -Pattern $commentedLine) {
        # Раскомментируем
        (Get-Content $phpIni) -replace [regex]::Escape($commentedLine), $ext | Set-Content $phpIni
        Write-Host "  ✓ Раскомментирована $extName"
    } elseif (-not (Select-String -Path $phpIni -Pattern $ext)) {
        # Добавим в конец
        Add-Content -Path $phpIni -Value $ext
        Write-Host "  ✓ Добавлена $extName"
    }
}

# ========== Добавление PHP в PATH ==========
Write-Host ""
Write-Host "Добавление PHP в PATH..." -ForegroundColor Cyan

$currentPath = [Environment]::GetEnvironmentVariable("Path", "User")
if ($currentPath -notlike "*$phpPath*") {
    [Environment]::SetEnvironmentVariable("Path", "$currentPath;$phpPath", "User")
    Write-Host "✓ PHP добавлен в PATH (текущая сессия: перезагрузите PowerShell для применения)"
}

# Обновление PATH для текущей сессии
$env:Path = "$env:Path;$phpPath"

# ========== Загрузка и установка Composer ==========
Write-Host ""
Write-Host "Загрузка Composer..." -ForegroundColor Cyan

$composerUrl = "https://getcomposer.org/Composer-Setup.exe"
$composerExe = "$env:TEMP\Composer-Setup.exe"

try {
    $client = New-Object System.Net.WebClient
    $client.DownloadFile($composerUrl, $composerExe)
    Write-Host "✓ Composer загружен"
    
    Write-Host "Запуск установщика Composer..." -ForegroundColor Cyan
    & $composerExe /S
    Write-Host "✓ Composer установлен"
} catch {
    Write-Host "✗ Ошибка при установке Composer: $_" -ForegroundColor Red
    Write-Host "Загрузите установщик вручную: $composerUrl" -ForegroundColor Yellow
    exit 1
}

# ========== Проверка ==========
Write-Host ""
Write-Host "Проверка установки..." -ForegroundColor Cyan

Write-Host ""
Write-Host "PHP версия:"
& "$phpPath\php.exe" -v

Write-Host ""
Write-Host "Composer версия:"
composer --version

Write-Host ""
Write-Host "========================================" -ForegroundColor Green
Write-Host "Установка завершена!"
Write-Host "========================================" -ForegroundColor Green
Write-Host ""
Write-Host "Следующие шаги:" -ForegroundColor Cyan
Write-Host "1. Перезагрузите PowerShell (закройте и откройте заново)"
Write-Host "2. Убедитесь, что MySQL и Redis запущены"
Write-Host "3. Выполните: .\scripts\setup-windows.bat"
Write-Host ""

Read-Host "Нажмите Enter для выхода"
