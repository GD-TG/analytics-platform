@echo off
chcp 65001 >nul
echo ========================================
echo ЗАПУСК BACKEND СЕРВЕРА
echo ========================================
echo.
echo Проверка PHP...
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [ОШИБКА] PHP не найден!
    echo.
    echo Установите PHP или используйте XAMPP/WAMP
    echo.
    pause
    exit /b 1
)

echo [OK] PHP найден
echo.
echo Проверка Composer...
where composer >nul 2>&1
if %errorlevel% neq 0 (
    echo [ПРЕДУПРЕЖДЕНИЕ] Composer не найден
    echo Продолжаю запуск...
    echo.
)

echo.
echo ========================================
echo Запуск Laravel сервера...
echo ========================================
echo.
echo Сервер будет доступен на: http://localhost:8000
echo.
echo Для остановки нажмите Ctrl+C
echo.
echo ========================================
echo.

cd /d %~dp0
php artisan serve --host=127.0.0.1 --port=8000

if %errorlevel% neq 0 (
    echo.
    echo [ОШИБКА] Не удалось запустить сервер
    echo.
    echo Возможные причины:
    echo 1. PHP не установлен
    echo 2. Зависимости не установлены (выполните: composer install)
    echo 3. Порт 8000 занят
    echo.
    pause
)

