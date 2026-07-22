#!/bin/sh
set -e
cd /var/www/html || exit 1

php artisan key:generate --force || true

if [ "$DB_CONNECTION" = "sqlite" ]; then
  touch database/database.sqlite
  chmod 666 database/database.sqlite
  sqlite3 database/database.sqlite "ALTER TABLE users ADD COLUMN calendar_id TEXT;" 2>/dev/null || true
fi

php artisan migrate --force || true
php artisan db:seed --force || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
