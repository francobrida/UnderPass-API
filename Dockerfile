FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip


RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd


COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www
COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN chown -R www-data:www-data /var/www/storage /var/www/cache

EXPOSE 9000
CMD ["php-fpm"]