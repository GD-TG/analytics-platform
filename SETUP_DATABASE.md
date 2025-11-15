# Настройка базы данных MySQL

## Шаги для настройки:

1. **Создайте базу данных MySQL:**
   ```sql
   CREATE DATABASE analytics_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Настройте .env файл:**
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=analytics_platform
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

3. **Выполните миграции:**
   ```bash
   php artisan migrate
   ```

4. **Создайте первого пользователя (опционально):**
   ```bash
   php artisan tinker
   ```
   Затем в консоли:
   ```php
   $user = \App\Models\User::create([
       'name' => 'Admin',
       'email' => 'admin@example.com',
       'password' => bcrypt('password'),
       'role' => 'admin',
   ]);
   ```

## Структура базы данных:

- `users` - пользователи системы
- `personal_access_tokens` - токены для API авторизации
- `projects` - проекты
- `yandex_counters` - счетчики Яндекс.Метрики
- `direct_accounts` - аккаунты Яндекс.Директа
- `direct_campaigns` - кампании Директа
- `metrics_monthly` - месячные метрики
- `metrics_age_monthly` - возрастные метрики
- `direct_totals_monthly` - итоги по Директу
- `direct_campaign_monthly` - данные по кампаниям
- `seo_queries_monthly` - SEO запросы
- `raw_api_responses` - сырые ответы API

