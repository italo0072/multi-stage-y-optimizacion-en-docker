# syntax=docker/dockerfile:1
FROM composer:2 AS composer-bin

FROM php:8.2-fpm-alpine AS php-runtime
WORKDIR /var/www/html
RUN docker-php-ext-install -j"$(nproc)" pdo_mysql \
    && (php -r 'exit(extension_loaded("Zend OPcache") ? 0 : 1);' || docker-php-ext-install opcache) \
    && php -r 'foreach (["pdo_mysql","mbstring","openssl","tokenizer","xml","ctype","json","dom","Zend OPcache"] as $ext) { if (!extension_loaded($ext)) { fwrite(STDERR, "Falta $ext\n"); exit(1); } }' \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf "expose_php=Off\nopcache.enable=1\nopcache.validate_timestamps=0\n" > "$PHP_INI_DIR/conf.d/production.ini"

FROM php-runtime AS build
COPY --from=composer-bin /usr/bin/composer /usr/local/bin/composer
RUN apk add --no-cache unzip
ENV COMPOSER_ALLOW_SUPERUSER=1
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-autoloader
COPY app ./app
COPY database ./database
RUN composer dump-autoload --no-dev --optimize --no-interaction --no-scripts \
    && composer check-platform-reqs --no-dev

FROM php-runtime AS runtime
COPY --from=build /var/www/html/vendor ./vendor
COPY artisan composer.json ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY routes ./routes
COPY storage ./storage
COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/laravel-entrypoint
RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage
USER www-data
# Artisan genera Swagger en la imagen final durante la construcción.
RUN php artisan package:discover --no-interaction \
    && L5_SWAGGER_CONST_HOST=/api php artisan l5-swagger:generate --no-interaction \
    && test -s storage/api-docs/api-docs.json
EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/laravel-entrypoint"]
CMD ["php-fpm", "-F"]
