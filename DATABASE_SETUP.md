# Настройка базы данных MySQL

## Ошибка подключения

Если вы видите ошибку:
```
SQLSTATE[HY000] [2002] Подключение не установлено, т.к. конечный компьютер отверг запрос на подключение
```

Это означает, что приложение не может подключиться к MySQL серверу.

## Решение проблемы

### Шаг 1: Проверьте, запущен ли MySQL

#### Windows (через службы):
1. Нажмите `Win + R`
2. Введите `services.msc` и нажмите Enter
3. Найдите службу **MySQL** или **MySQL80**
4. Убедитесь, что она **Запущена**
5. Если не запущена - нажмите правой кнопкой → **Запустить**

#### Через командную строку:
```cmd
net start MySQL80
```
или
```cmd
net start MySQL
```

### Шаг 2: Проверьте настройки в .env файле

Откройте файл `.env` в корне проекта и проверьте настройки:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=analytics_platform
DB_USERNAME=root
DB_PASSWORD=ваш_пароль
```

**Важно:**
- `DB_HOST` должен быть `127.0.0.1` или `localhost` для локального сервера
- `DB_PORT` обычно `3306` (стандартный порт MySQL)
- `DB_DATABASE` - имя базы данных (её нужно создать)
- `DB_USERNAME` - обычно `root` для локальной разработки
- `DB_PASSWORD` - пароль MySQL (может быть пустым, если не установлен)

### Шаг 3: Создайте базу данных

#### Вариант 1: Через командную строку MySQL

1. Откройте командную строку
2. Подключитесь к MySQL:
```cmd
mysql -u root -p
```
(введите пароль, если он установлен)

3. Создайте базу данных:
```sql
CREATE DATABASE analytics_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. Проверьте, что база создана:
```sql
SHOW DATABASES;
```

5. Выйдите:
```sql
EXIT;
```

#### Вариант 2: Через phpMyAdmin

1. Откройте phpMyAdmin (обычно `http://localhost/phpmyadmin`)
2. Войдите с учетными данными MySQL
3. Создайте новую базу данных:
   - Нажмите "Создать базу данных"
   - Имя: `analytics_platform`
   - Сравнение: `utf8mb4_unicode_ci`
   - Нажмите "Создать"

### Шаг 4: Выполните миграции

После создания базы данных выполните миграции:

```cmd
cd C:\PlanicaTask\analytics-platform
php artisan migrate
```

Это создаст все необходимые таблицы в базе данных.

### Шаг 5: Проверьте подключение

Проверьте, что подключение работает:

```cmd
php artisan tinker
```

Затем в tinker:
```php
DB::connection()->getPdo();
```

Если команда выполнилась без ошибок - подключение работает!

## Альтернатива: Использование SQLite (для тестирования)

Если MySQL не установлен, можно временно использовать SQLite:

1. В `.env` измените:
```env
DB_CONNECTION=sqlite
DB_DATABASE=C:\PlanicaTask\analytics-platform\database\database.sqlite
```

2. Создайте файл базы данных:
```cmd
cd C:\PlanicaTask\analytics-platform
type nul > database\database.sqlite
```

3. Выполните миграции:
```cmd
php artisan migrate
```

## Установка MySQL (если не установлен)

### Windows:

1. Скачайте MySQL Installer: https://dev.mysql.com/downloads/installer/
2. Выберите "MySQL Server" и "MySQL Workbench"
3. Установите с настройками по умолчанию
4. Запомните пароль root, который вы установите
5. Добавьте пароль в `.env` файл

### Через XAMPP/WAMP:

Если используете XAMPP или WAMP:
- MySQL уже включен
- Обычно пароль root пустой
- Порт: 3306
- Хост: 127.0.0.1

## Проверка после настройки

После настройки попробуйте снова:

```cmd
php artisan analytics:sync-daily
```

Если всё настроено правильно, команда должна выполниться без ошибок подключения.

