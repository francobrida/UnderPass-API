#!/bin/sh
php artisan config:clear
php artisan cache:clear
php artisan migrate --force || true
php-fpm &
nginx -g "daemon off;"