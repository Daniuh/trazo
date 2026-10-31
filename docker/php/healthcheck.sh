#!/bin/sh
# Healthcheck de PHP-FPM: responde "pong" si el pool está aceptando peticiones.
set -e

SCRIPT_NAME=/fpm-ping SCRIPT_FILENAME=/fpm-ping REQUEST_METHOD=GET \
  cgi-fcgi -bind -connect 127.0.0.1:9000 | grep -q pong
