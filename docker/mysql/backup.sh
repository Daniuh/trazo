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
CHECK_SECONDS="${BACKUP_CHECK_SECONDS:-300}"
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

# Momento (epoch) del respaldo más nuevo; 0 si no hay ninguno
newest() {
  find "$DIR" -maxdepth 1 -name '*.sql.gz.enc' -printf '%T@\n' 2>/dev/null | sort -n | tail -1 | cut -d. -f1 | grep . || echo 0
}

# Toca respaldar si ya pasó la hora del día y el último es anterior, o si no hay ninguno de las últimas 24 h
due() {
  local today_at
  today_at=$(date -d "today $AT" +%s)
  if [ "$(date +%s)" -ge "$today_at" ] && [ "$(newest)" -lt "$today_at" ]; then return 0; fi
  ! fresh 24
}

# Revisa cada 5 minutos en vez de dormir hasta la hora: si MySQL aún no acepta conexiones al arrancar, o el equipo
# estuvo suspendido a la hora del respaldo, se reintenta solo en la siguiente vuelta
schedule() {
  trap 'exit 0' TERM INT
  log "respaldo diario a las $AT; revisión cada $CHECK_SECONDS s"
  while true; do
    # Cada respaldo en su propio proceso: una falla no detiene el ciclo y el healthcheck la delata
    if due; then ( run ) || log "falló el respaldo; se reintenta en la próxima revisión"; fi
    sleep "$CHECK_SECONDS" &
    wait $!
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
