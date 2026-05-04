FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev nginx \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath pcntl

COPY .github/nginx.conf /etc/nginx/sites-available/default

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
CMD service nginx start && php-fpm