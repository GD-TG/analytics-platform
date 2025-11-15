@echo off
REM Первичная установка: composer install, key generate, migrate, seed

echo ========================================
echo Первичная установка Analytics Platform
echo ========================================

REM Проверка наличия composer
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Composer не найден. Установите Composer: https://getcomposer.org/Composer-Setup.exe
    pause
    exit /b 1
)

REM Проверка наличия php
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP не найден. Установите PHP и добавьте в PATH.
    pause
    exit /b 1
)

REM Копирование .env
if not exist ".env" (
    echo Копирую .env.docker в .env
    copy .env.docker .env
)

echo.
echo Установка PHP-зависимостей через Composer...
call composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo ERROR: composer install failed
    pause
    exit /b 1
)

echo.
echo Генерация APP_KEY...
call php artisan key:generate
if %errorlevel% neq 0 (
    echo ERROR: key:generate failed
    pause
    exit /b 1
)

echo.
echo Выполнение миграций...
call php artisan migrate --force
if %errorlevel% neq 0 (
    echo ERROR: migrate failed
    pause
    exit /b 1
)

echo.
echo Запуск сидеров...
call php artisan db:seed --force
if %errorlevel% neq 0 (
    echo WARNING: db:seed failed or no seeders exist
)

echo.
echo Создание symlink для storage...
call php artisan storage:link
if %errorlevel% neq 0 (
    echo WARNING: storage:link failed (может быть уже создана)
)

echo.
echo ========================================
echo Установка завершена!
echo ========================================
echo.
echo Теперь запустите все сервисы:
echo   .\scripts\run-all.bat
echo.
pause
