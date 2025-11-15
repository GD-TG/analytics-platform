# 📋 Где хранятся и используются переменные Yandex

## 📁 Расположение файлов

### 1. `.env` файл (корень проекта)
```
C:\PlanicaTask\analytics-platform\.env
```

**Содержит:**
```env
YANDEX_CLIENT_ID=081345a9f48742d18f6cbacf890cfb1b
YANDEX_CLIENT_SECRET=bd90a984306e4d3bb8cdea8fb492610e
YANDEX_OAUTH_TOKEN=ваш_oauth_токен_здесь
```

### 2. Конфигурационный файл
```
config/integrations.php
```

**Читает переменные из .env:**
```php
return [
    'yandex' => [
        'client_id' => env('YANDEX_CLIENT_ID'),
        'client_secret' => env('YANDEX_CLIENT_SECRET'),
        'oauth_token' => env('YANDEX_OAUTH_TOKEN'),
        'default_currency' => env('YANDEX_DEFAULT_CURRENCY', 'RUB'),
        'default_timezone' => env('DEFAULT_TIMEZONE', 'Europe/Moscow'),
    ],
];
```

## 🔄 Как это работает

### Схема работы:

```
.env файл
    ↓
config/integrations.php (читает через env())
    ↓
Config::get('integrations.yandex.client_id')
    ↓
YandexOAuthService, MetrikaClient, DirectClient
```

## 📍 Где используются переменные

### 1. **YandexOAuthService** (`app/Services/Yandex/YandexOAuthService.php`)

```php
public function __construct()
{
    // Читает из config/integrations.php
    $this->clientId = Config::get('integrations.yandex.client_id');
    $this->clientSecret = Config::get('integrations.yandex.client_secret');
}
```

**Используется для:**
- Получения URL авторизации
- Обмена кода на токен
- Валидации токена

### 2. **MetrikaClient** (`app/Services/Metrika/MetrikaClient.php`)

```php
public function __construct() 
{
    // Читает из config/metrika.php
    $this->token = Config::get('metrika.api_token');
    // config/metrika.php читает из .env: env('YANDEX_OAUTH_TOKEN')
}
```

**Конфигурация:** `config/metrika.php`
```php
return [
    'api_token' => env('YANDEX_OAUTH_TOKEN'),
    // ...
];
```

**Используется для:**
- Запросов к API Яндекс.Метрики
- Получения данных счетчиков
- Синхронизации метрик

### 3. **DirectClient** (`app/Services/Direct/DirectClient.php`)

```php
public function __construct()
{
    // Сначала пытается получить из config/direct.php, 
    // если нет - берет из config/integrations.php
    $this->token = Config::get('direct.api_token') 
        ?: Config::get('integrations.yandex.oauth_token');
}
```

**Конфигурация:** `config/direct.php`
```php
return [
    'api_token' => env('YANDEX_OAUTH_TOKEN'),
    // ...
];
```

**Используется для:**
- Запросов к API Яндекс.Директа
- Получения данных кампаний
- Синхронизации рекламных данных

### 4. **YandexAuthController** (`app/Http/Controllers/Yandex/YandexAuthController.php`)

```php
public function validateToken(Request $request): JsonResponse
{
    // Читает токен из запроса или из конфигурации
    $token = $request->get('token') 
        ?? config('integrations.yandex.oauth_token');
    // ...
}
```

**Используется для:**
- Валидации OAuth токена
- Проверки работоспособности API

## 🔑 Типы токенов

### 1. **YANDEX_OAUTH_TOKEN** (в .env)
- **Назначение:** Токен для работы с API Яндекс.Метрики и Яндекс.Директа
- **Где хранится:** В файле `.env`
- **Как получить:** Через OAuth авторизацию
- **Используется:** В `MetrikaClient`, `DirectClient` для API запросов

### 2. **Токен пользователя** (в базе данных)
- **Назначение:** Токен для авторизации пользователей через Yandex ID
- **Где хранится:** В таблице `personal_access_tokens` (Laravel Sanctum)
- **Как получить:** Автоматически при авторизации через Yandex ID
- **Используется:** В `AuthController` для аутентификации пользователей

## 📝 Как обновить переменные

### 1. Отредактируйте `.env` файл:
```env
YANDEX_CLIENT_ID=новый_client_id
YANDEX_CLIENT_SECRET=новый_client_secret
YANDEX_OAUTH_TOKEN=новый_токен
```

### 2. Очистите кеш конфигурации:
```bash
php artisan config:clear
```

### 3. Перезапустите сервер (если нужно):
```bash
php artisan serve
```

## 🔍 Проверка переменных

### Проверить, что переменные загружены:

```bash
php artisan tinker
```

```php
Config::get('integrations.yandex.client_id');
Config::get('integrations.yandex.client_secret');
Config::get('integrations.yandex.oauth_token');
```

### Проверить через API:

```bash
# Проверить токен
curl http://localhost:8000/api/yandex/validate-token

# Получить URL авторизации
curl http://localhost:8000/api/yandex/auth-url
```

## ⚠️ Важно

1. **`.env` файл НЕ коммитится в Git** (уже в `.gitignore`)
2. **Токены хранятся только в `.env`**, не в базе данных
3. **После изменения `.env` нужно очистить кеш:** `php artisan config:clear`
4. **YANDEX_OAUTH_TOKEN** - это токен для API, не для пользователей
5. **Токены пользователей** хранятся в БД через Laravel Sanctum

## 📚 Связанные файлы

### Конфигурационные файлы:
- `.env` - переменные окружения (корень проекта)
- `config/integrations.php` - основная конфигурация Yandex
- `config/metrika.php` - конфигурация для Метрики
- `config/direct.php` - конфигурация для Директа

### Сервисы:
- `app/Services/Yandex/YandexOAuthService.php` - OAuth сервис (Client ID, Secret)
- `app/Services/Metrika/MetrikaClient.php` - клиент Метрики (OAuth токен)
- `app/Services/Direct/DirectClient.php` - клиент Директа (OAuth токен)

### Контроллеры:
- `app/Http/Controllers/Auth/AuthController.php` - авторизация пользователей через Yandex ID
- `app/Http/Controllers/Yandex/YandexAuthController.php` - управление OAuth токенами

## 🔄 Полная схема работы

```
.env файл
├── YANDEX_CLIENT_ID
├── YANDEX_CLIENT_SECRET
└── YANDEX_OAUTH_TOKEN
    │
    ├──→ config/integrations.php
    │   └──→ YandexOAuthService (Client ID, Secret)
    │   └──→ YandexAuthController (валидация токена)
    │
    ├──→ config/metrika.php
    │   └──→ MetrikaClient (OAuth токен для API Метрики)
    │
    └──→ config/direct.php
        └──→ DirectClient (OAuth токен для API Директа)
```

