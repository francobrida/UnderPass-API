FROM php:8.3-apache

# 1. Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip libzip-dev

# 2. Instalar extensiones de PHP necesarias para Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 3. Habilitar el módulo rewrite de Apache (Vital para las rutas de Laravel)
RUN a2enmod rewrite

# 4. Configurar el DocumentRoot de Apache a la carpeta /public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 5. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Copiar el código
WORKDIR /var/www/html
COPY . .

# 7. Permisos y optimización
RUN composer install --no-interaction --optimize-autoloader --no-dev
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Exponer el puerto que Railway espera (Apache usa el 80 por defecto, Railway lo mapea solo)
EXPOSE 80

# 9. Comando de inicio (Apache ya corre en primer plano por defecto en esta imagen)
CMD ["apache2-foreground"]