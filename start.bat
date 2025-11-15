@echo off
echo ========================================
echo Запуск Analytics Platform
echo ========================================
echo.

echo [1/2] Запуск Laravel Backend сервера...
start "Laravel Backend" cmd /k "cd /d %~dp0 && php artisan serve --host=127.0.0.1 --port=8000"

echo Ожидание запуска backend (5 секунд)...
timeout /t 5 /nobreak >nul

echo.
echo [2/2] Запуск React Frontend сервера...
cd frontend
start "React Frontend" cmd /k "npm run dev"

echo.
echo ========================================
echo Серверы запускаются...
echo ========================================
echo.
echo Backend:  http://localhost:8000
echo Frontend: http://localhost:5173
echo.
echo Откройте в браузере: http://localhost:5173
echo.
echo Для остановки закройте окна серверов
echo.
pause

