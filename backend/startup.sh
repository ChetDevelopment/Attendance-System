#!/bin/sh
set -e
cd /var/www/html || exit 1

cat > .env << EOF
APP_ENV=${APP_ENV:-production}
APP_DEBUG=false
APP_KEY=${APP_KEY:-}
APP_URL=${APP_URL:-http://localhost}
FRONTEND_URL=${FRONTEND_URL:-http://localhost:3000}
DB_CONNECTION=${DB_CONNECTION:-sqlite}
DB_DATABASE=${DB_DATABASE:-/var/www/html/database/database.sqlite}
CACHE_DRIVER=${CACHE_DRIVER:-file}
SESSION_DRIVER=${SESSION_DRIVER:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}
LOG_CHANNEL=stack
EOF

php artisan key:generate --force 2>&1

touch database/database.sqlite 2>/dev/null || true
chmod 666 database/database.sqlite 2>/dev/null || true

php artisan migrate --force 2>&1 || true
php artisan db:seed --force 2>&1 || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
