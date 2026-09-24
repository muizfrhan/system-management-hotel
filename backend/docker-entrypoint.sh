#!/bin/bash
set -e

if [ ! -f .env ]; then
    cp .env.example .env 2>/dev/null || true
fi

if [ -n "$APP_KEY" ]; then
    sed -i "s|APP_KEY=.*|APP_KEY=$APP_KEY|" .env
fi

if [ -n "$DB_HOST" ]; then
    sed -i "s|DB_HOST=.*|DB_HOST=$DB_HOST|" .env
    sed -i "s|DB_PORT=.*|DB_PORT=${DB_PORT:-3306}|" .env
    sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-lokanata_hotel}|" .env
    sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-lokanata}|" .env
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-lokanata_secret}|" .env
fi

if [ -n "$APP_URL" ]; then
    sed -i "s|APP_URL=.*|APP_URL=$APP_URL|" .env
fi

if [ -n "$FRONTEND_URL" ]; then
    sed -i "s|FRONTEND_URL=.*|FRONTEND_URL=$FRONTEND_URL|" .env
fi

if [ -n "$SANCTUM_STATEFUL_DOMAINS" ]; then
    sed -i "s|SANCTUM_STATEFUL_DOMAINS=.*|SANCTUM_STATEFUL_DOMAINS=$SANCTUM_STATEFUL_DOMAINS|" .env
fi

echo "Waiting for database..."
until mysqladmin ping -h "$DB_HOST" -u "$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do
    sleep 2
done
echo "Database is ready!"

php artisan key:generate --force --no-interaction 2>/dev/null || true

php artisan migrate --force --no-interaction

php artisan storage:link 2>/dev/null || true

php artisan db:seed --force --no-interaction 2>/dev/null || true

echo "Backend is ready!"

exec "$@"
