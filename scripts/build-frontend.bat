@echo off
REM Сборка фронтенда

echo ========================================
echo Сборка фронтенда
echo ========================================

REM Проверка наличия Node.js
where node >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Node.js не найден. Установите Node.js: https://nodejs.org/
    pause
    exit /b 1
)

REM Переход в папку frontend
cd frontend

echo.
echo Установка Node-зависимостей...
call npm ci
if %errorlevel% neq 0 (
    echo ERROR: npm ci failed
    pause
    exit /b 1
)

echo.
echo Сборка проекта...
call npm run build
if %errorlevel% neq 0 (
    echo ERROR: npm run build failed
    pause
    exit /b 1
)

echo.
echo Копирование артефактов в public...
if exist "dist" (
    xcopy dist ..\public /E /I /Y
    echo ✓ Артефакты скопированы в public/
) else (
    echo ERROR: dist папка не найдена
    pause
    exit /b 1
)

echo.
echo ========================================
echo Фронтенд успешно собран!
echo ========================================
echo.
pause
