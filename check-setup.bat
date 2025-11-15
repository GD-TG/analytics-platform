@echo off
echo ========================================
echo Проверка настройки проекта
echo ========================================
echo.

echo [1] Проверка PHP...
php -v
if %errorlevel% neq 0 (
    echo ОШИБКА: PHP не найден или не установлен
    pause
    exit /b 1
)
echo OK
echo.

echo [2] Проверка Composer...
composer --version
if %errorlevel% neq 0 (
    echo ВНИМАНИЕ: Composer не найден
)
echo.

echo [3] Проверка Node.js...
node --version
if %errorlevel% neq 0 (
    echo ОШИБКА: Node.js не найден или не установлен
    pause
    exit /b 1
)
echo OK
echo.

echo [4] Проверка npm...
npm --version
if %errorlevel% neq 0 (
    echo ОШИБКА: npm не найден
    pause
    exit /b 1
)
echo OK
echo.

echo [5] Проверка Laravel...
php artisan --version
if %errorlevel% neq 0 (
    echo ОШИБКА: Laravel не настроен правильно
    pause
    exit /b 1
)
echo OK
echo.

echo [6] Проверка .env файла...
if exist .env (
    echo OK - .env файл найден
) else (
    echo ВНИМАНИЕ: .env файл не найден
    echo Создайте его на основе .env.example
)
echo.

echo [7] Проверка зависимостей backend...
if exist vendor (
    echo OK - Зависимости установлены
) else (
    echo ВНИМАНИЕ: Зависимости не установлены
    echo Выполните: composer install
)
echo.

echo [8] Проверка зависимостей frontend...
if exist frontend\node_modules (
    echo OK - Зависимости установлены
) else (
    echo ВНИМАНИЕ: Зависимости не установлены
    echo Выполните: cd frontend ^&^& npm install
)
echo.

echo ========================================
echo Проверка завершена
echo ========================================
echo.
pause

