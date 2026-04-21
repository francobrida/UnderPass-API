FROM dunglas/frankenphp:1-php8.4

RUN apt-get update && apt-get install -y \
    git unzip libzip-dev libpng-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath pcntl

ENV SERVER_NAME=:80

ENV APP_RUNTIME=Laravel\Octane\FrankenPHP\Runtime 

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
COPY . .

RUN composer install --no-interaction --optimize-autoloader --no-dev

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80
 
CMD ["frankenphp", "php-server", "--root", "public/"]