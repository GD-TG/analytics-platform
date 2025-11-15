@echo off
REM Проверка статуса всех сервисов

echo ========================================
echo Проверка статуса сервисов
echo ========================================

REM PHP
echo.
echo [1] PHP:
php -v
if %errorlevel% neq 0 echo ERROR: PHP не найден

REM Composer
echo.
echo [2] Composer:
composer --version
if %errorlevel% neq 0 echo ERROR: Composer не найден

REM Node.js
echo.
echo [3] Node.js:
node --version
npm --version
if %errorlevel% neq 0 echo ERROR: Node.js не найден

REM MySQL
echo.
echo [4] MySQL (проверка подключения):
mysql -u analytics -panalytics -h 127.0.0.1 -e "SELECT 1 as 'MySQL Status';" 2>nul
if %errorlevel% neq 0 (
    echo ERROR: MySQL не доступна. Убедитесь, что MySQL Server запущена.
    echo   - Проверьте: services.msc^(MySQL80 должна быть в статусе Running^)
    echo   - Или запустите вручную: "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld"
) else (
    echo OK: MySQL доступна
)

REM Redis
echo.
echo [5] Redis:
redis-cli ping >nul 2>nul
if %errorlevel% neq 0 (
    echo ERROR: Redis не доступна. Запустите Redis:
    echo   - WSL: wsl redis-server --daemonize yes
    echo   - Или используйте готовую сборку: https://memurai.com/
) else (
    echo OK: Redis доступна
)

echo.
echo ========================================
echo Проверка завершена
echo ========================================
echo.
pause
