---
title: Activar y desactivar
description: Desactivar una cuenta cierra sus sesiones al instante y nunca deja a Trazo sin Super admin; activarla la devuelve sin tocar nada más.
audience: tecnico
area: flujos
module: users
order: 3
---

# Activar y desactivar

Una cuenta no se borra: se desactiva. Así sus incidentes, su historial y su auditoría siguen apuntando a una persona real. Una cuenta desactivada no puede ingresar (recibe el mismo mensaje que unos datos incorrectos) y no aparece para recibir incidentes. Sus incidentes asignados no se reasignan solos: es decisión de quien supervisa.

```flujo
carriles: [Administración, Navegador, API, Base de datos, Persona afectada]
pasos:
  - id: pulsa
    carril: Administración
    tipo: inicio
    texto: Pulsa Desactivar y confirma
    detalle: En la ficha de la persona; el botón aparece solo si la API lo permite (`actions`).
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía POST /users/{id}/deactivate
    va: puede
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Puede administrarla y está activa?
    detalle: '`users.activate`, no ser él mismo y tener un nivel mayor.'
    si: raiz
    no: eneg
  - id: eneg
    carril: API
    tipo: error
    texto: Responde 403 o 409
  - id: raiz
    carril: Base de datos
    tipo: decision
    texto: Si es Super admin, ¿queda otro activo?
    detalle: 'Lee los Super admin activos con `FOR UPDATE` dentro de la transacción: dos desactivaciones a la vez no pueden dejar a Trazo sin ninguno.'
    si: desactiva
    no: e409
  - id: e409
    carril: Base de datos
    tipo: error
    texto: Responde 409 LAST_SUPER_ADMIN
  - id: desactiva
    carril: Base de datos
    texto: La desactiva y borra sus sesiones
    detalle: 'En la misma transacción: `is_active = false`, todos sus tokens revocados y `admin_actions` (`user_deactivated`).'
    va: senal
  - id: senal
    carril: Persona afectada
    texto: Recibe session.changed
    detalle: Por su canal personal, si tiene Trazo abierto.
    va: fuera
  - id: fuera
    carril: Persona afectada
    tipo: fin
    texto: Su pantalla se cierra con aviso y recibe un correo
    detalle: 'Al pedir `/auth/me` la API responde `UNAUTHENTICATED`. Aunque el aviso no llegue, la regla de cada token rechaza las cuentas desactivadas en la siguiente petición.'
```

Activar (`POST /users/{id}/activate`) sigue el mismo camino sin el paso del Super admin ni el cierre de sesiones; la persona vuelve a entrar con su contraseña de siempre y recibe un correo.

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `USER_ALREADY_INACTIVE` | 409 | Ya estaba desactivada | Esta cuenta ya está desactivada. Recarga la página para ver su estado actual. |
| `USER_ALREADY_ACTIVE` | 409 | Ya estaba activa | Esta cuenta ya está activa. Recarga la página para ver su estado actual. |
| `LAST_SUPER_ADMIN` | 409 | Es el último Super admin activo | Trazo necesita al menos un Super admin activo. Antes de este cambio, da el rol de Super admin a otra persona. |
| `USER_SELF_MANAGEMENT` | 403 | Intenta desactivarse a sí mismo | Tu propio rol, tus permisos, tu estado y tu acceso los cambia otra persona que administre Trazo. |
| `USER_OUTRANKED` | 403 | La persona tiene su mismo nivel o uno mayor | Esta persona tiene un rol de tu mismo nivel o superior, así que no puedes cambiar su cuenta. |

## Dónde está en el código

- [`ChangeUserStatus`](../../../backend/app/Modules/Users/Application/ChangeUserStatus.php): transacción, sesiones, aviso y correo.
- [`RootGuard`](../../../backend/app/Modules/Users/Application/RootGuard.php): siempre un Super admin activo.
- [`UserAccess`](../../../backend/app/Modules/Users/Domain/UserAccess.php).
- [`AuthServiceProvider`](../../../backend/app/Modules/Auth/Infrastructure/AuthServiceProvider.php): un token de cuenta desactivada no vale.
- [`UserDetailPage`](../../../frontend/src/modules/admin/presentation/UserDetailPage.vue) y [`AccessCard`](../../../frontend/src/modules/admin/presentation/components/AccessCard.vue).
- Explicación: [Módulo Users](../../backend/users.md).
