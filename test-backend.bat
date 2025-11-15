@echo off
echo ========================================
echo Проверка работы backend сервера
echo ========================================
echo.

echo Проверка порта 8000...
netstat -ano | findstr :8000
if %errorlevel% equ 0 (
    echo OK - Порт 8000 занят (сервер запущен)
) else (
    echo ВНИМАНИЕ: Порт 8000 свободен (сервер не запущен)
    echo.
    echo Запустите сервер командой:
    echo php artisan serve
    echo.
    echo Или используйте: start-backend.bat
)
echo.

echo Проверка доступности сервера...
curl http://localhost:8000/health 2>nul
if %errorlevel% equ 0 (
    echo OK - Сервер отвечает
) else (
    echo ОШИБКА: Сервер не отвечает
    echo.
    echo Убедитесь, что:
    echo 1. Backend сервер запущен
    echo 2. Порт 8000 не занят другим приложением
    echo 3. PHP установлен и доступен
)
echo.

pause

