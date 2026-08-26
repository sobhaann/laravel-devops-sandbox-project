FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer config -g repos.packagist composer \
    https://package-mirror.liara.ir/repository/composer/


WORKDIR /var/www/html

# Copy ONLY the dependency manifests first. This layer — and the expensive
# composer install after it — is now cached based purely on these two files,
# not your entire repo. Editing docker-compose.yml, my.cnf, etc. no longer
# touches this cache at all.
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

# Now bring in the rest of the app.
COPY . .

RUN composer dump-autoload --optimize \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]