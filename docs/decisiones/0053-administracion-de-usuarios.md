---
title: "ADR-0053: Administración de usuarios: jerarquía por nivel, permisos por persona y reautenticación"
description: "Administración de usuarios: jerarquía por nivel, permisos por persona y reautenticación"
audience: tecnico
area: decisiones
order: 63
---

# ADR-0053: Administración de usuarios: jerarquía por nivel, permisos por persona y reautenticación

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
[ADR-0021](0021-permisos-granulares.md) definió roles construidos sobre permisos granulares, con dos reglas: siempre existe un Super admin activo y nadie cambia su propio rol. Al construir la administración de usuarios aparecieron necesidades que esa decisión no cubría:

- Dos personas con el mismo rol pueden necesitar accesos distintos: un permiso de más o uno de menos.
- Hace falta un nivel intermedio: alguien que cree y gestione a las demás personas sin tener el control total. El Super admin queda para restablecer cualquier acceso y crear a esos administradores.
- `users.manage` era un solo permiso para todo: crear, cambiar roles, restablecer accesos. No permitía tener un administrador "completo o no".
- Había que decidir cómo nace la contraseña de una cuenta nueva, cuántos roles puede tener una persona y qué acciones exigen confirmar la identidad de quien administra.

## Alternativas evaluadas
Permisos distintos dentro de un mismo rol:
1. **Excepciones por persona sobre su rol: concedidos (`grant`) y quitados (`deny`).**
2. Solo permisos concedidos, sin poder quitar.
3. Un rol por cada combinación de permisos.

Jerarquía:
1. **Un nivel numérico por rol, guardado como dato (`roles.level`).**
2. Alcances en el permiso (`users.manage.below`).
3. Reglas por nombre de rol en el código.

Contraseña de una cuenta nueva:
1. **Contraseña aleatoria que nadie conoce y correo de bienvenida que lleva a "¿Olvidaste tu contraseña?".**
2. Quien administra define una contraseña temporal.
3. Un enlace de invitación con un código de larga duración.

Roles por persona:
1. **Uno, en la interfaz y en la API, con la tabla N:M conservada.**
2. Varios a la vez.

Confirmar acciones sensibles:
1. **El código actual de la aplicación de autenticación de quien administra.**
2. La contraseña de quien administra.
3. Solo una confirmación en pantalla.

## Decisión
- **Permisos efectivos = los del rol + los concedidos − los quitados.** Si chocan, gana el quitado. Las excepciones viven en `user_permissions`, con quién las dio. `UserPermissions` es la consulta única para la sesión y para las reglas de cada módulo; `PermissionSql` aplica la misma regla como filtro SQL (por ejemplo, para el selector de "asignar a"), y una prueba compara las dos con todos los permisos.
- **Cinco roles con nivel:**
  - Solicitante 20
  - Agente 40
  - Supervisor 60
  - Administrador 80
  - Super admin 100

  Se administra solo hacia abajo: un rol o una persona por debajo del propio nivel. La cima (100) administra a todos, incluso a otro Super admin, nunca a sí misma. Solo el Super admin tiene nivel 100.
- **`users.manage` se dividió en siete permisos.** Son `users.create`, `users.update`, `users.activate`, `users.roles.assign`, `users.permissions.grant`, `users.mfa.reset` y `users.access.reset`.
  - El Administrador tiene los cinco primeros.
  - Restablecer el MFA y el acceso queda para el Super admin, salvo que se lo conceda a una persona.
- **Nadie concede lo que no tiene.** Quitar sí se puede. El Super admin no lleva excepciones: su rol ya trae todo.
- **Cuenta nueva sin contraseña conocida.**
  - La cuenta se crea con una contraseña aleatoria y llega una bienvenida que explica cómo crear la contraseña con el flujo de recuperación ([ADR-0052](0052-recuperacion-y-cambio-de-contrasena.md)).
  - Restablecer el acceso funciona igual: invalida la contraseña, cierra sesiones y avisa cómo crear una nueva.
- **Un rol por persona.** Al cambiarlo se quitan las excepciones que el rol nuevo vuelve innecesarias. (Reemplazado por el [ADR-0069](0069-cambio-de-rol-con-permisos-exactos.md): ahora se borran todas.)
- **Reautenticación con el código de la aplicación** para:
  - Dar o quitar un rol que trae permisos de usuarios, roles o sistema.
  - Tocar esos mismos permisos uno por uno.
  - Restablecer un MFA o un acceso.

  El código no se puede repetir y cinco códigos incorrectos en un minuto pausan la reautenticación. La API responde `REAUTHENTICATION_REQUIRED` y el front abre el diálogo del código: el front no decide qué es sensible.
- **Siempre queda un Super admin activo.** Antes de desactivar o bajar de rol a uno, la transacción bloquea a los Super admin activos (`FOR UPDATE`), así dos cambios al mismo tiempo no los dejan en cero.
- **Registro en `admin_actions`** con quien actuó, el tipo (catálogo `admin_action_types`), la persona afectada, el detalle del cambio, la IP y el navegador. Es la tabla que también usarán las acciones de sistema.
- **Avisos por correo** a la persona afectada: bienvenida, rol, permisos, activación, desactivación, MFA y acceso restablecidos, y el cambio de correo (este va a la dirección anterior).
- Para no revelar qué correos existen, las peticiones de la administración revisan el permiso **antes** de validar los datos.

## Alternativas descartadas
- **Solo concedidos:** no deja quitarle algo a una persona puntual.
- **Un rol por combinación:** con el tiempo serían decenas de roles casi iguales y nadie sabría qué tiene cada quien.
- **Alcances en el permiso:** la jerarquía quedaría escondida en los códigos, y un rol nuevo no tendría dónde ubicarse.
- **Reglas por nombre de rol:** rompe ADR-0021; cada cambio exigiría tocar código.
- **Contraseña temporal:** quien administra conocería una contraseña ajena, y suele viajar por canales inseguros.
- **Enlace de invitación largo:** sería otro secreto de larga duración en un correo; el flujo de recuperación ya resuelve lo mismo con un código de 15 minutos.
- **Varios roles:** los cinco roles ya se acumulan; varios a la vez confunden, y para un acceso distinto están los permisos por persona.
- **Contraseña para reautenticarse:** el código de la aplicación es un factor que un robo de sesión o de contraseña no da.
- **Solo confirmar en pantalla:** no protege si alguien se apoderó de la sesión.

## Consecuencias
- Un acceso especial se da sin crear roles, y queda registrado quién lo dio.
- Quien administra necesita tener el MFA activo para las acciones sensibles; si no, la API responde `REAUTHENTICATION_UNAVAILABLE` y el front lo explica.
- El Super admin queda como la única cuenta capaz de restablecer accesos por defecto. Si pierde el teléfono y sus códigos, otro Super admin puede restablecer su MFA. Por eso conviene tener al menos dos.
- Cualquier consulta que elija personas por permiso debe usar `PermissionSql`, no la tabla de roles.
