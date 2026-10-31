# Imagen de la API (Laravel). La misma imagen sirve a app, init, worker y scheduler.
# Documentado en docs/operacion/docker.md

FROM mlocati/php-extension-installer:2.12.3 AS ext-installer
FROM composer:2.10.3 AS composer

# Base común: PHP-FPM con las extensiones que necesita Laravel
FROM php:8.4.26-fpm-alpine AS base

COPY --from=ext-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql redis intl zip bcmath pcntl \
    && apk add --no-cache fcgi tini

COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf
RUN rm -f /usr/local/etc/php-fpm.d/zz-docker.conf \
    && mkdir -p /run/trazo && chown www-data:www-data /run/trazo
COPY --chmod=755 docker/php/entrypoint.sh /usr/local/bin/trazo-entrypoint
COPY --chmod=755 docker/php/healthcheck.sh /usr/local/bin/fpm-healthcheck

ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
WORKDIR /var/www/html
ENTRYPOINT ["/sbin/tini", "--", "trazo-entrypoint"]
CMD ["php-fpm"]

# Desarrollo: el código (y el script de arranque, dev-init.sh) llega montado desde el host y se usa composer dentro del contenedor
FROM base AS dev
RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini \
    && apk add --no-cache su-exec
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=1 COMPOSER_HOME=/tmp/composer
USER www-data

# Dependencias de producción, sin herramientas de desarrollo
FROM composer AS vendor
WORKDIR /app
COPY backend/composer.json backend/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

# Producción: código y dependencias horneados en la imagen
FROM base AS prod
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY --chown=www-data:www-data backend/ ./
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && rm /usr/local/bin/composer
USER www-data
