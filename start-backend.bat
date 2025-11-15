@echo off
chcp 65001 >nul
echo ========================================
echo Запуск Laravel Backend Server
echo ========================================
echo.

cd /d %~dp0

echo [1/3] Проверка PHP...
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [ОШИБКА] PHP не найден в PATH!
    echo.
    echo Решение:
    echo 1. Установите PHP: https://www.php.net/downloads
    echo 2. Или используйте XAMPP/WAMP
    echo 3. Добавьте PHP в PATH системы
    echo.
    pause
    exit /b 1
)
php --version
echo [OK] PHP найден
echo.

echo [2/3] Проверка Laravel...
if not exist "artisan" (
    echo [ОШИБКА] Файл artisan не найден!
    echo Убедитесь, что вы в корне проекта Laravel
    echo.
    pause
    exit /b 1
)
echo [OK] Laravel найден
echo.

echo [3/3] Запуск сервера...
echo.
echo ========================================
echo Сервер запускается на http://localhost:8000
echo ========================================
echo.
echo Для остановки нажмите Ctrl+C
echo.
echo После запуска проверьте:
echo http://localhost:8000/health
echo.
echo ========================================
echo.

php artisan serve --host=127.0.0.1 --port=8000

if %errorlevel% neq 0 (
    echo.
    echo ========================================
    echo [ОШИБКА] Не удалось запустить сервер
    echo ========================================
    echo.
    echo Возможные причины:
    echo 1. Зависимости не установлены
    echo    Решение: composer install
    echo.
    echo 2. Порт 8000 занят другим приложением
    echo    Решение: php artisan serve --port=8001
    echo.
    echo 3. Ошибка в конфигурации
    echo    Решение: php artisan config:clear
    echo.
    pause
)
