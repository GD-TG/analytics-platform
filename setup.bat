@echo off
echo ========================================
echo Настройка Analytics Platform
echo ========================================
echo.

echo [1/5] Очистка кеша конфигурации...
php artisan config:clear
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось очистить кеш конфигурации
    pause
    exit /b 1
)

echo [2/5] Очистка кеша приложения...
php artisan cache:clear
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось очистить кеш
    pause
    exit /b 1
)

echo [3/5] Очистка кеша маршрутов...
php artisan route:clear
if %errorlevel% neq 0 (
    echo ОШИБКА: Не удалось очистить кеш маршрутов
    pause
    exit /b 1
)

echo [4/5] Проверка статуса миграций...
php artisan migrate:status
if %errorlevel% neq 0 (
    echo ВНИМАНИЕ: Проблема с миграциями. Убедитесь, что база данных настроена.
    echo.
    echo Выполните миграции вручную:
    echo php artisan migrate
    echo.
    pause
)

echo [5/5] Оптимизация приложения...
php artisan optimize:clear
if %errorlevel% neq 0 (
    echo ВНИМАНИЕ: Не удалось оптимизировать приложение
)

echo.
echo ========================================
echo Настройка завершена!
echo ========================================
echo.
echo Теперь можно запустить серверы:
echo - start-backend.bat (для backend)
echo - start-frontend.bat (для frontend)
echo - или start.bat (для обоих)
echo.
pause

