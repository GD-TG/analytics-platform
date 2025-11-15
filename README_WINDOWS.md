# Запуск Analytics Platform на Windows (без Docker)

Полный гайд установки и запуска всех сервисов на Windows.

## Требования

- Windows 10+ (или WSL2 для Linux-инструментов)
- 4+ GB RAM
- 2+ GB свободного места на диске

## Шаг 1: Установка PHP 8.2+

### Способ A: Использование Chocolatey (рекомендуется)

Откройте PowerShell от администратора и выполните:

```powershell
choco install php --version=8.2 -y
choco install composer -y
```

### Способ B: Ручная установка

1. Скачайте PHP 8.2 thread-safe для Windows: https://www.php.net/downloads.php
2. Распакуйте в `C:\php` (или любую папку)
3. Скопируйте `php.ini-production` в `php.ini`
4. Добавьте `C:\php` в переменную окружения `PATH`
5. Скачайте Composer: https://getcomposer.org/Composer-Setup.exe (установщик)

### Проверка установки

```powershell
php -v
composer --version
```

Должны вывести версии.

### Включите расширения в php.ini

Отройте `C:\php\php.ini` и раскомментируйте/добавьте:

```ini
extension=pdo_mysql
extension=redis
extension=gd
extension=zip
extension=mbstring
extension=intl
extension=bcmath
```

Перезагрузите консоль.

---

## Шаг 2: Установка MySQL 8.0

### Способ A: Chocolatey

```powershell
choco install mysql -y
```

### Способ B: Скачать установщик

https://dev.mysql.com/downloads/mysql/

Во время установки:
- Выберите "MySQL Server"
- Порт: 3306 (по умолчанию)
- Root пароль: задайте (запомните!)

### Создание БД и пользователя

Откройте PowerShell и подключитесь к MySQL:

```powershell
mysql -u root -p
# введите пароль root
```

Затем выполните в MySQL:

```sql
CREATE DATABASE analytics CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'analytics'@'localhost' IDENTIFIED BY 'analytics';
GRANT ALL PRIVILEGES ON analytics.* TO 'analytics'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Шаг 3: Установка Redis

### Способ A: WSL2 (рекомендуется)

Если у вас установлена WSL2:

```powershell
# В WSL2 терминале
wsl
sudo apt-get update
sudo apt-get install redis-server -y
redis-server --daemonize yes
```

### Способ B: Windows (готовый .exe)

Скачайте готовую сборку Redis для Windows:
https://github.com/microsoftarchive/redis/releases

или используйте готовый облик (например, с Memurai):
https://memurai.com/

Распакуйте и запустите `redis-server.exe` (будет слушать на 6379).

### Проверка

```powershell
redis-cli ping
# Должно вывести PONG
```

---

## Шаг 4: Установка Node.js

### Способ A: Chocolatey

```powershell
choco install nodejs -y
```

### Способ B: Скачать установщик

https://nodejs.org/en/download/ (LTS версия)

### Проверка

```powershell
node --version
npm --version
```

---

## Шаг 5: Установка зависимостей Laravel

В корне проекта откройте PowerShell и выполните:

```powershell
cd C:\Users\Dark_Angel\Desktop\hack\analytics-platform

# Скопируйте .env.docker в .env
Copy-Item .env.docker .env -Force

# Установите PHP-зависимости
composer install

# Сгенерируйте APP_KEY
php artisan key:generate

# Выполните миграции
php artisan migrate --seed

# Создайте symlink для storage
php artisan storage:link
```

---

## Шаг 6: Сборка фронтенда

```powershell
cd frontend

# Установите Node-зависимости
npm ci

# Соберите проект
npm run build

# Скопируйте артефакты в public
Copy-Item -Recurse dist\* ..\public -Force
```

---

## Шаг 7: Запуск всех сервисов

Откройте 4 отдельных окна PowerShell (администратор):

### Окно 1: веб-сервер Laravel

```powershell
cd C:\Users\Dark_Angel\Desktop\hack\analytics-platform
php artisan serve --host=127.0.0.1 --port=8000
```

Откройте в браузере: **http://127.0.0.1:8000**

### Окно 2: очередь (queue worker)

```powershell
cd C:\Users\Dark_Angel\Desktop\hack\analytics-platform
php artisan queue:work --sleep=3 --tries=3
```

### Окно 3: планировщик (scheduler)

```powershell
cd C:\Users\Dark_Angel\Desktop\hack\analytics-platform
php artisan schedule:work
```

### Окно 4: MySQL (если не установлена как сервис)

```powershell
# Если MySQL не запущена как сервис, запустите вручную
"C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld"
# или используйте "Services" (services.msc) для запуска MySQL Service
```

### Для Redis

Если используете WSL2:
```powershell
wsl
redis-server --daemonize yes
```

Или если установили готовый .exe:
```powershell
"C:\path\to\redis-server.exe"
```

---

## Быстрый запуск (батники)

Я добавил батники в папку `scripts/` для автоматизации:

- `scripts/setup-windows.bat` — первичная установка (composer install, key, migrate, seed)
- `scripts/run-all.bat` — запускает все сервисы в отдельных окнах

Запустите:

```powershell
.\scripts\setup-windows.bat  # только первый раз
.\scripts\run-all.bat        # каждый раз для запуска
```

---

## Проверка всё работает

После запуска всех сервисов:

1. Откройте http://127.0.0.1:8000 — должна загрузиться главная страница
2. API доступен по http://127.0.0.1:8000/api/
3. Очередь должна обрабатывать задачи в окне 2
4. Планировщик должен выполнять кроны в окне 3

---

## Проблемы и решения

| Проблема | Решение |
|----------|----------|
| "php: command not found" | Добавьте PHP в PATH или переустановите через Chocolatey |
| "MySQL error: Access denied" | Проверьте пароль в `.env` (`DB_PASSWORD=`) |
| "Redis connection refused" | Убедитесь, что Redis запущен (`redis-cli ping`) |
| "Port 8000 already in use" | Измените порт: `php artisan serve --port=8001` |
| "CORS ошибки" | Проверьте `config/cors.php` и убедитесь, что фронтенд обращается к правильному URL |
| "Migration failed" | Проверьте логи: `php artisan migrate --verbose` |

---

## Переменные окружения (.env)

После первого `php artisan key:generate` в `.env` должны быть:

```ini
APP_KEY=base64:...  # сгенерируется автоматически

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=analytics
DB_USERNAME=analytics
DB_PASSWORD=analytics

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
```

Если используете WSL Redis, `REDIS_HOST` может быть `localhost` или IP адрес WSL.

---

## Остановка всех сервисов

В каждом окне PowerShell нажмите **Ctrl+C** для остановки.

Для MySQL:
```powershell
# или через services.msc остановите MySQL Service
```

---

## Дополнительно

- Документация Laravel: https://laravel.com/docs
- Redis для Windows: https://memurai.com/
- Решение проблем: см. логи в `storage/logs/laravel.log`
