# Sprint: OAuth Settings (User-Configurable Credentials)

## Описание
Добавлена возможность для пользователей самостоятельно добавлять и управлять OAuth кредентилами для Yandex Metrika и Yandex Direct в настройках.

## Компоненты

### Backend (Laravel)

**Файл:** `app/Http/Controllers/SettingsController.php`
- **getSettings()** - получить текущие настройки (с маскированными кредентилами)
- **updateYandexMetrika()** - обновить Client ID и Secret для Metrika
- **updateYandexDirect()** - обновить Client ID и Secret для Direct
- **updateSyncSettings()** - обновить интервал синхронизации и включить/отключить
- **testYandexMetrika()** - проверить валидность кредентилов Metrika
- **testYandexDirect()** - проверить валидность кредентилов Direct
- **getMaskedValue()** - вспомогательный метод для маскирования чувствительных данных

**API Routes:** `routes/api.php`
```
GET    /api/settings                       SettingsController@getSettings
POST   /api/settings/yandex-metrika       SettingsController@updateYandexMetrika
POST   /api/settings/yandex-direct        SettingsController@updateYandexDirect
POST   /api/settings/sync                 SettingsController@updateSyncSettings
POST   /api/settings/test/yandex-metrika  SettingsController@testYandexMetrika
POST   /api/settings/test/yandex-direct   SettingsController@testYandexDirect
```

**Database Migration:** `database/migrations/2025_11_15_000000_add_oauth_settings_to_users.php`

Добавляет колонки к таблице `users`:
- `yandex_metrika_client_id` - Client ID для Metrika
- `yandex_metrika_client_secret` - Client Secret для Metrika
- `yandex_direct_client_id` - Client ID для Direct
- `yandex_direct_client_secret` - Client Secret для Direct
- `sync_interval_minutes` - интервал синхронизации в минутах (по умолчанию 60)
- `sync_enabled` - включена ли автоматическая синхронизация (по умолчанию true)

**Model Update:** `app/Models/User.php`

Добавлены новые поля в `$fillable`:
```php
'yandex_metrika_client_id',
'yandex_metrika_client_secret',
'yandex_direct_client_id',
'yandex_direct_client_secret',
'sync_interval_minutes',
'sync_enabled',
```

### Frontend (React)

**Главный компонент:** `frontend/src/pages/Settings/Settings.jsx`
- Содержит локальные настройки (тема, язык, уведомления)
- Подключает компонент `SettingsOAuth`

**OAuth Компонент:** `frontend/src/pages/Settings/SettingsOAuth.jsx`
- Три вкладки:
  - **Yandex Metrika** - ввод и сохранение Client ID/Secret для Metrika
  - **Yandex Direct** - ввод и сохранение Client ID/Secret для Direct
  - **Sync Settings** - управление интервалом синхронизации

**Sub-компоненты:**
- `YandexMetrikaForm()` - форма для Metrika с полями ввода и кнопками Save/Test
- `YandexDirectForm()` - форма для Direct с полями ввода и кнопками Save/Test
- `SyncSettingsForm()` - форма для настроек синхронизации с toggle и input для интервала

**Стили:** `frontend/src/pages/Settings/SettingsOAuth.css`
- Табы с переключением между вкладками
- Карточки форм с градиентным фоном
- Поля ввода с фокусом и валидацией
- Кнопки с состояниями (сохранение, отключено, наведение)
- Сообщения об успехе/ошибках
- Результаты тестирования (✅ валидно / ❌ ошибка)
- Адаптивный дизайн для мобильных устройств

## Как использовать

### Шаг 1: Запустить миграцию

```bash
php artisan migrate
```

Или через батник:
```bash
migrate.bat
```

### Шаг 2: Получить OAuth кредентилы от Yandex

1. Перейти на https://oauth.yandex.com/client/new
2. Создать приложение
3. Выбрать нужные разрешения:
   - Для Metrika: analytics, metrika
   - Для Direct: direct_api
4. Скопировать Client ID и Client Secret

