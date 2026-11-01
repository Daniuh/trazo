#!/bin/sh
# Crea el .env de producción en el servidor con secretos aleatorios generados ahí mismo (nunca pasan por el
# repositorio ni por la consola). Lo que solo sabe quien despliega (dominio, correo, contraseñas de las cuentas)
# queda vacío para completarlo a mano. No pisa un .env existente.
# Uso: sh scripts/deploy/new-env.sh trazo.daniuh.dev
# Documentado en docs/operacion/despliegue.md
set -eu

DOMAIN="${1:?Indica el dominio, por ejemplo: sh scripts/deploy/new-env.sh trazo.daniuh.dev}"
cd "$(dirname "$0")/../.."

if [ -e .env ]; then
  echo "Ya existe un .env: no se toca. Bórralo a mano si de verdad quieres empezar de cero." >&2
  exit 1
fi

secret() { openssl rand -hex "${1:-24}"; }

umask 077
cat > .env <<EOF
# Producción. Generado por scripts/deploy/new-env.sh; los secretos son aleatorios y solo viven aquí.
# Guarda APP_KEY y BACKUP_ENCRYPTION_KEY también fuera del servidor: sin ellas no se abren los datos cifrados.
APP_URL=https://${DOMAIN}
APP_KEY=base64:$(openssl rand -base64 32)
REVERB_ALLOWED_ORIGINS=${DOMAIN}
REVERB_APP_KEY=$(secret 16)
REVERB_APP_SECRET=$(secret)
DB_PASSWORD=$(secret)
DB_ROOT_PASSWORD=$(secret)
REDIS_PASSWORD=$(secret)
BACKUP_ENCRYPTION_KEY=$(secret 32)

# Correo (Gmail con contraseña de aplicación)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_FROM_ADDRESS=
MAIL_USERNAME=
MAIL_PASSWORD=

# Cuentas por rol (docs/operacion/cuentas.md). El correo lleva {rol}
EVALUATION_EMAIL_PATTERN=
OWNER_EMAIL_PATTERN=
EVALUATION_PASSWORD=
OWNER_PASSWORD=

# Contenedor del proxy de TLS del servidor, que se conecta a la red trazo-proxy al desplegar
TRAZO_PROXY_CONTAINER=
EOF

echo ".env creado con permisos 600. Completa MAIL_*, las cuentas y TRAZO_PROXY_CONTAINER antes de desplegar."
