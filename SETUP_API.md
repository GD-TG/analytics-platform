# Настройка API Яндекс.Метрики и Яндекс.Директа

## Полученные credentials

- **Client ID**: `081345a9f48742d18f6cbacf890cfb1b`
- **Client Secret**: `bd90a984306e4d3bb8cdea8fb492610e`

## Настройка .env файла

Добавьте следующие переменные в ваш `.env` файл:

```env
# Yandex OAuth credentials
YANDEX_CLIENT_ID=081345a9f48742d18f6cbacf890cfb1b
YANDEX_CLIENT_SECRET=bd90a984306e4d3bb8cdea8fb492610e
YANDEX_OAUTH_TOKEN=ваш_oauth_токен_здесь
YANDEX_DEFAULT_CURRENCY=RUB
DEFAULT_TIMEZONE=Europe/Moscow

# Direct API (использует тот же OAuth токен)
DIRECT_CLIENT_LOGIN=логин_клиента_в_директе
```

## Получение OAuth токена

Для работы API необходим OAuth токен. Получить его можно следующими способами:

### Способ 1: Через OAuth авторизацию (рекомендуется)

1. Перейдите по ссылке:
```
https://oauth.yandex.ru/authorize?response_type=token&client_id=081345a9f48742d18f6cbacf890cfb1b
```

2. Авторизуйтесь и разрешите доступ приложению
3. Скопируйте токен из URL (параметр `access_token`)
4. Добавьте токен в `.env` как `YANDEX_OAUTH_TOKEN`

### Способ 2: Через API (для автоматизации)

Используйте Client ID и Client Secret для получения токена программно.

## Что было исправлено

1. ✅ **DirectClient** - реализованы реальные запросы к API Яндекс.Директа
2. ✅ **MetrikaClient** - использует OAuth токен из конфигурации
3. ✅ **FetchDirectJob** - правильно обрабатывает DirectAccount и сохраняет данные
4. ✅ **ParseDirectResponseJob** - создан для обработки ответов Direct API
5. ✅ **SyncDailyCommand** - исправлена логика для работы с directAccounts
6. ✅ **Конфигурация** - обновлена для использования OAuth токена

## Проверка работы

После настройки `.env` файла:

1. Убедитесь, что в базе данных есть проекты с привязанными счетчиками Метрики и аккаунтами Директа
2. Запустите синхронизацию:
```bash
php artisan analytics:sync-daily
```

3. Проверьте логи:
```bash
tail -f storage/logs/laravel.log
```

## Важные замечания

- OAuth токен имеет срок действия, его нужно обновлять периодически
- Для каждого проекта в Директе используется свой `Client-Login` (указывается в таблице `direct_accounts`)
- Данные сохраняются в таблицы:
  - `metrics_monthly` - общие метрики Метрики
  - `metrics_age_monthly` - метрики по возрастным группам
  - `direct_campaign_monthly` - статистика по кампаниям Директа
  - `direct_totals_monthly` - итоги по всем кампаниям проекта
  - `raw_api_responses` - сырые ответы API (для отладки)

