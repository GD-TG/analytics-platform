# ✅ Sprint 2.H: Scheduled Sync (CRON)

## Реализовано

### 1. **SyncCommand** — ручная синхронизация
**Файл:** `app/Console/Commands/SyncCommand.php`

Команда для синхронизации данных Yandex Metrika:

#### Использование:
```bash
# Sync all active accounts and counters
php artisan analytics:sync

# Sync specific account
php artisan analytics:sync --account-id=12345

# Sync specific counter
php artisan analytics:sync --counter-id=87654321

# Force sync (ignore last_fetched_at)
php artisan analytics:sync --force
```

#### Что происходит:
1. Получает все активные аккаунты (where revoked = false)
2. Для каждого аккаунта получает активные счётчики
3. Проверяет `last_fetched_at` vs `SYNC_INTERVAL_MINUTES`
4. Если нужна синхронизация → queues `FetchMetrikaJob`
5. Обновляет `last_fetched_at` на текущее время
6. Выводит summary с успехами/ошибками

#### Пример вывода:
```
🔄 Starting Metrika data sync...
Found 2 active account(s)

Account: 1 (User: 1)
  Found 2 counter(s)
  ✅ Counter 12345678: queued for sync
  ⏭️  Counter 87654321: recently synced, skipping

Account: 2 (User: 2)
  Found 1 counter(s)
  ✅ Counter 99999999: queued for sync

═══════════════════════════════════
Sync Summary:
  Total counters: 3
  Queued: 2
═══════════════════════════════════
✅ Sync completed in 0.45s
```

### 2. **SyncStatusCommand** — проверка статуса
**Файл:** `app/Console/Commands/SyncStatusCommand.php`

Команда для просмотра статуса последней синхронизации:

#### Использование:
```bash
php artisan analytics:sync-status
```

#### Пример вывода:
```
📊 Sync Status Report
═══════════════════════════════════════════════════

👤 Account 1 (User: 1)
   Status: ✅ ACTIVE
   Counters: 2
   ✅ Counter 12345678: OK (synced 5m ago, next in 55m)
   🔴 Counter 87654321: OVERDUE (last sync 120m ago)

👤 Account 2 (User: 2)
   Status: ✅ ACTIVE
   Counters: 1
   ⏳ Counter 99999999: PENDING (never synced)

═══════════════════════════════════════════════════
Summary:
   Total counters: 3
   ✅ In sync: 1
   ⏳ Pending: 1
   🔴 Overdue: 1
   Overall: 33%

⏰ Next scheduled sync: in ~60 minutes
   (Run 'php artisan analytics:sync --force' to sync now)
```

### 3. **Laravel Scheduler Configuration**
**Файл:** `app/Console/Kernel.php`

Добавлен периодический запуск `analytics:sync` команды:

```php
// app/Console/Kernel.php
protected function schedule(Schedule $schedule): void
{
    // Синхронизация данных каждый час (configurable)
    $syncInterval = env('SYNC_INTERVAL_MINUTES', 60);
    $schedule->command('analytics:sync')
             ->everyMinutes($syncInterval)
             ->timezone('Europe/Moscow')
             ->withoutOverlapping()
             ->onOneServer()
             ->appendOutputTo(storage_path('logs/sync.log'));
}
```

**Параметры:**
- `everyMinutes($syncInterval)` — запускать каждые N минут
- `withoutOverlapping()` — не запускать, если предыдущая выполняется
- `onOneServer()` — только на одном сервере (для load balancer'ов)
- `appendOutputTo()` — логировать в файл

### 4. **Database Migrations**
**Файл:** `database/migrations/2024_01_01_000002_create_yandex_counters_table.php`

Добавлены колонки в таблицу `yandex_counters`:

```php
$table->boolean('active')->default(true)->index();
$table->timestamp('last_fetched_at')->nullable()->index();
```

**Назначение:**
- `active` — можно отключить счётчик без удаления
- `last_fetched_at` — timestamp последней синхронизации (индексирована для быстрого поиска)

### 5. **Конфигурация метрики**
**Файл:** `config/metrika.php`

Требуется добавить в конфигурацию (уже добавлено):
```php
'sync_interval_minutes' => env('METRIKA_SYNC_INTERVAL_MINUTES', 60),
```

## Как это работает

### Цикл синхронизации

```
┌─────────────────────────────┐
│   Laravel Scheduler         │ (runs every X minutes)
│   executes analytics:sync   │
└────────────┬────────────────┘
             │
             ▼
┌─────────────────────────────┐
│   SyncCommand               │
│   1. Get active accounts    │
│   2. Get active counters    │
│   3. Check last_fetched_at  │
└────────────┬────────────────┘
             │
             ▼
┌─────────────────────────────┐
│   FetchMetrikaJob (Queue)   │
│   1. Get access token       │
│   2. Call Metrika API       │
│   3. Retry if 429/5xx       │
│   4. Parse response         │
│   5. Store raw data         │
└────────────┬────────────────┘
             │
             ▼
┌─────────────────────────────┐
│   Aggregate metrics monthly │
│   (via ParseMetrikaJob)     │
└─────────────────────────────┘
```

### Запуск Scheduler локально

```bash
# Terminal 1: Start Laravel server
php artisan serve

# Terminal 2: Run scheduler
php artisan schedule:work

# Terminal 3: Start queue worker
php artisan queue:work redis
```

## Переменные окружения (.env)

```env
# Sync interval (minutes)
METRIKA_SYNC_INTERVAL_MINUTES=60

# Queue (for async fetch jobs)
QUEUE_CONNECTION=redis

# Scheduler timezone
APP_TIMEZONE=Europe/Moscow
```

## Примеры команд

### Синхронизировать всё прямо сейчас
```bash
php artisan analytics:sync --force
```

### Синхронизировать конкретный аккаунт
```bash
php artisan analytics:sync --account-id=1
```

### Синхронизировать конкретный счётчик
```bash
php artisan analytics:sync --counter-id=12345678
```

### Проверить статус
```bash
php artisan analytics:sync-status
```

### Посмотреть логи синхронизации
```bash
tail -f storage/logs/sync.log
```

## Интеграция с очередью

Каждая синхронизация queues `FetchMetrikaJob`:

```php
FetchMetrikaJob::dispatch(
    accountId: $account->id,
    counterId: $counter->id,
    userId: $account->user_id
);
```

Job затем обрабатывается очередью (Redis), что позволяет:
- ✅ Не блокировать команду
- ✅ Обрабатывать несколько job'ов параллельно
- ✅ Автоматически retry если job упал
- ✅ Логировать failures

## Deployment

### Cron на production сервере

Вместо `php artisan schedule:work`, используй cron job:

```bash
# Add to crontab
* * * * * cd /path/to/analytics-platform && php artisan schedule:run >> /dev/null 2>&1
```

Это запускает Laravel Scheduler каждую минуту (как обычный cron).

### С Docker

```dockerfile
# In Dockerfile
RUN echo "* * * * * cd /app && php artisan schedule:run >> /dev/null 2>&1" | crontab -
```

### С Supervisor (для queue worker)

```ini
[program:analytics-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3
autostart=true
autorestart=true
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/analytics-worker.log
```

## Статус

✅ **COMPLETED** — Scheduled sync готов к production использованию

**Дальше:**
- Sprint 2.I: Metrics dashboard (UI для статуса)
- Sprint 2.J: PDF export (отчёты)
- Sprint 2.K: AI insights (анализ)

