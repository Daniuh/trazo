---
title: "ADR-0064: Respaldo diario de MySQL"
description: "Respaldo diario de MySQL: servicio propio con la imagen de MySQL, volcado consistente, cifrado y firmado, siete días de historia"
audience: tecnico
area: decisiones
order: 74
---

# ADR-0064: Respaldo diario de MySQL

- **Estado:** Aceptada
- **Fecha:** 2026-10-26

## Contexto
El [ADR-0026](0026-despliegue.md) aprobó un respaldo diario de la base, y las [mejoras aplicadas](../mejoras/aplicadas.md) lo daban por hecho. Al revisar el anexo apareció que no existía: ningún servicio ni tarea hacía copias de la base. La documentación decía algo falso.

El volcado de la base lleva nombres, correos e historial de los incidentes. La regla del proyecto es que nada sensible quede en texto plano, y un respaldo es justo el archivo que más se copia y se mueve.

## Alternativas evaluadas
- Un servicio `backup` en el `docker-compose.yml` base, con la misma imagen de MySQL y un script propio.
- Una tarea del scheduler de Laravel que llame a `mysqldump`.
- El paquete `spatie/laravel-backup`.
- Dejarlo para la fase de despliegue.
- Pasarlo a las mejoras propuestas.

## Decisión
Un servicio `backup` en el compose base, así corre igual en local y en el despliegue:

- **Imagen:** `mysql:8.4.11`, la misma de la base. Ya trae `mysqldump`, `gzip`, `openssl`, `gosu` y la zona horaria de Bogotá. No suma imágenes ni versiones.
- **Volcado:** `mysqldump --single-transaction --quick` lee una foto consistente de InnoDB sin bloquear las tablas: la app sigue funcionando durante el respaldo.
- **Cifrado:** AES-256-CBC con la llave derivada por PBKDF2 (600.000 iteraciones), más una firma HMAC-SHA256 en un archivo `.hmac` al lado. La restauración revisa la firma antes de descifrar, así que un archivo dañado, modificado o cifrado con otra llave se rechaza con un mensaje claro.
- **Llave:** en local se genera sola en el volumen `trazo_backup-key`, igual que la `APP_KEY`. En producción `BACKUP_ENCRYPTION_KEY` es obligatoria; si falta, el servicio no arranca. La llave y las credenciales pasan a `openssl` y `mysqldump` por archivos temporales privados, nunca como argumentos visibles.
- **Horario:** cada día a las 3:00 en `America/Bogota` (`BACKUP_AT`, `BACKUP_TIMEZONE`). Si al arrancar no hay un respaldo de las últimas 24 horas, hace uno de inmediato.
- **Historia:** 7 días (`BACKUP_RETENTION_DAYS`) en el volumen `trazo_backups`.
- **Mínimo privilegio:** usa el usuario de la app, no root, y solo la red interna `data`. Como root solo prepara carpetas y llave; el trabajo lo hace el usuario `mysql` (`gosu`).
- **Salud y recursos:** el healthcheck falla si el último respaldo tiene más de 26 horas. Tiene límites de 0,5 CPU y 256 MB.
- **Restauración:** `trazo-backup restore <archivo> <base>`. La base de destino es obligatoria, para que nadie sobrescriba la principal por descuido. Se probó restaurando en `trazo_test`: los mismos conteos por tabla que la base de origen.

## Alternativas descartadas
- **Scheduler de Laravel:** mezcla la operación de la base con la app y obliga a instalar el cliente de MySQL en la imagen de PHP. Además, si la app está caída, tampoco hay respaldo.
- **`spatie/laravel-backup`:** suma una dependencia con muchas más funciones de las necesarias (destinos en la nube, notificaciones). Tiene el mismo problema de acoplarse a la app.
- **Dejarlo para el despliegue:** la documentación seguiría diciendo algo falso, y el servicio se puede probar ya en local.
- **Pasarlo a propuestas:** se perdería una mejora ya aprobada que cuesta poco construir.
- **Cifrado con GPG o `age`:** suma una herramienta que la imagen no trae. `openssl` con firma HMAC da confidencialidad e integridad con lo que ya existe.

## Consecuencias
- Con la configuración por defecto se puede perder como máximo un día de datos. Si el negocio lo pide, se baja con un horario más frecuente o con los binlogs (queda en [mejoras propuestas](../mejoras/propuestas.md)).
- Los respaldos viven en el mismo servidor. Una copia fuera del servidor sigue como mejora propuesta.
- **Sin la llave, los respaldos no sirven.** En producción hay que guardarla fuera del servidor, junto a la `APP_KEY`.
- El volcado no incluye procedimientos ni eventos de MySQL, porque Trazo no los usa. Si algún día se agregan, hay que sumar `--routines --events` y los privilegios correspondientes.

## Dónde está en el código
- [`docker/mysql/backup.sh`](../../docker/mysql/backup.sh): subcomandos `schedule`, `run`, `list`, `check` y `restore`.
- [`docker-compose.yml`](../../docker-compose.yml): servicio `backup` y volúmenes `backups` y `backup-key`.
- [`.env.example`](../../.env.example): variables `BACKUP_*`.
- Guía: [Respaldos](../operacion/respaldos.md).
