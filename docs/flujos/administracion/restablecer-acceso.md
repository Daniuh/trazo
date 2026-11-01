---
title: Restablecer MFA o acceso
description: Qué hace el Super admin cuando alguien pierde su teléfono o su contraseña, siempre con su propio código y cerrando las sesiones de la persona.
audience: tecnico
area: flujos
module: users
order: 4
---

# Restablecer MFA o acceso

Dos acciones para cuando una persona no puede entrar. Por defecto son solo del Super admin (`users.mfa.reset` y `users.access.reset`), salvo que se concedan a alguien en particular. Las dos piden el código de la aplicación de quien las hace, cierran todas las sesiones de la persona y le avisan por correo.

- **Restablecer MFA:** quita su factor y sus códigos de recuperación. En el siguiente ingreso lo configura de nuevo.
- **Restablecer acceso:** su contraseña deja de servir (se reemplaza por una aleatoria) y debe crear una nueva con "¿Olvidaste tu contraseña?".

```flujo
carriles: [Super admin, Navegador, API, Base de datos, Persona afectada]
pasos:
  - id: pulsa
    carril: Super admin
    tipo: inicio
    texto: Pulsa Restablecer MFA o Restablecer acceso
    va: codigo
  - id: codigo
    carril: Navegador
    texto: Pide su código y envía la acción
    detalle: '`DELETE /users/{id}/mfa` o `POST /users/{id}/access-reset`, con `mfa_code`.'
    va: puede
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Tiene el permiso y la supera en nivel?
    detalle: Para restablecer el MFA, la persona además debe tenerlo activo.
    si: valida
    no: eneg
  - id: eneg
    carril: API
    tipo: error
    texto: Responde 403 o 409
  - id: valida
    carril: API
    tipo: decision
    texto: ¿Su código es válido?
    detalle: Sin repetición; cinco incorrectos en un minuto bloquean la reautenticación.
    si: aplica
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422 o 423
  - id: aplica
    carril: Base de datos
    texto: Quita el MFA o la contraseña y cierra sesiones
    detalle: 'En una transacción: `user_mfa_factors` y `user_recovery_codes` borrados, o contraseña aleatoria; todos sus tokens revocados; `admin_actions` (`user_mfa_reset` o `user_access_reset`).'
    va: fuera
  - id: fuera
    carril: Persona afectada
    texto: Sus pantallas abiertas se cierran
    detalle: Por `session.changed` en su canal personal.
    va: correo
  - id: correo
    carril: Persona afectada
    tipo: fin
    texto: Recibe el correo y vuelve a entrar
    detalle: 'Con MFA restablecido: ingresa con su contraseña y configura el MFA. Con acceso restablecido: crea su contraseña desde `/recuperar` (su MFA sigue activo, así que lo pide al final).'
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `USER_MFA_NOT_ENABLED` | 409 | La persona no tiene MFA activo | Esta persona no tiene activa la verificación en dos pasos, así que no hay nada que restablecer. |
| `MFA_CODE_INVALID` | 422 | El código de quien administra no es correcto | El código no es correcto. Escribe el que muestra ahora tu aplicación de autenticación. |
| `REAUTHENTICATION_LOCKED` | 423 | Cinco códigos incorrectos en un minuto | Escribiste varios códigos incorrectos seguidos. Espera {wait} y vuelve a intentarlo. |
| `USER_OUTRANKED` | 403 | La persona tiene su mismo nivel o uno mayor | Esta persona tiene un rol de tu mismo nivel o superior, así que no puedes cambiar su cuenta. |
| `USER_SELF_MANAGEMENT` | 403 | Intenta restablecer lo suyo | Tu propio rol, tus permisos, tu estado y tu acceso los cambia otra persona que administre Trazo. |

## Dónde está en el código

- [`ResetUserAccess`](../../../backend/app/Modules/Users/Application/ResetUserAccess.php): `resetMfa` y `resetAccess`.
- [`AuthAccountSecurity`](../../../backend/app/Modules/Users/Infrastructure/AuthAccountSecurity.php): código de quien administra, quitar el MFA y cerrar sesiones.
- [`UserAccessController`](../../../backend/app/Modules/Users/Http/UserAccessController.php) y [`UserAccessRequest`](../../../backend/app/Modules/Users/Http/Requests/UserAccessRequest.php).
- [`AccessCard`](../../../frontend/src/modules/admin/presentation/components/AccessCard.vue) y [`useReauth`](../../../frontend/src/shared/ui/feedback/useReauth.ts).
- Explicación: [Módulo Users](../../backend/users.md).
