---
title: Modo mantenimiento
description: Cerrar Trazo a todos menos a quien opera el sistema, mientras se hace una tarea delicada, y volver a abrirlo.
audience: tecnico
area: flujos
module: system
order: 5
---

# Modo mantenimiento

El modo mantenimiento cierra la API para todos con un 503, menos para quien puede ejecutar acciones de sistema, que sigue trabajando y ve un aviso fijo. Se guarda como una marca en Redis (con quién lo activó y cuándo), no en archivos: funciona igual con varias copias de la app.

```flujo
carriles: [Super admin, API, Redis, Otras personas]
pasos:
  - id: activa
    carril: Super admin
    tipo: inicio
    texto: Activa el mantenimiento con su código
    detalle: '`PUT /system/maintenance`, con las mismas protecciones de las acciones de sistema: límite, permiso, código y registro.'
    va: ya
  - id: ya
    carril: API
    tipo: decision
    texto: ¿Ya estaba activo?
    detalle: Pedir el estado que ya tiene es un conflicto y no se registra dos veces.
    si: e409
    no: marca
  - id: e409
    carril: API
    tipo: error
    texto: Responde 409
  - id: marca
    carril: Redis
    texto: Guarda la marca system:maintenance
    detalle: 'Con nombre y fecha. Registro `system_maintenance_enabled` en `admin_actions`.'
    va: peticion
  - id: peticion
    carril: Otras personas
    texto: Hacen una petición
    va: abierta
  - id: abierta
    carril: API
    tipo: decision
    texto: ¿Es ingreso, MFA o cerrar sesión?
    detalle: 'Esas rutas siguen abiertas para que quien opera pueda entrar. `/up` (el chequeo de Docker) responde normal.'
    si: pasa
    no: puede
  - id: pasa
    carril: API
    tipo: fin
    texto: Responde normal
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Tiene system.actions.execute?
    si: aviso
    no: e503
  - id: e503
    carril: Otras personas
    tipo: error
    texto: Reciben 503 con el aviso de mantenimiento
    detalle: 'Cada acción muestra "Trazo está en mantenimiento. Vuelve a intentarlo en unos minutos." Al abrir o recargar Trazo, la sesión no se puede comprobar y va a la pantalla "No pudimos cargar Trazo" con "Reintentar". La sesión no se cierra.'
  - id: aviso
    carril: Super admin
    tipo: fin
    texto: Sigue trabajando con el aviso fijo
    detalle: 'Cada respuesta trae `X-Maintenance: on`, y el shell muestra el aviso arriba hasta que lo apague.'
```

Al apagarlo (el mismo endpoint con `enabled: false`) se borra la marca, queda `system_maintenance_disabled` en el registro y "Reintentar" vuelve a funcionar para todos.

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `SERVICE_UNAVAILABLE` | 503 | Trazo está en mantenimiento | Trazo está en mantenimiento. Vuelve a intentarlo en unos minutos. |
| `MAINTENANCE_ALREADY_ENABLED` | 409 | Ya estaba activo | El modo mantenimiento ya estaba activo. Actualiza el panel para ver su estado. |
| `MAINTENANCE_ALREADY_DISABLED` | 409 | Ya estaba apagado | El modo mantenimiento ya estaba apagado. Actualiza el panel para ver su estado. |

## Dónde está en el código

- [`SetMaintenance`](../../../backend/app/Modules/System/Application/SetMaintenance.php) y [`CacheMaintenanceSwitch`](../../../backend/app/Modules/System/Infrastructure/CacheMaintenanceSwitch.php).
- [`EnforceMaintenance`](../../../backend/app/Modules/System/Http/Middleware/EnforceMaintenance.php): quién pasa y quién recibe 503.
- [`maintenance.ts`](../../../frontend/src/shared/http/maintenance.ts): el aviso fijo por la cabecera.
- Explicación: [Módulo System](../../backend/system.md).
