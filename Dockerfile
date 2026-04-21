FROM php:8.4-apache

# 1. Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev

# 2. Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 3. Habilitar mod_rewrite
RUN a2enmod rewrite

# 4. Configurar DocumentRoot
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 5. Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Código
WORKDIR /var/www/html
COPY . .

# 7. Instalación de dependencias (ahora sí pasará el check de versión)
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 8. Permisos críticos para Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Puerto y arranque
EXPOSE 80
CMD ["apache2-foreground"]