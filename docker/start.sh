#!/bin/sh

# 1. Preparar la app
php artisan config:clear
php artisan cache:clear
php artisan migrate --force || true

# 2. Asegurar permisos totales sobre la carpeta de trabajo
chown -R www-data:www-data /var/www

# 3. Arrancar PHP-FPM en segundo plano
php-fpm -D

# 4. Arrancar Nginx en PRIMER PLANO (esto es lo que mantiene vivo el contenedor)
echo "--- LANZANDO NGINX ---"
nginx -g "daemon off;"