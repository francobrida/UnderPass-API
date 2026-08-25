FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev nginx \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath pcntl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

ENV APP_ENV=production

WORKDIR /app
COPY . .

COPY docker/nginx.conf /etc/nginx/sites-available/default

RUN composer install --no-dev --optimize-autoloader
RUN chown -R www-data:www-data /app && chmod -R 775 /app/storage bootstrap/cache


CMD sh -c "sed -i 's/\${PORT}/'$PORT'/g' /etc/nginx/sites-available/default; \
    php artisan optimize:clear; \
    php artisan migrate --force; \
    php artisan passport:keys --force; \
    chown -R www-data:www-data /app/storage; \
    chmod -R 600 /app/storage/*.key; \
    php-fpm -D; \
    (while true; do php artisan schedule:run --no-interaction > /dev/null 2>&1; sleep 60; done) & \
    nginx -g 'daemon off;'"