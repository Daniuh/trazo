#!/bin/sh
# Preparación del entorno local: permisos del código montado, dependencias, migraciones y seeders.
# Corre como root solo para ajustar permisos; todo lo demás se ejecuta como www-data.
# Documentado en docs/operacion/docker.md
set -e

cd /var/www/html

# En Windows y macOS el código montado llega sin permiso de escritura para www-data
mkdir -p vendor storage/logs storage/framework/cache storage/framework/views bootstrap/cache
chmod -R a+rwX vendor storage bootstrap/cache

# La configuración llega por variables del contenedor; el .env vacío evita que Laravel lo busque en vano
[ -f .env ] || su-exec www-data sh -c 'echo "# Local: la configuración viene de docker-compose." > .env'

su-exec www-data composer install --no-interaction --prefer-dist
su-exec www-data php artisan migrate --force --seed
