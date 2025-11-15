@echo off
echo ========================================
echo Проверка статуса Backend сервера
echo ========================================
echo.

echo Проверяю http://localhost:8000/health...
echo.

powershell -Command "try { $response = Invoke-WebRequest -Uri 'http://localhost:8000/health' -TimeoutSec 3 -UseBasicParsing; Write-Host '[OK] Backend работает!'; Write-Host $response.Content } catch { Write-Host '[ОШИБКА] Backend не запущен или недоступен'; Write-Host 'Запустите: start-backend.bat или php artisan serve' }"

echo.
echo ========================================
pause

