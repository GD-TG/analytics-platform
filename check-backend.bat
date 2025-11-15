@echo off
echo ========================================
echo Проверка статуса Backend сервера
echo ========================================
echo.

curl -s http://localhost:8000/health >nul 2>&1
if %errorlevel% equ 0 (
    echo [OK] Backend сервер запущен на http://localhost:8000
    echo.
    curl -s http://localhost:8000/health
    echo.
) else (
    echo [ОШИБКА] Backend сервер НЕ запущен!
    echo.
    echo Запустите backend командой:
    echo   start-backend.bat
    echo.
    echo Или вручную:
    echo   php artisan serve
    echo.
)

echo.
pause

