#!/bin/sh
set -e

cd /var/www/html

# Generate app key if not set
php artisan key:generate --force

# Run migrations
php artisan migrate --force

# Seed if no data exists
php artisan db:seed --force

# Start server
php artisan serve --host=0.0.0.0 --port=${PORT:-8080}
