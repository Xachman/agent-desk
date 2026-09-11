#!/bin/bash
set -e

APP_DIR=/var/www/html

cd "$APP_DIR"

if [ ! -f .env ]; then
    cp .env.example .env
fi

if [ -z "$APP_KEY" ] && ! grep -q "^APP_KEY=" .env | grep -v "^APP_KEY=$"; then
    php artisan key:generate --ansi
fi

php artisan config:clear --ansi || true
php artisan migrate --force --ansi || true

if [ ! -f storage/oauth-private.key ] && [ -d vendor/laravel/passport ]; then
    php artisan passport:keys --force --ansi || true
fi

exec "$@"
