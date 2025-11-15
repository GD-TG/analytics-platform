# Запуск проекта в Docker (локально)

Эти инструкции позволяют поднять все сервисы (MySQL, Redis, PHP-FPM, Nginx, сборка фронтенда) с помощью Docker Compose.

Требования:
- Docker Desktop (Windows) или Docker Engine + docker-compose
- ~4+ GB свободной памяти

Шаги:

1) Клонируйте репозиторий и перейдите в корень проекта.

2) Скопируйте `.env.docker` в `.env` (опционально — скрипт сделает это автоматически внутри контейнера, если `.env` отсутствует):

```powershell
Copy-Item .env.docker .env -Force
```

3) Запустите скрипт (PowerShell):

```powershell
cd C:\path\to\analytics-platform
.\scripts\start-docker.ps1
```

Этот скрипт выполнит:
- `docker-compose up -d --build`
- `composer install` внутри контейнера PHP
- `php artisan key:generate`
- `php artisan migrate --force`
- `php artisan db:seed --force` (если сидеры есть)
- `php artisan storage:link`
- `docker-compose run --rm node` — сборка фронтенда и копирование результата в `public/`

4) Откройте приложение: http://localhost:8080

Запуск отдельных команд (если нужно вручную):

```powershell
# Сборка и запуск
docker-compose up -d --build

# Выполнить консольные команды в контейнере app
docker-compose exec app bash
# внутри контейнера:
# composer install
# php artisan key:generate
# php artisan migrate --force
# php artisan db:seed --force
# php artisan storage:link

# Сборка фронтенда (локально без контейнера)
cd frontend
npm ci
npm run build
# затем скопировать dist -> public
```

Проблемы и отладка:
- Логи сервиса: `docker-compose logs -f web` или `docker-compose logs -f app`.
- Проверить статус контейнеров: `docker ps`.
- Если миграции падают — проверьте переменные окружения в `.env` и доступ к БД (порт 3306).

Если хотите — могу добавить `docker-compose.override.yml` для разработки с Vite dev-server, либо подготовить Dockerfile для production-готовой сборки.
