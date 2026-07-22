#!/bin/sh
set -e
cd /var/www/html || exit 1

php artisan key:generate --force || true
php artisan migrate --force || true
php artisan db:seed --force || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
