#!/bin/sh
# Despliegue en el servidor: construye y levanta Trazo en modo producción y deja al proxy de TLS conectado a la red
# trazo-proxy. Solo toca contenedores, redes y volúmenes de Trazo; al proxy le agrega una red sin reiniciarlo.
# Uso (desde la carpeta del proyecto, con el código ya actualizado): sh scripts/deploy/deploy.sh
# Documentado en docs/operacion/despliegue.md
set -eu

cd "$(dirname "$0")/../.."
[ -f .env ] || { echo "Falta el .env: créalo con scripts/deploy/new-env.sh" >&2; exit 1; }

compose() { docker compose -p trazo -f docker-compose.yml -f docker-compose.prod.yml "$@"; }

compose up -d --build --wait

# El proxy pierde esta red si alguien vuelve a crear su contenedor: conectarlo de nuevo es seguro y no lo reinicia
PROXY="$(sed -n 's/^TRAZO_PROXY_CONTAINER=//p' .env)"
if [ -n "$PROXY" ]; then
  if docker inspect "$PROXY" --format '{{json .NetworkSettings.Networks}}' | grep -q '"trazo-proxy"'; then
    echo "El proxy $PROXY ya está en la red trazo-proxy."
  else
    docker network connect trazo-proxy "$PROXY"
    echo "Proxy $PROXY conectado a la red trazo-proxy."
  fi
else
  echo "Aviso: TRAZO_PROXY_CONTAINER está vacío; el proxy no se conectó." >&2
fi

compose ps
