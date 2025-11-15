@echo off
REM Запускает все сервисы в отдельных окнах PowerShell

echo ========================================
echo Запуск всех сервисов Analytics Platform
echo ========================================

REM Проверка наличия необходимых команд
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: PHP не найден. Установите PHP и добавьте в PATH.
    pause
    exit /b 1
)

where redis-cli >nul 2>nul
if %errorlevel% neq 0 (
    echo WARNING: Redis не найден. Убедитесь, что Redis запущен вручную.
    echo   Вы можете запустить Redis из WSL (wsl redis-server --daemonize yes)
    echo   или использовать готовую сборку (https://memurai.com/)
)

REM Получаем текущую директорию
set PROJECT_DIR=%cd%

echo.
echo 1. Запускаю веб-сервер Laravel на порту 8000...
start "Laravel Server" powershell -NoExit -Command "cd '%PROJECT_DIR%'; php artisan serve --host=127.0.0.1 --port=8000"

echo 2. Запускаю очередь (queue worker)...
timeout /t 2
start "Queue Worker" powershell -NoExit -Command "cd '%PROJECT_DIR%'; php artisan queue:work --sleep=3 --tries=3"

echo 3. Запускаю планировщик (scheduler)...
timeout /t 2
start "Scheduler" powershell -NoExit -Command "cd '%PROJECT_DIR%'; php artisan schedule:work"

echo.
echo ========================================
echo Все сервисы запущены!
echo ========================================
echo.
echo URL приложения: http://127.0.0.1:8000
echo API: http://127.0.0.1:8000/api/
echo.
echo Откройте браузер и откройте URL выше.
echo.
echo Окна можно закрывать нажатием Ctrl+C в каждом.
echo.
timeout /t 5
