# syntax=docker/dockerfile:1

# Use the official FrankenPHP image with PHP 8.3
FROM dunglas/frankenphp:latest-php8.3

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    netcat-traditional \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    pdo_sqlite \
    sqlite3 \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip \
    opcache \
    redis

# Set working directory
WORKDIR /app

# Copy composer files first for better caching
COPY composer.json composer.lock /app/

# Install Composer dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy application code
COPY . /app

# Run composer scripts
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache
RUN chmod -R 775 /app/storage /app/bootstrap/cache

# Copy Caddyfile
COPY Caddyfile /etc/caddy/Caddyfile

# Copy entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Expose port (Render will assign PORT env variable)
EXPOSE ${PORT:-80}

# Health check (use PORT env variable or default to 80)
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:${PORT:-80}/up || exit 1

# Run entrypoint script and start FrankenPHP
CMD ["/usr/local/bin/docker-entrypoint.sh"]
