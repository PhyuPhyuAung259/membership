#!/bin/sh
set -e

cd /var/www/html

# Make sure runtime dirs exist and are writable (ephemeral filesystem on Render)
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
chown -R www-data:www-data storage bootstrap/cache

echo "--> Caching configuration"
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "--> Running migrations"
php artisan migrate --force || echo "!! Migrations failed - check your DB env vars"

# Link public storage (harmless if it already exists)
php artisan storage:link || true

echo "--> Starting php-fpm and nginx"
php-fpm -D
exec nginx -g 'daemon off;'
