FROM php:8.4-cli

# Dependencias
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpng-dev libonig-dev libxml2-dev

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# App
WORKDIR /app
COPY . .

RUN composer install --no-dev --optimize-autoloader

# Laravel necesita esto
RUN php artisan config:cache || true
RUN php artisan route:cache || true

# Puerto Railway
ENV PORT=8080
EXPOSE 8080

# Arranque
CMD php -S 0.0.0.0:$PORT -t public
