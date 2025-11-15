<?php

// Генерация APP_KEY для Laravel
$key = 'base64:' . base64_encode(random_bytes(32));

$envFile = __DIR__ . '/.env';
$envExampleFile = __DIR__ . '/.env.example';

// Если .env не существует, создаем его из .env.example или создаем новый
if (!file_exists($envFile)) {
    if (file_exists($envExampleFile)) {
        copy($envExampleFile, $envFile);
    } else {
        // Создаем базовый .env файл
        $basicEnv = <<<ENV
APP_NAME="Laravel Analytics"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=analytics_platform
DB_USERNAME=root
DB_PASSWORD=

YANDEX_CLIENT_ID=
YANDEX_CLIENT_SECRET=
YANDEX_OAUTH_TOKEN=

ENV;
        file_put_contents($envFile, $basicEnv);
    }
}

// Читаем .env файл
$envContent = file_get_contents($envFile);

// Проверяем, есть ли уже APP_KEY
if (preg_match('/^APP_KEY=.*$/m', $envContent)) {
    // Заменяем существующий ключ
    $envContent = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY=' . $key, $envContent);
} else {
    // Добавляем новый ключ после APP_NAME или в начало файла
    if (preg_match('/^APP_NAME=.*$/m', $envContent)) {
        $envContent = preg_replace('/^(APP_NAME=.*)$/m', "$1\nAPP_KEY=$key", $envContent);
    } else {
        $envContent = "APP_KEY=$key\n" . $envContent;
    }
}

// Сохраняем .env файл
file_put_contents($envFile, $envContent);

echo "APP_KEY успешно сгенерирован и добавлен в .env файл!\n";
echo "Ключ: $key\n";

