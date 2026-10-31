#!/bin/bash
# Respaldo diario de MySQL: volcado consistente (sin bloquear la app), comprimido, cifrado con AES-256 y firmado con
# HMAC. Corre en el servicio "backup" con la imagen de MySQL. Subcomandos: schedule (por defecto), run, list, check y
# restore. Documentado en docs/operacion/respaldos.md
set -euo pipefail

DIR=/backups
KEY_FILE=/run/backup-key/key
AT="${BACKUP_AT:-03:00}"
DAYS="${BACKUP_RETENTION_DAYS:-7}"
MAX_AGE_HOURS="${BACKUP_MAX_AGE_HOURS:-26}"
export TZ="${BACKUP_TIMEZONE:-America/Bogota}"

log() { echo "[respaldo] $(date '+%F %T %Z') $*"; }
fail() { echo "[respaldo] $*" >&2; exit 1; }

# Hay un respaldo más nuevo que N horas
fresh() {
  find "$DIR" -maxdepth 1 -name '*.sql.gz.enc' -mmin "-$(($1 * 60))" 2>/dev/null | grep -q .
}

# Como root solo se preparan carpetas y llave; el trabajo lo hace el usuario mysql
prepare() {
  mkdir -p "$DIR" "$(dirname "$KEY_FILE")"
  if [ -z "${BACKUP_ENCRYPTION_KEY:-}" ] && [ ! -s "$KEY_FILE" ]; then
    [ "${APP_ENV:-local}" = "production" ] && fail "Falta BACKUP_ENCRYPTION_KEY: en producción debe definirse en el entorno."
    (umask 077 && openssl rand -base64 32 > "$KEY_FILE")
    log "llave local generada en $KEY_FILE"
  fi
  chown -R mysql:mysql "$DIR" "$(dirname "$KEY_FILE")"
  chmod 700 "$DIR" "$(dirname "$KEY_FILE")"
}

# Archivos temporales privados con la llave y las credenciales: nada sensible en los argumentos de un proceso
secrets() {
  umask 077
  KEY_TMP="$(mktemp)"
  CNF_TMP="$(mktemp)"
  trap 'rm -f "$KEY_TMP" "$CNF_TMP" "${PART:-}"' EXIT
  if [ -n "${BACKUP_ENCRYPTION_KEY:-}" ]; then printf '%s' "$BACKUP_ENCRYPTION_KEY" > "$KEY_TMP"; else cp "$KEY_FILE" "$KEY_TMP"; fi
  printf '[client]\nhost=%s\nuser=%s\npassword="%s"\n' "${DB_HOST:-mysql}" "$DB_USERNAME" "$DB_PASSWORD" > "$CNF_TMP"
}

hmac() {
  openssl dgst -sha256 -hmac "$(cat "$KEY_TMP")" -r "$1" | cut -d' ' -f1
}

run() {
  secrets
  local name
  name="$DB_DATABASE-$(date -u +%Y%m%dT%H%M%SZ).sql.gz.enc"
  PART="$DIR/.$name.part"

  # Se revisa a mano: dentro de un "||" bash ignora set -e y seguiría con un volcado a medias
  if ! mysqldump --defaults-extra-file="$CNF_TMP" --single-transaction --quick --no-tablespaces \
    --set-gtid-purged=OFF --hex-blob "$DB_DATABASE" \
    | gzip -6 \
    | openssl enc -aes-256-cbc -pbkdf2 -iter 600000 -salt -pass "file:$KEY_TMP" -out "$PART"; then
    fail "el volcado falló; no se guardó nada"
  fi

  # La firma va primero: un .enc sin su .hmac nunca se da por bueno
  hmac "$PART" > "$DIR/$name.hmac"
  mv "$PART" "$DIR/$name"
  PART=""
  log "listo: $name ($(du -h "$DIR/$name" | cut -f1))"

  find "$DIR" -maxdepth 1 \( -name '*.sql.gz.enc' -o -name '*.hmac' -o -name '.*.part' \) -mmin "+$((DAYS * 1440))" -delete
}

# Espera hasta la hora configurada cada día. Si al arrancar no hay respaldo de las últimas 24 h, hace uno ya
schedule() {
  trap 'exit 0' TERM INT
  fresh 24 || ( run ) || log "falló el respaldo inicial"
  while true; do
    local now next
    now=$(date +%s)
    next=$(date -d "today $AT" +%s)
    [ "$next" -gt "$now" ] || next=$(date -d "tomorrow $AT" +%s)
    log "próximo respaldo: $(date -d "@$next" '+%F %T %Z')"
    sleep $((next - now)) &
    wait $!
    # Cada respaldo en su propio proceso: una falla no detiene el ciclo y el healthcheck la delata
    ( run ) || log "falló el respaldo"
  done
}

restore() {
  local file="${1:-}" db="${2:-}"
  [ -n "$file" ] && [ -n "$db" ] || fail "Uso: trazo-backup restore <archivo> <base>"
  [ -f "$DIR/$file" ] || fail "No existe $DIR/$file (usa \"trazo-backup list\")."
  secrets
  [ "$(hmac "$DIR/$file")" = "$(cat "$DIR/$file.hmac" 2>/dev/null)" ] \
    || fail "La firma de $file no coincide: el archivo está dañado, fue modificado o se cifró con otra llave."

  log "restaurando $file en la base $db"
  openssl enc -d -aes-256-cbc -pbkdf2 -iter 600000 -pass "file:$KEY_TMP" -in "$DIR/$file" \
    | gunzip \
    | mysql --defaults-extra-file="$CNF_TMP" "$db"
  log "restauración terminada"
}

command="${1:-schedule}"
shift || true

# El healthcheck no necesita preparar nada
if [ "$command" = "check" ]; then
  fresh "$MAX_AGE_HOURS" || fail "El último respaldo tiene más de $MAX_AGE_HOURS horas."
  exit 0
fi

if [ "$(id -u)" = "0" ]; then
  prepare
  exec gosu mysql bash "$0" "$command" "$@"
fi

case "$command" in
  schedule) schedule ;;
  run) run ;;
  list) ls -lh "$DIR" ;;
  restore) restore "$@" ;;
  *) fail "Subcomando desconocido: $command (schedule, run, list, check, restore)" ;;
esac
