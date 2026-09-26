#!/bin/bash
set -euo pipefail

DB_HOST="${DB_HOST:-db}"
DB_PORT="${DB_PORT:-3306}"
DB_USERNAME="${DB_USERNAME:-lokanata}"
DB_PASSWORD="${DB_PASSWORD:-}"
DB_WAIT_RETRIES="${DB_WAIT_RETRIES:-30}"
DB_WAIT_INTERVAL="${DB_WAIT_INTERVAL:-2}"
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY must be provided." >&2
    exit 1
fi

if ! [[ "$DB_WAIT_RETRIES" =~ ^[1-9][0-9]*$ ]]; then
    echo "DB_WAIT_RETRIES must be a positive integer." >&2
    exit 1
fi

if [ "$RUN_MIGRATIONS" != "true" ] && [ "$RUN_MIGRATIONS" != "false" ]; then
    echo "RUN_MIGRATIONS must be true or false." >&2
    exit 1
fi

echo "Waiting for database..."
attempt=1
while [ "$attempt" -le "$DB_WAIT_RETRIES" ]; do
    if MYSQL_PWD="$DB_PASSWORD" mysqladmin ping -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" --connect-timeout=2 --silent; then
        echo "Database is ready!"
        break
    fi

    if [ "$attempt" -eq "$DB_WAIT_RETRIES" ]; then
        echo "Database did not become ready after ${DB_WAIT_RETRIES} attempts." >&2
        exit 1
    fi

    attempt=$((attempt + 1))
    sleep "$DB_WAIT_INTERVAL"
done

if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

php artisan storage:link --force

echo "Backend is ready!"
exec "$@"
