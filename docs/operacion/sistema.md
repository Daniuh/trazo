---
title: Panel de sistema
description: Cuándo usar cada acción del panel de sistema, qué revisa la tarea de cada minuto y cómo salir del modo mantenimiento si algo falla.
audience: tecnico
area: operacion
order: 5
---

# Panel de sistema

El Super admin resuelve desde **Sistema** los problemas operativos más comunes, sin entrar al servidor. Lo que pasa por debajo está en el [Módulo System](../backend/system.md).

## Cuándo usar cada acción

| Señal en el panel | Qué hacer |
|---|---|
| Workers en "Falla" (sin señal hace más de 2 min) o la cola creciendo | **Reiniciar workers.** Terminan el job en curso y Docker los levanta otra vez en segundos |
| Tiempo real en "Falla", o las pantallas dejaron de recibir avisos | **Reiniciar** en Tiempo real. Las pantallas se reconectan solas |
| Las cifras del inicio no cuadran con el listado | **Limpiar caché de datos.** Las cifras se calculan de nuevo al pedirlas |
| Jobs fallidos (por ejemplo, el correo no respondía) | Corregir la causa y **Reintentar**; descartar los que ya no tienen sentido |
| Hay que hacer un cambio delicado (migración manual, restaurar un respaldo) | **Activar** el modo mantenimiento y desactivarlo al terminar |

Cada acción pide el código de la aplicación de autenticación, permite 5 por minuto y queda en **Auditoría → Administración**.

Si el panel muestra la base de datos o Redis en "Falla", la acción no está en la app: hay que revisar los contenedores en el servidor (`docker compose -p trazo ps` y sus logs).

## La revisión de cada minuto

El servicio `scheduler` corre `system:check` cada minuto:

- Deja su propia señal de vida y encola la del worker.
- Registra un evento si Redis o Reverb no responden, si el worker lleva más de 2 minutos sin señal, o si la cola tiene más de 100 pendientes o el más viejo espera más de 5 minutos.

Cada día, a las 03:30, `system:prune-events` borra los eventos resueltos hace más de 90 días.

Para correrla a mano:

```bash
docker compose -p trazo exec app php artisan system:check
```

> Al cambiar código de Laravel hay que reiniciar `worker`, `scheduler` y `reverb`: son procesos largos y no recargan el código solos.

## Si el modo mantenimiento queda activo

Con el modo activo, el Super admin sigue entrando: el ingreso y el segundo paso del MFA siguen abiertos. Si aun así no se puede desactivar desde la pantalla (por ejemplo, porque nadie con el permiso tiene su teléfono a mano), se quita la marca en Redis desde el servidor:

```bash
docker compose -p trazo exec app php artisan tinker --execute="cache()->forget('system:maintenance');"
```

## Variables

| Variable | Por defecto | Para qué |
|---|---|---|
| `SYSTEM_EVENTS_RETENTION_DAYS` | `90` | Días que se guardan los eventos resueltos |
