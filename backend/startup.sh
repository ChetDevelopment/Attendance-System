#!/bin/sh
set -e
cd /var/www/html || exit 1

# Override .env with Render runtime env vars (injected via dashboard)
sed -i "s/^DB_CONNECTION=.*/DB_CONNECTION=${DB_CONNECTION:-sqlite}/" .env
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-/var/www/html/database/database.sqlite}|" .env
sed -i "s/^APP_KEY=.*/APP_KEY=${APP_KEY:-}/" .env
sed -i "s/^APP_ENV=.*/APP_ENV=${APP_ENV:-production}/" .env
sed -i "s/^APP_DEBUG=.*/APP_DEBUG=${APP_DEBUG:-false}/" .env

php artisan key:generate --force || true

touch database/database.sqlite
chmod 666 database/database.sqlite

php artisan migrate --force || true
php artisan db:seed --force || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
