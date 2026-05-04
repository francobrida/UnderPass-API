FROM php:8.3-fpm


RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath pcntl


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .


RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data storage bootstrap/cache


CMD php artisan migrate --force; php artisan passport:keys --force; php artisan serve --host=0.0.0.0 --port=${PORT}