#!/bin/sh
set -e
cd /var/www/html || exit 1

# Append runtime env overrides to .env
{
  echo ""
  echo "# --- Runtime overrides ---"
  echo "APP_ENV=${APP_ENV:-production}"
  echo "APP_DEBUG=${APP_DEBUG:-false}"
  echo "APP_URL=${APP_URL:-http://localhost}"
  echo "FRONTEND_URL=${FRONTEND_URL:-http://localhost:3000}"
  echo "DB_CONNECTION=${DB_CONNECTION:-sqlite}"
  echo "DB_DATABASE=${DB_DATABASE:-/var/www/html/database/database.sqlite}"
  echo "CACHE_DRIVER=${CACHE_DRIVER:-file}"
  echo "SESSION_DRIVER=${SESSION_DRIVER:-file}"
  echo "QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}"
} >> .env

php artisan key:generate --force || true

touch database/database.sqlite
chmod 666 database/database.sqlite

php artisan migrate --force || true
php artisan db:seed --force || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
