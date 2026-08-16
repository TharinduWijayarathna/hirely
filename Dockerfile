# syntax=docker/dockerfile:1

FROM php:8.4-cli-bookworm AS base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git unzip curl ca-certificates libzip-dev libpng-dev libonig-dev libicu-dev \
    && docker-php-ext-install pdo_mysql zip bcmath pcntl intl opcache \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS app

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
RUN cp .env.example .env \
    && php artisan key:generate --force \
    && composer dump-autoload --optimize --no-dev \
    && npm run build \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

EXPOSE 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
