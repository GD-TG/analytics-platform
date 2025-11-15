@echo off
echo ========================================
echo Выполнение миграций базы данных
echo ========================================
echo.

echo Проверка подключения к базе данных...
php artisan migrate:status
if %errorlevel% neq 0 (
    echo.
    echo ОШИБКА: Не удалось подключиться к базе данных
    echo.
    echo Убедитесь, что:
    echo 1. MySQL запущен
    echo 2. База данных создана (analytics_platform)
    echo 3. Настройки в .env файле правильные
    echo.
    pause
    exit /b 1
)

echo.
echo Выполнение миграций...
php artisan migrate
if %errorlevel% neq 0 (
    echo.
    echo ОШИБКА: Не удалось выполнить миграции
    echo Проверьте настройки базы данных в .env
    echo.
    pause
    exit /b 1
)

echo.
echo ========================================
echo Миграции выполнены успешно!
echo ========================================
echo.
pause

