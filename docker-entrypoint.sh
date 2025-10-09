#!/bin/sh
set -e

echo "🚀 Starting FrankenPHP Laravel application..."

# Wait for external database to be ready (if DB_HOST is provided)
if [ -n "${DB_HOST}" ] && [ "${DB_CONNECTION}" = "pgsql" ]; then
    echo "⏳ Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."
    timeout=30
    elapsed=0
    until nc -z "${DB_HOST}" "${DB_PORT}" || [ $elapsed -ge $timeout ]; do
        sleep 1
        elapsed=$((elapsed + 1))
    done
    if [ $elapsed -ge $timeout ]; then
        echo "⚠️  PostgreSQL connection timeout, continuing anyway..."
    else
        echo "✅ PostgreSQL is ready"
    fi
fi

# Set proper permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /app/storage /app/bootstrap/cache
chmod -R 775 /app/storage /app/bootstrap/cache

# Run migrations if AUTO_MIGRATE is enabled
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    echo "🔄 Running migrations..."
    php artisan migrate --force --no-interaction || echo "⚠️  Migration failed, continuing..."

    # Run production setup if SETUP_PRODUCTION is enabled
    if [ "${SETUP_PRODUCTION:-false}" = "true" ]; then
        echo "🔧 Setting up production environment..."
        php artisan app:setup-production || echo "⚠️  Production setup failed, continuing..."
    fi
else
    echo "⏭️  Skipping migrations (AUTO_MIGRATE not enabled)"
fi

# Clear and cache Laravel configuration
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear || echo "⚠️  Cache clear failed, continuing..."

# Only optimize in production
if [ "${APP_ENV}" = "production" ]; then
    echo "⚡ Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "✨ Application ready!"

# Start FrankenPHP
exec frankenphp run --config /etc/caddy/Caddyfile
