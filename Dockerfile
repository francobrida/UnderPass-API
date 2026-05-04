FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev nginx \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app
COPY . .

COPY docker/nginx.conf /etc/nginx/sites-available/default

RUN composer install --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /app && chmod -R 755 /app/storage

CMD sh -c "sed -i 's/\${PORT}/'$PORT'/g' /etc/nginx/sites-available/default && \
    php-fpm -D && \
    php artisan optimize:clear && \
    php artisan migrate:fresh --force --no-interaction && \
    php artisan passport:install --force --no-interaction && \
    nginx -g 'daemon off;'"