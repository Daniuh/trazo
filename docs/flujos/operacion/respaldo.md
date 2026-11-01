---
title: Respaldo diario y restauración
description: Cómo se hace, cifra, firma y guarda el respaldo diario de MySQL, cómo se recupera solo si falla y cómo se restaura con verificación de la firma.
audience: tecnico
area: flujos
module: operacion
order: 2
---

# Respaldo diario y restauración

El servicio `backup` del compose respalda la base todos los días a las 3:00 (hora de Bogotá) y guarda 7 días en el volumen `trazo_backups`. Cada respaldo se comprime, se cifra con AES-256 y se firma con HMAC-SHA256: un archivo alterado, dañado o cifrado con otra llave se rechaza antes de tocar la base.

```flujo
carriles: [Servicio backup, MySQL, Volumen de respaldos, Operación]
pasos:
  - id: vuelta
    carril: Servicio backup
    tipo: inicio
    texto: Revisa cada 5 minutos si toca respaldar
    detalle: 'No duerme hasta la hora: si MySQL todavía no aceptaba conexiones o el equipo estuvo suspendido a las 3:00, se reintenta solo en la siguiente vuelta (`BACKUP_CHECK_SECONDS`).'
    va: toca
  - id: toca
    carril: Servicio backup
    tipo: decision
    texto: ¿Pasaron las 3:00 sin respaldo, o no hay uno de 24 h?
    si: secretos
    no: vuelta
  - id: secretos
    carril: Servicio backup
    texto: Prepara llave y credenciales en archivos temporales
    detalle: 'Nunca en los argumentos de un proceso (se verían con `ps`). La llave es `BACKUP_ENCRYPTION_KEY` (obligatoria en producción) o una generada en el volumen `trazo_backup-key` en local.'
    va: volcado
  - id: volcado
    carril: MySQL
    texto: Vuelca sin bloquear la app
    detalle: '`mysqldump --single-transaction --quick --no-tablespaces`: una foto consistente mientras la app sigue escribiendo.'
    va: ok
  - id: ok
    carril: Servicio backup
    tipo: decision
    texto: ¿El volcado terminó bien?
    si: cifra
    no: falla
  - id: falla
    carril: Servicio backup
    tipo: error
    texto: No guarda nada y reintenta en 5 min
    detalle: 'El healthcheck del servicio se pone en rojo si no hay un respaldo de las últimas 26 h.'
  - id: cifra
    carril: Volumen de respaldos
    texto: Guarda .sql.gz.enc y su firma .hmac
    detalle: 'gzip → `openssl enc -aes-256-cbc -pbkdf2 -iter 600000`. La firma se escribe antes de dar el archivo por bueno: un `.enc` sin su `.hmac` nunca cuenta. Borra los de más de 7 días.'
    va: vuelta2
  - id: vuelta2
    carril: Servicio backup
    tipo: fin
    texto: Sigue revisando cada 5 minutos
  - id: restaura
    carril: Operación
    tipo: inicio
    texto: Ejecuta trazo-backup restore archivo base
    detalle: '`list` muestra los respaldos. Se recomienda restaurar primero en una base aparte (por ejemplo `trazo_test`) y comparar.'
    va: firma
  - id: firma
    carril: Servicio backup
    tipo: decision
    texto: ¿La firma coincide?
    si: carga
    no: rechaza
  - id: rechaza
    carril: Servicio backup
    tipo: error
    texto: Se detiene sin tocar la base
  - id: carga
    carril: MySQL
    tipo: fin
    texto: Descifra, descomprime y carga
```

Probado: una restauración en `trazo_test` dio conteos idénticos a la base original, y un archivo alterado se rechazó.

## Dónde está en el código

- [`backup.sh`](../../../docker/mysql/backup.sh): `schedule`, `run`, `list`, `check` y `restore`.
- [`docker-compose.yml`](../../../docker-compose.yml): el servicio `backup`, su volumen y su healthcheck.
- Explicación y comandos: [Respaldos](../../operacion/respaldos.md).
