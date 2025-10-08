#!/usr/bin/env bash
# exit on error
set -o errexit

echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Installing NPM dependencies..."
npm ci

echo "Building frontend assets..."
npm run build

echo "Clearing and caching config..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Running database migrations..."
php artisan migrate --force

echo "Seeding database (roles and permissions)..."
php artisan db:seed --class=RoleSeeder --force

echo "Generating Shield permissions..."
php artisan shield:generate --all

echo "Build completed successfully!"
