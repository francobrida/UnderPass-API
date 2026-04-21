#!/bin/sh

# Limpiar caché para evitar rutas viejas
php artisan config:clear
php artisan cache:clear

# Migraciones (siempre con force en producción)
php artisan migrate --force || true

# Permisos de último minuto
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Arrancar PHP-FPM en segundo plano
php-fpm &

# Arrancar Nginx en primer plano (esto mantiene vivo el contenedor)
nginx -g "daemon off;"