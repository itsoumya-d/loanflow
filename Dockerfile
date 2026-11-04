# syntax=docker/dockerfile:1.7

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY . .
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

FROM php:8.4-fpm-alpine AS runtime

RUN apk add --no-cache icu-dev oniguruma-dev libzip-dev \
    && docker-php-ext-install -j"$(nproc)" bcmath intl opcache pcntl pdo_mysql \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY docker/php.ini /usr/local/etc/php/conf.d/99-loanflow.ini
COPY docker/entrypoint.sh /usr/local/bin/loanflow-entrypoint

RUN chmod +x /usr/local/bin/loanflow-entrypoint \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data

ENTRYPOINT ["loanflow-entrypoint"]
CMD ["php-fpm"]
