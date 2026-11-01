---
title: Módulo Users
description: Administración de personas y de roles, jerarquía por nivel, permisos por persona, reautenticación y registro de acciones administrativas.
audience: tecnico
area: backend
module: users
order: 6
---

# Módulo Users

Se encarga de administrar a las personas que usan Trazo: crearlas, corregir sus datos, darles un rol, concederles o quitarles permisos, activarlas o desactivarlas, y restablecer su MFA o su acceso. También administra los **roles** (crearlos, editarlos y eliminarlos) y es el dueño de los **permisos efectivos**, que consultan todos los módulos.

## Endpoints

Todos exigen sesión. Las rutas de personas responden `403` a quien no tiene `users.view`.

| Método | Ruta | Permiso | Código de quien administra | Respuesta |
|---|---|---|---|---|
| `GET` | `/api/v1/users` | `users.view` | — | Página de personas (`search`, `sort`, `filter[role]`, `filter[status]`, `filter[mfa]`) |
| `POST` | `/api/v1/users` | `users.create` | Si el rol trae permisos sensibles | `201` con la ficha; admite `Idempotency-Key` |
| `GET` | `/api/v1/users/{id}` | `users.view` | — | Ficha con permisos, incidentes pendientes asignados y `actions` |
| `PATCH` | `/api/v1/users/{id}` | `users.update` | — | Ficha; si cambió el correo, avisa a la dirección anterior |
| `PUT` | `/api/v1/users/{id}/roles` | `users.roles.assign` | Si el rol anterior o el nuevo traen permisos sensibles | Ficha |
| `PUT` | `/api/v1/users/{id}/permissions` | `users.permissions.grant` | Si cambia un permiso sensible | Ficha. Recibe los permisos que debe **tener**; el back calcula las excepciones |
| `POST` | `/api/v1/users/{id}/activate` y `/deactivate` | `users.activate` | — | Ficha; desactivar cierra sus sesiones al instante |
| `DELETE` | `/api/v1/users/{id}/mfa` | `users.mfa.reset` | Siempre | Ficha |
| `POST` | `/api/v1/users/{id}/access-reset` | `users.access.reset` | Siempre | Ficha |
| `GET` | `/api/v1/roles` | `users.view` o `roles.manage` | — | Roles de mayor a menor nivel, con permisos, política de MFA, personas que lo tienen, si quien consulta lo puede dar (`assignable`) y `actions` |
| `GET` | `/api/v1/roles/{code}` | `users.view` o `roles.manage` | — | Un rol |
| `POST` | `/api/v1/roles` | `roles.manage` | Si trae permisos sensibles | `201` con el rol; admite `Idempotency-Key` |
| `PATCH` | `/api/v1/roles/{code}` | `roles.manage` | Si cambian permisos sensibles, el nivel o la política de MFA se vuelve más permisiva | El rol |
| `DELETE` | `/api/v1/roles/{code}` | `roles.manage` | — | `204`; solo un rol creado que nadie tiene |
| `GET` | `/api/v1/permissions` | `users.view` o `roles.manage` | — | Permisos con su módulo, si son sensibles y si quien consulta los puede conceder (`grantable`) |

Los permisos sensibles son los de los módulos `users`, `roles` y `system`. El código de la aplicación de quien administra viaja en el campo `mfa_code` del cuerpo (`code` es el identificador de un rol).

```json
GET /api/v1/users/01m5nzqhhgqs8myzj3rq3smqex

{
  "data": {
    "id": "01m5nzqhhgqs8myzj3rq3smqex",
    "name": "Laura Gómez",
    "email": "agente1@trazo.test",
    "is_active": true,
    "role": { "code": "agent", "name": "Agente", "level": 40 },
    "mfa_enabled": true,
    "last_login_at": "2026-10-24T15:46:00+00:00",
    "created_at": "2026-10-23T22:37:00+00:00",
    "permissions": {
      "effective": ["audit.admin.view", "dashboard.view.own", "incidents.create", "…"],
      "role": ["dashboard.view.own", "incidents.create", "incidents.take", "…"],
      "granted": ["audit.admin.view"],
      "denied": ["incidents.take"]
    },
    "pending_assigned_incidents": 7,
    "is_self": false,
    "actions": {
      "update": true, "activate": false, "deactivate": true, "assign_role": true,
      "grant_permissions": true, "reset_mfa": false, "reset_access": false
    }
  }
}
```

