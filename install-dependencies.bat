@echo off
echo ========================================
echo Установка зависимостей проекта
echo ========================================
echo.

echo [1/2] Установка зависимостей backend (Composer)...
composer install --no-interaction
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось установить зависимости backend
    echo Убедитесь, что Composer установлен
    pause
    exit /b 1
)
echo OK
echo.

echo [2/2] Установка зависимостей frontend (npm)...
cd frontend
call npm install
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось установить зависимости frontend
    echo Убедитесь, что Node.js и npm установлены
    cd ..
    pause
    exit /b 1
)
cd ..
echo OK
echo.

echo ========================================
echo Все зависимости установлены!
echo ========================================
echo.
pause

