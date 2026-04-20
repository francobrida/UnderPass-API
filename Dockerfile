FROM php:8.4-apache

# Instalaciones de sistema
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# LIMPIEZA DE MÓDULOS MPM (Evita el error AH00534)
# Desactivamos físicamente los archivos que cargan otros MPM
RUN rm -f /etc/apache2/mods-enabled/mpm_event.load /etc/apache2/mods-enabled/mpm_event.conf \
    && rm -f /etc/apache2/mods-enabled/mpm_worker.load /etc/apache2/mods-enabled/mpm_worker.conf
# Forzamos la carga de prefork y rewrite
RUN a2enmod mpm_prefork rewrite

# Configuración de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Archivos y permisos
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Comando de inicio limpio
CMD ["sh", "-c", "php artisan migrate --force && apache2-foreground"]