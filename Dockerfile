FROM php:8.4-fpm

# Dependencias del sistema
RUN apt-get update && apt-get install -y \
    git curl zip unzip nginx libpng-dev libonig-dev libxml2-dev

# Extensiones PHP necesarias para Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configuración de trabajo
WORKDIR /var/www

# Copiar proyecto
COPY . .

# Instalar dependencias Laravel
RUN composer install --no-dev --optimize-autoloader

# Permisos necesarios
RUN chown -R www-data:www-data storage bootstrap/cache

# Copiar config de Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Script de arranque
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Puerto Railway
EXPOSE 8080

CMD ["/start.sh"]
