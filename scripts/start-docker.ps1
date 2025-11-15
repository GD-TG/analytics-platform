# PowerShell script — запускает docker-compose, устанавливает зависимости и выполняет миграции/сиды
# Запускать из корня репозитория: .\scripts\start-docker.ps1

docker-compose up -d --build
Write-Host "Ждём немного пока контейнеры поднимутся..."
Start-Sleep -Seconds 8

# Если .env не существует — скопируем .env.docker внутрь контейнера как .env
docker-compose exec app sh -c "if [ ! -f .env ]; then cp .env.docker .env; fi"

Write-Host "Устанавливаем PHP-зависимости (composer)"
docker-compose exec app composer install --no-interaction --prefer-dist --optimize-autoloader

Write-Host "Генерируем APP_KEY"
docker-compose exec app php artisan key:generate

Write-Host "Выполняем миграции"
docker-compose exec app php artisan migrate --force

Write-Host "Запускаем сидеры (если есть)"
docker-compose exec app php artisan db:seed --force

Write-Host "Создаём символьную ссылку в storage"
docker-compose exec app php artisan storage:link || true

Write-Host "Перезапуск очередей"
docker-compose exec app php artisan queue:restart || true

Write-Host "Frontend: сборка через node-контейнер"
docker-compose run --rm node

Write-Host "Готово. Откройте http://localhost:8080"
