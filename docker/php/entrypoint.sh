#!/bin/sh
# Arranque de los contenedores de Laravel: resuelve la APP_KEY y luego ejecuta el comando recibido.
# Documentado en docs/operacion/docker.md
set -e

KEY_FILE="${APP_KEY_FILE:-/run/trazo/app-key}"

# En producción la llave llega por entorno; en local se genera una vez y se guarda en un volumen
if [ -z "$APP_KEY" ]; then
  if [ ! -s "$KEY_FILE" ]; then
    if [ "$APP_ENV" = "production" ]; then
      echo "Falta APP_KEY: en producción debe definirse en el entorno." >&2
      exit 1
    fi
    umask 077
    echo "base64:$(head -c 32 /dev/urandom | base64)" > "$KEY_FILE"
    # Si la generó root, los demás contenedores (www-data) deben poder leerla
    [ "$(id -u)" = "0" ] && chown www-data:www-data "$KEY_FILE"
    echo "APP_KEY local generada en $KEY_FILE"
  fi
  APP_KEY="$(cat "$KEY_FILE")"
  export APP_KEY
fi

exec "$@"