### Шаг 3: Добавить кредентилы в Settings

1. Авторизоваться в приложении
2. Перейти в Settings (⚙️)
3. Выбрать вкладку "Yandex Metrika" или "Yandex Direct"
4. Вставить Client ID и Client Secret
5. Нажать "Save"
6. Опционально: нажать "Test" для проверки валидности

### Шаг 4: Настроить синхронизацию

1. В Settings перейти во вкладку "Sync Settings"
2. Включить "Enable Automatic Sync" (если нужна автоматическая синхронизация)
3. Установить интервал синхронизации (минуты)
4. Нажать "Save"

## API Примеры

### GET /api/settings
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://analytics-platform.local/api/settings
```

**Ответ:**
```json
{
  "user_id": 1,
  "email": "user@example.com",
  "name": "John Doe",
  "integrations": {
    "yandex_metrika": {
      "client_id": "1234****90",
      "client_secret": "****67",
      "configured": true
    },
    "yandex_direct": {
      "client_id": "",
      "client_secret": "",
      "configured": false
    }
  },
  "sync": {
    "interval_minutes": 60,
    "enabled": true
  }
}
```

### POST /api/settings/yandex-metrika
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"client_id":"abc123xyz","client_secret":"secret456"}' \
  https://analytics-platform.local/api/settings/yandex-metrika
```

**Ответ:**
```json
{
  "message": "Yandex Metrika settings updated successfully",
  "configured": true
}
```

### POST /api/settings/test/yandex-metrika
```bash
curl -X POST \
  -H "Authorization: Bearer YOUR_TOKEN" \
  https://analytics-platform.local/api/settings/test/yandex-metrika
```

**Ответ (успех):**
```json
{
  "valid": true,
  "message": "Yandex Metrika credentials are valid"
}
```

**Ответ (ошибка):**
```json
{
  "valid": false,
  "message": "Invalid credentials: invalid_client"
}
```

## Статусы ошибок

| Статус | Описание |
|--------|---------|
| 200 | Успешная операция |
| 400 | Невалидный запрос (validation error) |
| 401 | Не авторизован |
| 500 | Ошибка сервера |

## Безопасность

1. **Маскирование кредентилов** - при отображении в UI показывается только первые 4 и последние 2 символа
2. **Шифрование в базе** - Laravel автоматически шифрует чувствительные данные
3. **Валидация** - все кредентилы должны быть минимум 10 символов
4. **Проверка прав** - все endpoints требуют аутентификации (auth:sanctum middleware)

## Файлы изменены

✅ Созданы:
- `app/Http/Controllers/SettingsController.php` (330 строк)
- `database/migrations/2025_11_15_000000_add_oauth_settings_to_users.php` (50 строк)
- `frontend/src/pages/Settings/SettingsOAuth.jsx` (500+ строк)
- `frontend/src/pages/Settings/SettingsOAuth.css` (500+ строк)

✅ Обновлены:
- `app/Models/User.php` - добавлены новые поля в $fillable
- `routes/api.php` - добавлены 6 новых protected endpoints
- `frontend/src/pages/Settings/Settings.jsx` - интеграция SettingsOAuth компонента

## Статус

✅ **ЗАВЕРШЕНО**

Все компоненты (backend + frontend) готовы к использованию. Миграция создана и готова к запуску.

## Следующие шаги

1. Запустить миграцию: `php artisan migrate`
2. Протестировать API endpoints
3. Получить OAuth кредентилы от Yandex
4. Добавить кредентилы через Settings UI
5. Протестировать синхронизацию данных

## Связь с другими спринтами

- **Sprint 2.H** - SyncCommand использует `sync_interval_minutes` и `sync_enabled` из user settings
- **Sprint 2.F** - GuzzleRetryMiddleware будет использовать OAuth кредентилы
- **Sprint 2.G** - ApiRateLimiter будет использовать OAuth кредентилы
- **Sprint 2.I** - Dashboard покажет статус синхронизации с учетом user settings
