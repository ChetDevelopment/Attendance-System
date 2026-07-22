#!/bin/sh
set -e
cd /var/www/html || exit 1

# Write runtime .env (Render env vars aren't injected into Docker containers)
cat > .env << EOF
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-true}
APP_KEY=${APP_KEY:-}
APP_URL=${APP_URL:-http://localhost}
FRONTEND_URL=${FRONTEND_URL:-http://localhost:3000}
DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST:-dpg-d9g27obrjlhs739040vg-a}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-attendance_db_5w4u}
DB_USERNAME=${DB_USERNAME:-attendance_db_5w4u_user}
DB_PASSWORD=${DB_PASSWORD:-7wmpWXcCT7yFczXAtiyEgfoakSiglzUs}
CACHE_DRIVER=${CACHE_DRIVER:-file}
SESSION_DRIVER=${SESSION_DRIVER:-file}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}
LOG_CHANNEL=stack
EOF

php artisan key:generate --force || true
php artisan migrate --force || true
php artisan db:seed --force || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
