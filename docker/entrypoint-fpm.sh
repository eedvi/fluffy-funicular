#!/bin/sh
set -e

echo "🚀 Starting Laravel with PHP-FPM + Nginx..."

# Wait for database
if [ -n "${DB_HOST}" ] && [ "${DB_CONNECTION}" = "pgsql" ]; then
    echo "⏳ Waiting for PostgreSQL at ${DB_HOST}:${DB_PORT}..."
    timeout=30
    elapsed=0
    until nc -z "${DB_HOST}" "${DB_PORT}" 2>/dev/null || [ $elapsed -ge $timeout ]; do
        sleep 1
        elapsed=$((elapsed + 1))
    done
    if [ $elapsed -ge $timeout ]; then
        echo "⚠️  PostgreSQL connection timeout, continuing anyway..."
    else
        echo "✅ PostgreSQL is ready"
    fi
fi

# Set permissions
echo "🔐 Setting permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create log directories
mkdir -p /var/log/php-fpm
chown -R www-data:www-data /var/log/php-fpm

# Run migrations if enabled
if [ "${AUTO_MIGRATE:-false}" = "true" ]; then
    echo "🔄 Running migrations..."
    php artisan migrate --force --no-interaction || echo "⚠️  Migration failed, continuing..."

    if [ "${SETUP_PRODUCTION:-false}" = "true" ]; then
        echo "🔧 Setting up production..."
        php artisan app:setup-production || echo "⚠️  Production setup failed, continuing..."
    fi
else
    echo "⏭️  Skipping migrations"
fi

# Clear caches
echo "🧹 Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear || echo "⚠️  Cache clear failed, continuing..."

# Optimize for production
if [ "${APP_ENV}" = "production" ]; then
    echo "⚡ Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

echo "✨ Application ready!"

# Replace PORT in nginx config
if [ -n "${PORT}" ]; then
    echo "🔧 Configuring Nginx to listen on port ${PORT}..."
    sed -i "s/\${PORT:-8080}/${PORT}/g" /etc/nginx/http.d/default.conf
fi

# Start supervisor
echo "🌐 Starting PHP-FPM and Nginx..."
exec /usr/bin/supervisord -c /etc/supervisord.conf
