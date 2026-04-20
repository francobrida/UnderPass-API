#!/bin/sh

# Laravel cache 
php artisan config:cache || true
php artisan route:cache || true

# Migraciones 
php artisan migrate --force || true

# Levantar PHP-FPM en background
php-fpm &

# Levantar Nginx en foreground
nginx -g "daemon off;"