## Jerarquía

```mermaid
flowchart LR
  SA["Super admin · 100"] --> AD["Administrador · 80"] --> SU["Supervisor · 60"] --> AG["Agente · 40"] --> SO["Solicitante · 20"]
```

- **Solo se administra hacia abajo.** Quien administra actúa sobre personas con un nivel menor que el suyo, y solo da roles por debajo de su nivel.
- **La cima (Super admin) administra a todos**, incluso a otro Super admin (por ejemplo, para restablecer su MFA si perdió el teléfono).
- **Nadie se cambia a sí mismo** el rol, los permisos, el estado ni el acceso. Sus propios datos (nombres, apellidos y correo) sí los corrige.
- **Siempre queda un Super admin activo.** Desactivar o bajar de rol a uno bloquea antes a los Super admin activos en la misma transacción. Si quedaría ninguno, responde `LAST_SUPER_ADMIN`.

## Permisos por persona

- **Efectivos** = los del rol + los concedidos − los quitados. Si un permiso está a la vez concedido y quitado, gana el quitado.
- **Cómo se cambian:** el front envía la lista de permisos que la persona debe tener. `PermissionChange` (dominio) calcula las excepciones respecto de su rol y qué gana o pierde.
- **Nadie concede lo que no tiene**, salvo la cima. Quitar sí se puede.
- **El Super admin no lleva excepciones:** su rol ya trae todo.
- **Al cambiar de rol** la persona queda con los permisos exactos del rol nuevo: se borran todas sus excepciones (concedidos y quitados), y el registro de auditoría guarda cuáles eran en `cleared` ([ADR-0069](../decisiones/0069-cambio-de-rol-con-permisos-exactos.md)).
- **Dónde se aplica la regla:** `UserPermissions` es la consulta única para la sesión, `/auth/me` y las reglas de cada módulo. `PermissionSql` es la misma regla como filtro SQL, para elegir personas por permiso (por ejemplo, el selector de "asignar a"). Una prueba compara las dos con cada permiso y cada persona.

## Cuenta nueva y acceso restablecido

- **Crear una cuenta:** nace con una contraseña aleatoria de 64 caracteres que nadie conoce. Le llega un correo de bienvenida con su rol y los pasos para crear su contraseña en `/recuperar`, que es el mismo flujo de recuperación con código. El correo no lleva contraseñas ni códigos.
- **Restablecer el acceso:** cambia la contraseña por una aleatoria, cierra sus sesiones y le avisa cómo crear una nueva.
- **Restablecer el MFA:** quita su clave y sus códigos de recuperación y cierra sus sesiones. En el siguiente ingreso lo configura de nuevo.

## Reautenticación

- **Cómo se confirma:** con el código actual de la aplicación de quien administra. El código no se puede repetir (`last_used_step`, igual que en el ingreso).
- **Respuestas posibles:**
  - Sin código: `REAUTHENTICATION_REQUIRED`.
  - Código incorrecto: `MFA_CODE_INVALID` con `attempts_left`.
  - Quien administra no tiene MFA: `REAUTHENTICATION_UNAVAILABLE`.
  - Cinco códigos incorrectos en un minuto: `423 REAUTHENTICATION_LOCKED` con `Retry-After`.
- **Orden de las revisiones:** primero el permiso y la jerarquía, después el código. Así nadie gasta intentos en algo que de todos modos no puede hacer.

## Roles

- **Quién:** `roles.manage`, por defecto solo el Super admin. Quien lo tenga trabaja con la misma jerarquía:
  - roles por debajo de su nivel;
  - un nivel nuevo también por debajo del suyo (`ROLE_LEVEL_NOT_ALLOWED`, con el máximo en `max`);
  - nunca agrega un permiso que no tiene.
