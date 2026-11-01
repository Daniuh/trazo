---
title: Cambio de rol o desactivación en vivo
description: Cómo un cambio de rol, de permisos o de estado de una cuenta llega al instante a sus pantallas abiertas, sin esperar a la siguiente petición.
audience: tecnico
area: flujos
module: realtime
order: 3
---

# Cambio de rol o desactivación en vivo

Cuando alguien administra a una persona (le cambia el rol o los permisos, edita sus datos, la desactiva, restablece su MFA o su acceso) o esa persona cambia o recupera su contraseña, la API avisa por su canal personal. Lo mismo pasa con todos los miembros de un rol cuando se edita ese rol. El mensaje no trae datos: el navegador vuelve a pedir `/auth/me` y la respuesta decide qué pasa.

```flujo
carriles: [Administración, API, Base de datos, Reverb, Persona afectada]
pasos:
  - id: cambia
    carril: Administración
    tipo: inicio
    texto: Cambia rol, permisos o estado de una persona
    va: guarda
  - id: guarda
    carril: Base de datos
    texto: Guarda el cambio y su registro
    detalle: 'Al desactivar, restablecer el MFA o el acceso, también borra todos sus tokens de sesión. Todo queda en `admin_actions`.'
    va: senal
  - id: senal
    carril: API
    texto: Envía session.changed a su canal personal
    detalle: 'Puerto compartido `SessionSignals`; el aviso sale cuando la transacción se confirma. Si se editó un rol, va a todos sus miembros.'
    va: entrega
  - id: entrega
    carril: Reverb
    texto: Lo entrega a cada pestaña abierta
    va: me
  - id: me
    carril: Persona afectada
    texto: Su pantalla pide GET /auth/me
    va: vale
  - id: vale
    carril: API
    tipo: decision
    texto: ¿Su sesión sigue valiendo?
    si: actualiza
    no: cierra
  - id: cierra
    carril: Persona afectada
    tipo: error
    texto: Se cierra la sesión y va al login
    detalle: 'La API responde `UNAUTHENTICATED`, el cliente HTTP borra el token y lleva al login con "Tu sesión terminó. Inicia sesión de nuevo para continuar."'
  - id: actualiza
    carril: Persona afectada
    tipo: fin
    texto: Menú y botones se ajustan a sus permisos nuevos
    detalle: El menú del dock, los botones de cada pantalla y los canales de tiempo real (entra a los nuevos, sale de los que ya no aplican).
```

Cambiar la contraseña desde Mi cuenta también envía este aviso: las otras sesiones de la persona se cierran al instante, y la pestaña desde la que la cambió sigue abierta porque su token no se revocó.

## Dónde está en el código

- [`ChangeUserRole`](../../../backend/app/Modules/Users/Application/ChangeUserRole.php), [`ChangeUserPermissions`](../../../backend/app/Modules/Users/Application/ChangeUserPermissions.php), [`ChangeUserStatus`](../../../backend/app/Modules/Users/Application/ChangeUserStatus.php), [`ResetUserAccess`](../../../backend/app/Modules/Users/Application/ResetUserAccess.php), [`UpdateUser`](../../../backend/app/Modules/Users/Application/UpdateUser.php) y [`UpdateRole`](../../../backend/app/Modules/Users/Application/UpdateRole.php): quién envía el aviso.
- [`SessionSignals`](../../../backend/app/Modules/Shared/Application/Ports/SessionSignals.php) y [`BroadcastSessionSignals`](../../../backend/app/Modules/Realtime/Infrastructure/BroadcastSessionSignals.php).
- [`useRealtimeNotices`](../../../frontend/src/modules/realtime/presentation/useRealtimeNotices.ts): pide `/auth/me` al recibirlo.
- Explicación: [Módulo Realtime](../../backend/realtime.md).
