---
title: Crear una persona
description: Alta de una cuenta con su rol, sin que nadie le defina la contraseña, con la jerarquía de roles y la reautenticación cuando el rol es sensible.
audience: tecnico
area: flujos
module: users
order: 1
---

# Crear una persona

Quien administra (`users.create`) crea la cuenta con nombres, apellidos, correo y rol. Nadie le define la contraseña: la cuenta nace con una aleatoria y la persona recibe un correo de bienvenida que la lleva a crear la suya con "¿Olvidaste tu contraseña?". Solo se puede dar un rol por debajo del propio nivel.

```flujo
carriles: [Administración, Navegador, API, Base de datos, Correo, Persona nueva]
pasos:
  - id: llena
    carril: Administración
    tipo: inicio
    texto: Llena nombres, apellidos, correo y rol
    detalle: 'En `/administracion/usuarios/nuevo`. Solo se ofrecen los roles que puede dar (`GET /roles`, `assignable`).'
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía POST /users con Idempotency-Key
    va: permiso
  - id: permiso
    carril: API
    tipo: decision
    texto: ¿Tiene users.create?
    detalle: El permiso se revisa antes de validar los campos, para que nadie sin permiso pueda averiguar qué correos existen.
    si: valida
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403
  - id: valida
    carril: API
    tipo: decision
    texto: ¿Datos válidos y correo libre?
    detalle: 'Nombres y apellidos de hasta 80 caracteres cada uno; correo normalizado (minúsculas y sin espacios), válido y no registrado.'
    si: rol
    no: e422
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422 por campo
  - id: rol
    carril: API
    tipo: decision
    texto: ¿El rol está por debajo de su nivel?
    detalle: 'Niveles como dato (`roles.level`): Solicitante 20, Agente 40, Supervisor 60, Administrador 80, Super admin 100. El Super admin puede dar cualquiera.'
    si: sensible
    no: e422r
  - id: e422r
    carril: API
    tipo: error
    texto: Responde 422 rol no asignable
  - id: sensible
    carril: API
    tipo: decision
    texto: ¿El rol trae permisos sensibles?
    detalle: 'Permisos de los módulos de usuarios, roles o sistema. Si los trae, pide el código de la aplicación de quien administra, como en "Cambiar rol o permisos".'
    si: codigo
    no: guarda
  - id: codigo
    carril: Administración
    texto: Confirma con su código
    va: guarda
  - id: guarda
    carril: Base de datos
    texto: Crea la cuenta con contraseña aleatoria
    detalle: 'En la misma transacción, `admin_actions` (`user_created`) con nombre, correo y rol. `users.name` es una columna calculada de nombres + apellidos.'
    va: bienvenida
  - id: bienvenida
    carril: Correo
    texto: Envía el correo de bienvenida
    detalle: Por la cola, cifrado mientras espera. Explica cómo entrar y lleva a `/recuperar`.
    va: crea
  - id: crea
    carril: Persona nueva
    texto: Crea su contraseña con el código del correo
    detalle: El mismo flujo de "Recuperar la contraseña".
    va: entra
  - id: entra
    carril: Persona nueva
    tipo: fin
    texto: Ingresa y configura su MFA
    detalle: Con el ingreso de gracia de su rol.
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `ALREADY_TAKEN` (campo `email`) | 422 | El correo ya está registrado | Este valor ya está en uso. Prueba con otro. |
| `ROLE_NOT_ASSIGNABLE` | 422 | El rol es de su nivel o superior | No puedes dar ese rol: solo puedes asignar roles por debajo del tuyo. |
| `REAUTHENTICATION_REQUIRED` | 422 | El rol es sensible y no llegó el código; abre el diálogo del código | Para confirmar este cambio, escribe el código de tu aplicación de autenticación. |
| `FORBIDDEN_ACTION` | 403 | Sin `users.create` | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |

## Dónde está en el código

- [`CreateUser`](../../../backend/app/Modules/Users/Application/CreateUser.php): permiso, rol, reautenticación y bienvenida.
- [`CreateUserRequest`](../../../backend/app/Modules/Users/Http/Requests/CreateUserRequest.php), [`UserRequest`](../../../backend/app/Modules/Users/Http/Requests/UserRequest.php) y [`ChecksPermission`](../../../backend/app/Modules/Users/Http/Requests/ChecksPermission.php).
- [`UserAccess`](../../../backend/app/Modules/Users/Domain/UserAccess.php) (`canGiveRole`).
- [`AccountWelcome`](../../../backend/app/Modules/Users/Infrastructure/Mail/AccountWelcome.php).
- [`useUserCreate`](../../../frontend/src/modules/admin/application/useUserCreate.ts) y [`UserCreatePage`](../../../frontend/src/modules/admin/presentation/UserCreatePage.vue).
- Explicación: [Módulo Users](../../backend/users.md).
