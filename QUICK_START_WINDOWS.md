# Полная инструкция для запуска Analytics Platform на Windows

## Что нужно сделать вручную на вашем компьютере

### Шаг 1: Установка PHP 8.2

1. Откройте браузер и скачайте PHP 8.2:
   - URL: https://windows.php.net/downloads/releases/php-8.2.13-Win32-vs16-x64.zip

2. Распакуйте ZIP в: `C:\php8.2`

3. Перейдите в `C:\php8.2` и переименуйте или скопируйте:
   - `php.ini-production` → `php.ini`

4. Откройте `C:\php8.2\php.ini` в текстовом редакторе и найдите/раскомментируйте строки:
```ini
extension=pdo_mysql
extension=redis
extension=gd
extension=zip
extension=mbstring
extension=intl
extension=bcmath
```

5. Добавьте PHP в переменную окружения PATH:
   - Откройте "Переменные окружения" (Environment Variables)
   - Найдите `Path` в разделе "User variables"
   - Нажмите "Edit" и добавьте: `C:\php8.2`
   - ОК и перезагрузите PowerShell

6. Проверьте в PowerShell:
```powershell
php -v
```

### Шаг 2: Установка Composer

1. Скачайте установщик: https://getcomposer.org/Composer-Setup.exe
2. Запустите установщик (администратор)
3. Оставьте все по умолчанию

4. Проверьте:
```powershell
composer --version
```

### Шаг 3: Установка MySQL 8.0

1. Скачайте MySQL: https://dev.mysql.com/downloads/mysql/
   - Выберите MySQL Community Server 8.0
   - ОС: Microsoft Windows
   - Скачайте установщик

2. Запустите установщик:
   - Server type: Server Machine
   - Config Type: Development Machine
   - Port: 3306
   - Root password: (задайте пароль, запомните его)

3. Создайте БД и пользователя. Откройте PowerShell и выполните:
```powershell
mysql -u root -p
# Введите пароль root
```

Затем в MySQL:
```sql
CREATE DATABASE analytics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'analytics'@'localhost' IDENTIFIED BY 'analytics';
GRANT ALL PRIVILEGES ON analytics.* TO 'analytics'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Шаг 4: Установка Redis

Вариант A (WSL2 - рекомендуется если у вас Windows 10/11 Pro):
```powershell
wsl
sudo apt-get update
sudo apt-get install redis-server -y
redis-server --daemonize yes
# проверка: redis-cli ping (должно вернуть PONG)
```

Вариант B (Готовая сборка для Windows):
- Скачайте: https://memurai.com/ (просто установщик и готово)
- Или: https://github.com/microsoftarchive/redis/releases (более старая версия)

### Шаг 5: Запуск проекта

После установки всех зависимостей:

1. Откройте PowerShell в папке проекта:
```powershell
cd C:\Users\Dark_Angel\Desktop\hack\analytics-platform
```

2. Первичная установка (только один раз):
```powershell
.\scripts\setup-windows.bat
```

Этот батник выполнит:
- `composer install` (установка PHP-зависимостей)
- `php artisan key:generate` (генерация APP_KEY)
- `php artisan migrate --force` (создание таблиц БД)
- `php artisan db:seed --force` (заполнение тестовыми данными)
- `php artisan storage:link` (создание symlink для файлов)

3. Сборка фронтенда:
```powershell
.\scripts\build-frontend.bat
```

4. Запуск всех сервисов (откройте 3 отдельных окна PowerShell):

**Окно 1 (Веб-сервер):**
```powershell
cd C:\Users\Dark_Angel\Desktop\hack\analytics-platform
php artisan serve --host=127.0.0.1 --port=8000
```
Откройте в браузере: http://127.0.0.1:8000

**Окно 2 (Очередь):**
```powershell
cd C:\Users\Dark_Angel\Desktop\hack\analytics-platform
php artisan queue:work --sleep=3 --tries=3
```

**Окно 3 (Планировщик):**
```powershell
cd C:\Users\Dark_Angel\Desktop\hack\analytics-platform
php artisan schedule:work
```

### Проверка что всё работает

1. Откройте http://127.0.0.1:8000 в браузере
2. API: http://127.0.0.1:8000/api/
3. Очередь должна обрабатывать задачи (Окно 2)
4. Планировщик должен выполнять кроны (Окно 3)

### Проблемы и решения

| Проблема | Решение |
|----------|---------|
| "php: command not found" | Убедитесь, что PHP добавлен в PATH и перезагрузили PowerShell |
| MySQL ошибка "Access denied" | Проверьте пароль в `.env` файле (DB_PASSWORD) |
| Redis "Connection refused" | Убедитесь, что Redis запущен (`redis-cli ping`) |
| Port 8000 already in use | Используйте другой порт: `php artisan serve --port=8001` |
| CORS ошибки в браузере | Проверьте `config/cors.php` и убедитесь, что фронтенд обращается к правильному API URL |

### Переменные окружения (.env)

Проверьте файл `.env` в корне проекта:

```ini
APP_NAME=AnalyticsPlatform
APP_ENV=local
APP_KEY=base64:...

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=analytics
DB_USERNAME=analytics
DB_PASSWORD=analytics

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

Если используете WSL Redis, измените `REDIS_HOST` на IP адрес WSL (узнайте через `wsl hostname -I`).

---

## Быстрый старт (коротко)

```powershell
# 1. Скачать и установить: PHP, Composer, MySQL, Redis
# 2. Создать БД analytics
# 3. cd C:\Users\Dark_Angel\Desktop\hack\analytics-platform
# 4. .\scripts\setup-windows.bat
# 5. .\scripts\build-frontend.bat
# 6. 3 окна PowerShell с:
#    - php artisan serve
#    - php artisan queue:work
#    - php artisan schedule:work
# 7. Открыть http://127.0.0.1:8000
```

---

## Дополнительные ссылки

- Laravel документация: https://laravel.com/docs
- PHP: https://www.php.net/downloads
- Composer: https://getcomposer.org/
- MySQL: https://dev.mysql.com/downloads/
- Redis: https://memurai.com/ или WSL
- Node.js: https://nodejs.org/ (уже установлен)

Если возникнут ошибки при запуске — пришлите вывод ошибок и помогу отладить.
