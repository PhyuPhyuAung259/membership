#!/bin/sh
set -e

cd /var/www/html

# Make sure runtime dirs exist and are writable (ephemeral filesystem on Render)
mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/livewire-tmp
chown -R www-data:www-data storage bootstrap/cache

# nginx.conf runs workers as www-data, but Alpine's nginx package creates its
# own temp dirs (where it buffers an upload's body before handing it to
# PHP-FPM) owned by a different user. Without this, every file upload fails
# with "open() ... failed (13: Permission denied)" at the nginx layer, before
# the request ever reaches Laravel — invisible to application logs.
mkdir -p /var/lib/nginx/tmp/client_body /var/lib/nginx/tmp/fastcgi /var/lib/nginx/tmp/proxy /var/lib/nginx/tmp/uwsgi /var/lib/nginx/tmp/scgi
chown -R www-data:www-data /var/lib/nginx

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
