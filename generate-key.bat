@echo off
chcp 65001 >nul
echo ========================================
echo Генерация APP_KEY для Laravel
echo ========================================
echo.

cd /d %~dp0

echo Проверка PHP...
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo [ОШИБКА] PHP не найден!
    echo.
    pause
    exit /b 1
)

echo [OK] PHP найден
echo.

echo Генерация APP_KEY...
php artisan key:generate

if %errorlevel% neq 0 (
    echo.
    echo [ОШИБКА] Не удалось сгенерировать ключ
    echo.
    echo Возможные причины:
    echo 1. Файл .env не существует
    echo 2. Нет прав на запись в .env
    echo.
    pause
    exit /b 1
)

echo.
echo [OK] APP_KEY успешно сгенерирован!
echo.

echo Очистка кеша...
php artisan config:clear
php artisan cache:clear

echo.
echo ========================================
echo Готово! Теперь можно запустить сервер
echo ========================================
echo.
pause