- **El rol de la cima (Super admin) está bloqueado:** no se edita ni se elimina, y siempre tiene todos los permisos. Ningún otro rol llega a nivel 100.
- **Nivel único** entre 1 y 99 (índice `UNIQUE`). **Código** de 3 a 40 caracteres en minúsculas sin tildes, números y guion bajo, empezando por una letra (`RoleCode`). El código se elige al crearlo y no cambia.
- **Política de MFA del rol** (`mfa_policies`): obligatoria o no, y de 0 a 5 ingresos de gracia.
- **Editar un rol** afecta a todas sus personas desde su siguiente petición. Las excepciones de permisos que el cambio vuelve innecesarias se quitan (`pruneMemberOverrides`).
- **Piden el código de quien administra:**
  - crear un rol con permisos sensibles;
  - tocar permisos sensibles;
  - cambiar el nivel;
  - volver más permisiva la política de MFA (`MfaPolicy::isRelaxedBy`).
- **Eliminar:** los cinco roles base (`is_system`) no se eliminan, y un rol que alguien tiene tampoco. Su historial en `admin_actions` queda sin enlace (`SET NULL`), con el código y el nombre en el detalle.
- **Sin correo** a las personas de un rol cuando este cambia ([ADR-0055](../decisiones/0055-edicion-de-roles.md)).

## Registro y avisos

- **Registro en `admin_actions`:** cada cambio queda con quien lo hizo, el tipo, la persona o el rol afectado, los valores anteriores y nuevos, la IP y el navegador.
- **Avisos por correo** (en la cola y cifrados, [ADR-0062](../decisiones/0062-correos-cifrados-en-la-cola.md); texto plano, `Mail/AccountNotice` y `Mail/AccountWelcome`): bienvenida, rol cambiado, permisos cambiados, cuenta activada o desactivada, MFA restablecido, acceso restablecido y correo cambiado. Este último va a la dirección anterior.
- **No revelar qué correos existen:** las peticiones de la administración revisan el permiso **antes** de validar (`ChecksPermission`), así nadie sin permiso averigua si un correo existe por el error de "en uso".

## Errores

