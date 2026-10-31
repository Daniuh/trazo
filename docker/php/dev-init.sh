#!/bin/sh
# Preparación del entorno local: permisos del código montado, dependencias y migraciones.
# Corre como root solo para ajustar permisos; todo lo demás se ejecuta como www-data.
# Documentado en docs/operacion/docker.md
set -e

cd /var/www/html

# En Windows y macOS el código montado llega sin permiso de escritura para www-data
mkdir -p vendor storage/logs storage/framework/cache storage/framework/views bootstrap/cache
chmod -R a+rwX vendor storage bootstrap/cache

su-exec www-data composer install --no-interaction --prefer-dist
su-exec www-data php artisan migrate --force
