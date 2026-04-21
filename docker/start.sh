#!/bin/sh

chown -R www-data:www-data /var/www

php artisan config:clear
php artisan cache:clear
php artisan migrate --force || true

php-fpm -D

echo "--- NGINX ESTÁ EN LÍNEA, otra vez ---"
nginx -g "daemon off;"