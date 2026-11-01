---
title: Cambiar rol o permisos (reautenticación)
description: Cambiar el rol de una persona o sus permisos uno por uno, con la jerarquía, la regla de no conceder lo que no se tiene y el código de la aplicación para lo sensible.
audience: tecnico
area: flujos
module: users
order: 2
---

# Cambiar rol o permisos (reautenticación)

Cada persona tiene un rol y, encima, excepciones propias: permisos concedidos y quitados (`user_permissions`). Sus permisos efectivos son los del rol, más los concedidos, menos los quitados (gana el quitado).

- **Cambiar el rol** (`PUT /users/{id}/roles`) deja a la persona con los permisos exactos del rol nuevo: sus excepciones se borran y el registro guarda cuáles eran.
- **Cambiar permisos** (`PUT /users/{id}/permissions`) recibe la lista que la persona debe tener; la API guarda la diferencia con el rol como excepciones.

Lo sensible (permisos de usuarios, roles o sistema) pide el código de la aplicación autenticadora de quien administra, aunque ya tenga la sesión abierta.

```flujo
carriles: [Administración, Navegador, API, Base de datos, Persona afectada]
pasos:
  - id: cambia
    carril: Administración
    tipo: inicio
    texto: Elige el rol nuevo o marca los permisos
    detalle: En la ficha de la persona. `PermissionChecklist` muestra solo lo que puede conceder y marca los sensibles.
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía el cambio sin código
    detalle: '`useReauth` intenta primero sin código; solo si la API lo pide, abre el diálogo.'
    va: puede
  - id: puede
    carril: API
    tipo: decision
    texto: ¿Puede administrar a esta persona?
    detalle: 'Necesita el permiso de la acción, no puede ser él mismo y debe tener un nivel mayor. El Super admin administra a todos menos a sí mismo.'
    si: alcance
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403
  - id: alcance
    carril: API
    tipo: decision
    texto: ¿Rol por debajo y permisos que él tiene?
    detalle: 'Nadie concede lo que no tiene, salvo la cima. Al Super admin no se le conceden ni se le quitan permisos sueltos: los tiene todos por su rol.'
    si: sensible
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422 o 409
  - id: sensible
    carril: API
    tipo: decision
    texto: ¿Toca algo sensible?
    detalle: El rol anterior o el nuevo, o los permisos que cambian.
    si: codigo
    no: guarda
  - id: codigo
    carril: API
    tipo: decision
    texto: ¿Llegó un código válido?
    detalle: 'Sin código responde `REAUTHENTICATION_REQUIRED` y el navegador abre `AppCodeDialog`. El código no se puede repetir. Cinco incorrectos en un minuto bloquean la reautenticación (423) y dejan el evento `ReauthenticationLocked`. Quien no tiene MFA no puede confirmar: `REAUTHENTICATION_UNAVAILABLE`.'
    si: guarda
    no: pide
  - id: pide
    carril: Navegador
    texto: Pide el código y reenvía
    va: codigo
  - id: guarda
    carril: Base de datos
    texto: Guarda el rol o las excepciones
    detalle: 'Con lock sobre los Super admin activos: si el cambio dejaría a Trazo sin ninguno, responde `LAST_SUPER_ADMIN`. Registro en `admin_actions` con el antes y el después (y las excepciones borradas, si cambió el rol).'
    va: avisa
  - id: avisa
    carril: Persona afectada
    tipo: fin
    texto: Sus pantallas se ajustan al instante y recibe un correo
    detalle: 'Por `session.changed` en su canal personal (ver "Cambio de rol o desactivación en vivo"). El correo dice qué cambió.'
```

El paso 7 vuelve al 6 cuando falta el código: es la única vuelta del diagrama, con línea punteada.

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `USER_SELF_MANAGEMENT` | 403 | Intenta cambiar su propio rol o permisos | Tu propio rol, tus permisos, tu estado y tu acceso los cambia otra persona que administre Trazo. |
| `USER_OUTRANKED` | 403 | La persona tiene su mismo nivel o uno mayor | Esta persona tiene un rol de tu mismo nivel o superior, así que no puedes cambiar su cuenta. |
| `ROLE_NOT_ASSIGNABLE` | 422 | El rol nuevo no está por debajo del suyo | No puedes dar ese rol: solo puedes asignar roles por debajo del tuyo. |
| `PERMISSION_NOT_GRANTABLE` | 422 | Concede un permiso que él no tiene | Solo puedes conceder permisos que tú también tienes. Revisa los permisos marcados y vuelve a intentarlo. |
| `USER_ROOT_FULL_ACCESS` | 409 | Permisos sueltos a un Super admin | El Super admin tiene todos los permisos por su rol, así que no se le conceden ni se le quitan permisos uno por uno. |
| `LAST_SUPER_ADMIN` | 409 | El cambio dejaría sin Super admin activo | Trazo necesita al menos un Super admin activo. Antes de este cambio, da el rol de Super admin a otra persona. |
| `REAUTHENTICATION_REQUIRED` | 422 | Falta el código | Para confirmar este cambio, escribe el código de tu aplicación de autenticación. |
| `REAUTHENTICATION_UNAVAILABLE` | 422 | Quien administra no tiene MFA | Para hacer este cambio necesitas tener activa la verificación en dos pasos. Actívala desde Mi cuenta y vuelve a intentarlo. |
| `REAUTHENTICATION_LOCKED` | 423 | Cinco códigos incorrectos en un minuto | Escribiste varios códigos incorrectos seguidos. Espera {wait} y vuelve a intentarlo. |

## Dónde está en el código

- [`ChangeUserRole`](../../../backend/app/Modules/Users/Application/ChangeUserRole.php) y [`ChangeUserPermissions`](../../../backend/app/Modules/Users/Application/ChangeUserPermissions.php).
- [`UserAccess`](../../../backend/app/Modules/Users/Domain/UserAccess.php) y [`RootGuard`](../../../backend/app/Modules/Users/Application/RootGuard.php).
- [`AuthAccountSecurity`](../../../backend/app/Modules/Users/Infrastructure/AuthAccountSecurity.php): reautenticación con el código.
- [`UserPermissions`](../../../backend/app/Modules/Users/Infrastructure/UserPermissions.php) y [`PermissionSql`](../../../backend/app/Modules/Users/Infrastructure/PermissionSql.php): permisos efectivos.
- [`useReauth`](../../../frontend/src/shared/ui/feedback/useReauth.ts), [`AppCodeDialog`](../../../frontend/src/shared/ui/feedback/AppCodeDialog.vue), [`PermissionChecklist`](../../../frontend/src/modules/admin/presentation/components/PermissionChecklist.vue) y [`UserDetailPage`](../../../frontend/src/modules/admin/presentation/UserDetailPage.vue).
- Explicación: [Módulo Users](../../backend/users.md).
