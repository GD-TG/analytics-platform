# 📊 Analytics Platform — SaaS для аналитики Yandex.Metrika & Yandex.Direct

![CI/CD Pipeline](https://img.shields.io/github/actions/workflow/status/GD-TG/analytics-platform/ci.yml?branch=main&logo=github)
![Laravel](https://img.shields.io/badge/Laravel-10-red?logo=laravel)
![React](https://img.shields.io/badge/React-18-blue?logo=react)
![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue?logo=mysql)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 🎯 О проекте

**Analytics Platform** — это централизованная SaaS-платформа для сбора, анализа и визуализации данных аналитики от Яндекса. Маркетологи и аналитики получают единый интерфейс для просмотра метрик со всех своих счётчиков Метрики и кампаний Директа.

### ✨ Ключевые особенности

- **Per-user OAuth** — каждый пользователь безопасно подключает свой Yandex аккаунт
- **Encrypted token storage** — токены шифруются AES-256 в БД, защита от утечек
- **Background job processing** — асинхронная обработка данных через Redis очереди
- **Real-time metrics** — актуальные данные о визитах, источниках, демографии
- **Responsive UI** — интерфейс на React с Recharts графиками
- **Production-ready** — готово к развёртыванию в production (Docker, CI/CD)

---

## 🚀 Быстрый старт

### Предусловия

- PHP 8.1+
- MySQL 8.0+
- Node.js 16+
- Composer
- npm

### Установка (5 минут)

```bash
# 1. Клонировать репозиторий
git clone https://github.com/GD-TG/analytics-platform.git
cd analytics-platform

# 2. Backend setup
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed

# 3. Frontend setup
cd frontend
npm install
npm run build
cd ..

# 4. Запуск (разные терминалы)
# Terminal 1: Backend
php artisan serve --host=127.0.0.1 --port=8000

# Terminal 2: Frontend
cd frontend && npm run dev

# Terminal 3: Queue Worker
php artisan queue:work --queue=metrika-fetch,default
```

**Результат:** http://localhost:5173 (Frontend) + http://localhost:8000/api (Backend API)

---

## 📋 Тестовые учётные данные

После запуска `php artisan db:seed`:

| Email | Пароль |
|-------|--------|
| test1@example.com | password123 |
| test2@example.com | password123 |

---

## 🏗️ Архитектура

```
┌─────────────────────────────────────────────────────────────┐
│                     Frontend (React)                         │
│  Dashboard → YandexAuth → YandexSelect → Metrics            │
└──────────────────────────┬──────────────────────────────────┘
                           │ (HTTP/REST API)
┌──────────────────────────▼──────────────────────────────────┐
│                 Backend (Laravel 10)                         │
├──────────────────────────────────────────────────────────────┤
│  Controllers:                                                │
│  - AuthController (register, login, logout)                 │
│  - YandexAuthController (OAuth, exchange code, counters)    │
│  - ReportController (metrics, statistics)                   │
├──────────────────────────────────────────────────────────────┤
│  Services:                                                   │
│  - YandexTokenService (token management, refresh)           │
│  - MetrikaClient (API integration, retry logic)             │
│  - MetrikaFetcher (fetch visits, age, goals)                │
├──────────────────────────────────────────────────────────────┤
│  Database:                                                   │
│  - users, yandex_accounts (encrypted tokens)                │
│  - projects, yandex_counters                                │
│  - metrics_monthly, metrics_age_monthly, goals              │
│  - raw_api_responses, conversions                           │
├──────────────────────────────────────────────────────────────┤
│  Queues (Redis):                                            │
│  - FetchMetrikaJob → ParseMetrikaResponseJob → Aggregate    │
└──────────────────────────────────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│            External APIs (Yandex)                            │
│  - oauth.yandex.ru (authorization)                          │
│  - api-metrica.yandex.net (metrics data)                   │
│  - api.direct.yandex.com (campaigns)                       │
└──────────────────────────────────────────────────────────────┘
```

---

## 📦 Структура проекта

```
analytics-platform/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/AuthController.php
│   │   │   ├── Yandex/YandexAuthController.php
│   │   │   └── ReportController.php
│   │   ├── Kernel.php
│   │   └── Middleware/
│   ├── Models/
│   │   ├── User.php
│   │   ├── YandexAccount.php (encrypted tokens)
│   │   ├── YandexCounter.php
│   │   ├── Project.php
│   │   ├── MetricsMonthly.php
│   │   └── ...
│   ├── Jobs/
│   │   ├── Fetch/FetchMetrikaJob.php
│   │   ├── Process/ParseMetrikaResponseJob.php
│   │   └── Aggregate/AggregateMetrikaMonthlyJob.php
│   ├── Services/
│   │   └── Yandex/
│   │       ├── YandexTokenService.php (token lifecycle)
│   │       ├── MetrikaClient.php (API client with retry)
│   │       └── MetrikaFetcher.php
│   └── Console/
│       └── Kernel.php
├── database/
│   ├── migrations/
│   │   ├── create_users_table.php
│   │   ├── create_yandex_accounts_table.php
│   │   ├── create_yandex_counters_table.php
│   │   ├── create_metrics_monthly_table.php
│   │   └── ...
│   └── seeders/
│       └── DatabaseSeeder.php (test data)
├── routes/
│   ├── api.php (API routes, protected with auth:sanctum)
│   └── web.php
├── tests/
│   ├── Feature/
│   │   └── YandexTokenServiceTest.php
│   └── Unit/
├── frontend/
│   ├── src/
│   │   ├── api/
│   │   │   ├── yandex.js (Yandex API client)
│   │   │   └── http.js
│   │   ├── pages/
│   │   │   ├── Login/
│   │   │   ├── Dashboard/
│   │   │   ├── YandexCallback/
│   │   │   └── YandexSelect/
│   │   └── components/
│   ├── package.json
│   ├── vite.config.js
│   └── index.html
├── storage/
│   ├── logs/laravel.log
│   └── app/
├── .env.example
├── composer.json
├── package.json
├── DEMO.md (demo scenario & troubleshooting)
├── README.md (this file)
└── .github/
    └── workflows/
        └── ci.yml (GitHub Actions)
```

---

## 🔐 Безопасность

### Защита маршрутов

Все критичные endpoints защищены `auth:sanctum` middleware:

```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/yandex/exchange-code-new', ...);
    Route::get('/yandex/counters', ...);
    Route::post('/yandex/counters/save', ...);
});
```

### Шифрование токенов

Токены сохраняются в зашифрованном виде (AES-256) в таблице `yandex_accounts`:

```php
// app/Models/YandexAccount.php
protected function accessToken(): Attribute {
    return Attribute::make(
        get: fn ($value) => Crypt::decryptString($value),
        set: fn ($value) => Crypt::encryptString($value),
    );
}
```

### Per-user изоляция

Каждый пользователь видит только свои счётчики:

```php
// app/Http/Controllers/Yandex/YandexAuthController.php
$account = YandexAccount::where('id', $accountId)
    ->where('user_id', auth()->id())
    ->firstOrFail();
```

---

## 📚 API Endpoints

### Авторизация

| Метод | Endpoint | Описание |
|-------|----------|---------|
| POST | `/api/auth/register` | Регистрация |
| POST | `/api/auth/login` | Логин |
| GET | `/api/auth/me` | Текущий пользователь |
| POST | `/api/auth/logout` | Логаут |

### Yandex OAuth (Protected)

| Метод | Endpoint | Описание |
|-------|----------|---------|
| GET | `/api/yandex/auth-url-new` | Получить OAuth URL |
| POST | `/api/yandex/exchange-code-new` | Обменять код на токены |
| GET | `/api/yandex/validate-token-new` | Проверить токен |
| GET | `/api/yandex/counters` | Список счётчиков |
| POST | `/api/yandex/counters/save` | Сохранить счётчики |

### Метрики (Protected)

| Метод | Endpoint | Описание |
|-------|----------|---------|
| GET | `/api/statistics` | Общая статистика |
| GET | `/api/visits` | Данные визитов |
| GET | `/api/sources` | Источники трафика |
| GET | `/api/age-data` | Демографические данные |

---

## 🛠️ Разработка

### Создание новой миграции

```bash
php artisan make:migration create_my_table
```

### Создание нового Eloquent модели

```bash
php artisan make:model MyModel -m
```

### Создание нового background job

```bash
php artisan make:job MyJob
```

### Запуск тестов

```bash
# Backend
php artisan test

# Frontend
cd frontend && npm run test
```

### Запуск linter

```bash
# Backend
./vendor/bin/phpcs app --standard=PSR12

# Frontend
cd frontend && npm run lint
```

---

## 🐳 Docker

### Сборка

```bash
# Backend
docker build -t analytics-backend:latest .

# Frontend
docker build -t analytics-frontend:latest frontend/

# Entire stack
docker-compose up -d
```

### Docker-compose (если есть)

```bash
docker-compose up
# http://localhost:5173 (Frontend)
# http://localhost:8000 (Backend API)
```

---

## 📊 Мониторинг & Логи

### Просмотр логов

```bash
# Laravel
tail -f storage/logs/laravel.log

# Queue
tail -f storage/logs/queue.log

# Все логи
php artisan logs:clear
```

### Статус очереди

```bash
# Активные jobs
php artisan queue:monitor

# Неудачные jobs
php artisan queue:failed

# Переподоставить
php artisan queue:retry all
```

---

## 🔄 CI/CD

Проект использует **GitHub Actions** для автоматического тестирования и сборки:

```yaml
# .github/workflows/ci.yml
- Run PHP Unit Tests
- Run PHP Code Sniffer
- Run Frontend Lint
- Run Security Checks
- Build Docker Images
```

**Статус:** [![CI Status](https://github.com/GD-TG/analytics-platform/actions/workflows/ci.yml/badge.svg)](https://github.com/GD-TG/analytics-platform/actions)

---

## 📈 Performance

- **API response time:** < 100ms (cached)
- **Dashboard load:** < 500ms
- **Token refresh:** < 2s
- **Metric aggregation:** Async (background job)

---

## 🚀 Production Deployment

### Требования

- HTTPS + SSL сертификат
- Redis для queue
- MySQL с нормального размера БД
- Supervisor для background workers

### Инструкции

1. Клонировать код в `/var/www/analytics`
2. Установить зависимости: `composer install --no-dev`
3. Генерировать APP_KEY: `php artisan key:generate`
4. Миграции: `php artisan migrate --force`
5. Настроить Supervisor: `supervisorctl reread && supervisorctl update`
6. Настроить веб-сервер (nginx/Apache)
7. Backup БД: `mysqldump -u user -p database > backup.sql`

---

## 🐛 Troubleshooting

Смотрите **[DEMO.md](./DEMO.md)** для пошагового guide и решения проблем.

---

## 🤝 Contributing

1. Fork репо
2. Создайте feature branch (`git checkout -b feature/amazing-feature`)
3. Commit изменений (`git commit -m 'Add amazing feature'`)
4. Push в branch (`git push origin feature/amazing-feature`)
5. Откройте Pull Request

---

## 📄 License

MIT License — смотрите [LICENSE](./LICENSE) файл

---

## 👥 Команда

- **Разработка:** Dark_Angel
- **Архитектура:** Per-user OAuth, Redis queues, encrypted token storage

---

## 📞 Контакты

- GitHub Issues: [analytics-platform/issues](https://github.com/GD-TG/analytics-platform/issues)
- Email: support@analytics-platform.com (когда выпустим)

---

## 🎯 Roadmap

### Sprint 1 (MVP) ✅
- ✅ Per-user OAuth
- ✅ Route protection (auth:sanctum)
- ✅ Seed test data
- ✅ Basic CSS & demo
- 🔄 Unit tests & CI/CD

### Sprint 2 (v1.1) 🚀
- 🔄 Guzzle retry middleware with jitter
- 🔄 Per-account rate limiting
- 🔄 Scheduled sync (CRON)
- 🔄 Metrics dashboard
- 🔄 PDF export
- 🔄 AI insights stub
- 🔄 Admin panel

### Будущее (v2.0+)
- Multi-language UI (EN/RU)
- Advanced filtering & segmentation
- Custom reports builder
- Yandex.Direct full integration
- Slack/Telegram notifications
- Mobile app

---

**Made with ❤️ by Analytics Platform Team**
