FROM dunglas/frankenphp:1-php8.4

# 1. Instalar dependencias necesarias
RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath pcntl

# 2. Habilitar variables de entorno de Laravel
ENV PHP_INI_SCAN_DIR=$PHP_INI_SCAN_DIR:/usr/local/etc/php/conf.d
ENV SERVER_NAME=:80
ENV APP_RUNTIME=Laravel\Octane\FrankenPHP\Runtime

# 3. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Preparar la app
WORKDIR /app
COPY . .

# 5. Instalar dependencias de PHP
RUN composer install --no-interaction --optimize-autoloader --no-dev

# 6. Permisos para Laravel
RUN chown -R www-data:www-data storage bootstrap/cache

# 7. Exponer el puerto
EXPOSE 80

# 8. Arrancar FrankenPHP
CMD ["frankenphp", "php-server", "--public-url", "http://localhost:80", "--root", "public/"]
