@echo off
echo ========================================
echo Проверка работы серверов
echo ========================================
echo.

echo [1/2] Проверка Backend (http://localhost:8000)...
curl -s http://localhost:8000/health >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Backend работает!
    curl -s http://localhost:8000/health
    echo.
) else (
    echo ❌ Backend НЕ работает
    echo.
    echo Запустите backend:
    echo start-backend.bat
    echo.
)
echo.

echo [2/2] Проверка Frontend (http://localhost:5173)...
curl -s http://localhost:5173 >nul 2>&1
if %errorlevel% equ 0 (
    echo ✅ Frontend работает!
) else (
    echo ❌ Frontend НЕ работает
    echo.
    echo Запустите frontend:
    echo start-frontend.bat
    echo.
)
echo.

echo ========================================
echo Проверка завершена
echo ========================================
echo.
echo Откройте в браузере:
echo - Frontend: http://localhost:5173
echo - Backend: http://localhost:8000/health
echo.
pause

