@echo off
REM Скрипт установки PHP 8.2 и Composer для Windows

setlocal enabledelayedexpansion

set PHP_PATH=C:\php8.2
set COMPOSER_PATH=C:\Program Files\Composer
set PHP_URL=https://windows.php.net/downloads/releases/php-8.2.13-Win32-vs16-x64.zip
set TEMP_ZIP=%TEMP%\php-8.2.13.zip

echo ========================================
echo Установка PHP 8.2 и Composer
echo ========================================

REM Проверка наличия нужных команд
powershell -NoProfile -Command "Write-Host 'Проверка PowerShell...' -ForegroundColor Green"

REM Создание папки для PHP
if not exist "%PHP_PATH%" (
    mkdir "%PHP_PATH%"
    echo Создана папка %PHP_PATH%
)

REM Загрузка PHP
echo.
echo Загрузка PHP 8.2...
echo Используйте браузер для загрузки и распаковки:
echo %PHP_URL%
echo.
echo Распакуйте ZIP в: %PHP_PATH%
echo.

REM Попытка загрузить PHP через PowerShell
powershell -NoProfile -Command ^
    "[System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; ^
    (New-Object System.Net.WebClient).DownloadFile('%PHP_URL%', '%TEMP_ZIP%'); ^
    Write-Host 'PHP загружен в %TEMP_ZIP%'"

if exist "%TEMP_ZIP%" (
    echo.
    echo Распаковка PHP...
    powershell -NoProfile -Command "Expand-Archive -Path '%TEMP_ZIP%' -DestinationPath '%PHP_PATH%' -Force"
    echo PHP распакован в %PHP_PATH%
    del "%TEMP_ZIP%"
)

REM Копирование php.ini
if exist "%PHP_PATH%\php.ini-production" (
    copy "%PHP_PATH%\php.ini-production" "%PHP_PATH%\php.ini"
    echo php.ini создан
)

REM Добавление PHP в PATH
echo.
echo Добавление PHP в PATH...
setx PATH "%PATH%;%PHP_PATH%"
set PATH=%PATH%;%PHP_PATH%
echo PHP добавлен в PATH

REM Проверка PHP
echo.
echo Проверка PHP:
"%PHP_PATH%\php.exe" -v

REM Загрузка Composer
echo.
echo Загрузка Composer...
echo Используйте браузер для загрузки установщика:
echo https://getcomposer.org/Composer-Setup.exe
echo.

REM Проверка Composer
echo.
echo Проверка Composer:
composer --version

echo.
echo ========================================
echo Установка завершена!
echo ========================================
echo.
echo Следующие шаги:
echo 1. Закройте и перезагрузите PowerShell
echo 2. Убедитесь, что MySQL и Redis запущены
echo 3. Выполните: .\scripts\setup-windows.bat
echo.

pause
