---
title: Acciones de sistema
description: Reiniciar workers, reiniciar el tiempo real y limpiar la caché de datos desde la app, con permiso, código, límite de frecuencia y registro, sin acceso a Docker.
audience: tecnico
area: flujos
module: system
order: 4
---

# Acciones de sistema

Desde el panel de salud, el Super admin (`system.actions.execute`) puede ejecutar acciones dentro del alcance de la propia app. Ninguna toca Docker ni el servidor: usan las señales de Laravel y Docker vuelve a levantar lo que se cierra.

| Acción | Qué hace de verdad |
|---|---|
| Reiniciar workers | `queue:restart`: cada worker termina el job en curso y se cierra; Docker lo levanta de nuevo con el código actual |
| Reiniciar tiempo real | Escribe la clave `laravel:reverb:restart`, la misma de `artisan reverb:restart`: Reverb la revisa cada pocos segundos y se cierra |
| Limpiar caché de datos | Sube la generación de la caché del dashboard. Los bloqueos, límites y sesiones no se tocan |

Las mismas protecciones aplican al [modo mantenimiento](mantenimiento.md) y a los [jobs fallidos](jobs-fallidos.md).

```flujo
carriles: [Super admin, Navegador, API, Redis]
pasos:
  - id: pulsa
    carril: Super admin
    tipo: inicio
    texto: Pulsa la acción en la tarjeta
    va: codigo
  - id: codigo
    carril: Navegador
    texto: Pide su código como confirmación
    detalle: El diálogo del código explica qué va a pasar; escribirlo es la confirmación.
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía POST /system/actions/{acción}
    va: limite
  - id: limite
    carril: API
    tipo: decision
    texto: ¿Menos de 5 acciones en el minuto?
    detalle: 'Límite `system-actions`: 5 por minuto por persona.'
    si: permiso
    no: e429
  - id: e429
    carril: API
    tipo: error
    texto: Responde 429
  - id: permiso
    carril: API
    tipo: decision
    texto: ¿Tiene system.actions.execute?
    detalle: El permiso va primero, para que quien no lo tiene no gaste intentos de código.
    si: valida
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403
  - id: valida
    carril: API
    tipo: decision
    texto: ¿Código válido?
    detalle: Sin repetición; cinco incorrectos en un minuto bloquean la reautenticación (423).
    si: ejecuta
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422 o 423
  - id: ejecuta
    carril: Redis
    texto: Deja la señal o sube la generación
    va: registra
  - id: registra
    carril: API
    texto: Lo registra en admin_actions
    detalle: '`system_workers_restarted`, `system_realtime_restarted` o `system_cache_cleared`. Aparece en la auditoría de administración.'
    va: listo
  - id: listo
    carril: Super admin
    tipo: fin
    texto: Ve el aviso y el panel actualizado
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `TOO_MANY_REQUESTS` | 429 | Más de 5 acciones en un minuto | Hiciste demasiados intentos seguidos. Espera {wait} y vuelve a intentarlo. |
| `MFA_CODE_INVALID` | 422 | El código no es correcto | El código no es correcto. Escribe el que muestra ahora tu aplicación de autenticación. |
| `REAUTHENTICATION_LOCKED` | 423 | Cinco códigos incorrectos en un minuto | Escribiste varios códigos incorrectos seguidos. Espera {wait} y vuelve a intentarlo. |
| `FORBIDDEN_ACTION` | 403 | Sin `system.actions.execute` | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |

## Dónde está en el código

- [`RunSystemAction`](../../../backend/app/Modules/System/Application/RunSystemAction.php) y [`SystemGuard`](../../../backend/app/Modules/System/Application/SystemGuard.php).
- [`LaravelSystemControls`](../../../backend/app/Modules/System/Infrastructure/LaravelSystemControls.php): qué hace cada acción.
- [`SystemController`](../../../backend/app/Modules/System/Http/SystemController.php) y [`routes.php`](../../../backend/app/Modules/System/Http/routes.php): límite `system-actions`.
- [`useHealth`](../../../frontend/src/modules/system/application/useHealth.ts) y [`useReauth`](../../../frontend/src/shared/ui/feedback/useReauth.ts).
- Explicación: [Módulo System](../../backend/system.md).