| Código | Estado | Cuándo |
|---|---|---|
| `FORBIDDEN_ACTION` | 403 | Le falta el permiso de la acción |
| `USER_SELF_MANAGEMENT` | 403 | Quiso cambiarse a sí mismo el rol, los permisos, el estado o el acceso |
| `USER_OUTRANKED` | 403 | La persona está en su mismo nivel o por encima |
| `USER_ROOT_FULL_ACCESS` | 409 | Quiso dar o quitar permisos a un Super admin |
| `USER_ALREADY_ACTIVE` / `USER_ALREADY_INACTIVE` | 409 | La cuenta ya estaba en ese estado |
| `USER_MFA_NOT_ENABLED` | 409 | Restablecer un MFA que no está activo |
| `LAST_SUPER_ADMIN` | 409 | El cambio dejaría el sistema sin Super admin activo |
| `ROLE_NOT_ASSIGNABLE` | 422 | El rol está en su nivel o por encima |
| `PERMISSION_NOT_GRANTABLE` | 422 | Quiso conceder permisos que no tiene (`permissions`) |
| `REAUTHENTICATION_REQUIRED` / `REAUTHENTICATION_UNAVAILABLE` | 422 | Falta el código, o quien administra no tiene MFA |
| `REAUTHENTICATION_LOCKED` | 423 | Cinco códigos incorrectos en un minuto (`Retry-After`) |
| `ALREADY_TAKEN` (campo `email`) | 422 | El correo ya es de otra cuenta |
| `ROLE_OUTRANKED` | 403 | El rol está en su mismo nivel o por encima |
| `ROLE_LOCKED` | 409 | Quiso editar o eliminar el rol Super admin |
| `ROLE_SYSTEM` | 409 | Quiso eliminar uno de los roles base |
| `ROLE_IN_USE` | 409 | Quiso eliminar un rol que alguien tiene |
| `ROLE_LEVEL_NOT_ALLOWED` | 422 | El nivel está en el suyo o por encima (`max`) |
| `ALREADY_TAKEN` (campos `code` o `level`) · `ROLE_CODE_FORMAT` (campo `code`) | 422 | Código o nivel de otro rol; código con otra forma |

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| Domain | `UserAccess`, `UserAction`, `UserDenial` | Quién puede administrar a quién y por qué no (lógica pura) |
| Domain | `Administrator`, `ManagedUser`, `RoleLevel` | Quien administra (nivel y permisos), la persona administrada y el tope de la jerarquía |
| Domain | `PermissionChange`, `SensitivePermissions`, `UserSort`, `Exceptions/UserExceptions` | Excepciones de permisos, permisos sensibles, orden del listado y errores |
| Domain | `RoleAccess`, `RoleAction`, `RoleDenial`, `ManagedRole`, `MfaPolicy`, `Exceptions/RoleExceptions` | Quién puede editar o eliminar roles, niveles permitidos y cuándo una política de MFA se vuelve más permisiva |
| Application | `ListUsers`, `CreateUser`, `UpdateUser`, `ChangeUserStatus`, `ChangeUserRole`, `ChangeUserPermissions`, `ResetUserAccess` | Casos de uso de personas |
| Application | `ListRoles`, `CreateRole`, `UpdateRole`, `DeleteRole`, `RoleGate` | Casos de uso de roles; cargar, autorizar y revisar nivel y permisos |
| Application | `UserGate`, `Administrators`, `UserProfiles`, `RootGuard` | Cargar y autorizar, armar a quien administra, la ficha y la protección del último Super admin |
| Application | `UserRepository`, `UserListQuery`, `RoleCatalog`, `RoleRepository`, `EffectivePermissions`, `AccountSecurity`, `AccountNotifier` | Puertos. El registro de acciones (`AdminLog`) vive en [Shared](shared.md#puertos-que-comparten-los-módulos) |
| Infrastructure | `EloquentUserRepository`, `EloquentUserList`, `UserMapper`, `DatabaseRoleCatalog`, `DatabaseRoleRepository` | Personas, listado y roles (lectura y edición) |
| Infrastructure | `UserPermissions`, `PermissionSql` | Permisos efectivos por persona y como filtro SQL |
| Infrastructure | `AuthAccountSecurity` | Puente con el módulo Auth: cerrar sesiones, quitar el MFA y reautenticar |
| Infrastructure | `MailAccountNotifier`, `Mail/AccountNotice`, `Mail/AccountWelcome` | Avisos por correo |
| Http | `UserController`, `UserAccessController`, `RoleController`, `PermissionController`, `UserResource`, `RoleResource`, `RequestAdmin` | Endpoints y forma de la respuesta |
| Http | `Requests/ListUsersRequest`, `CreateUserRequest` y `UpdateUserRequest` (sobre `UserRequest`), `UserAccessRequest` con `ChangeRoleRequest` y `ChangePermissionsRequest`, `CreateRoleRequest` y `UpdateRoleRequest` (sobre `RoleRequest`; [ADR-0090](../decisiones/0090-una-validacion-por-ruta.md)), `ChecksPermission`, `Rules/RoleCode` | Validación, con el permiso revisado antes |

## Dónde está en el código

- [`backend/app/Modules/Users/`](../../backend/app/Modules/Users/)
- Correos: [`account-notice.blade.php`](../../backend/resources/views/mail/account-notice.blade.php) y [`account-welcome.blade.php`](../../backend/resources/views/mail/account-welcome.blade.php)
- Pruebas: [`tests/Feature/Users/`](../../backend/tests/Feature/Users/) y [`tests/Unit/Users/`](../../backend/tests/Unit/Users/)
- Tablas: [roles y permisos](../base-de-datos/diccionario/roles-y-permisos.md), [users](../base-de-datos/diccionario/users.md) y [admin_actions](../base-de-datos/diccionario/admin-actions.md)
- Decisiones: [ADR-0021](../decisiones/0021-permisos-granulares.md), [ADR-0053](../decisiones/0053-administracion-de-usuarios.md), [ADR-0054](../decisiones/0054-matriz-de-permisos-no-destructiva.md) y [ADR-0055](../decisiones/0055-edicion-de-roles.md)
