#!/bin/sh

php artisan config:clear
php artisan cache:clear
php artisan migrate --force || true


chown -R www-data:www-data /var/www

php-fpm -D


echo "--- LANZANDO NGINX ---"
nginx -g "daemon off;"