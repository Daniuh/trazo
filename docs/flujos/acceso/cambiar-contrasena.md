---
title: Cambiar la contraseña
description: Cambio desde Mi cuenta con la contraseña actual, la misma política que la recuperación y el cierre de las demás sesiones.
audience: tecnico
area: flujos
module: auth
order: 5
---

# Cambiar la contraseña

Desde Mi cuenta, la persona cambia su contraseña escribiendo primero la actual. La sesión desde la que hace el cambio sigue abierta; las demás se cierran.

```flujo
carriles: [Persona, Navegador, API, Base de datos, Correo]
pasos:
  - id: abre
    carril: Persona
    tipo: inicio
    texto: Escribe la actual, la nueva y la confirmación
    detalle: En el diálogo "Cambiar contraseña" de Mi cuenta, que muestra los requisitos de la nueva.
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía PUT /auth/password
    va: limite
  - id: limite
    carril: API
    tipo: decision
    texto: ¿Menos de 5 intentos en el minuto?
    detalle: 'Límite `password-change`: 5 por minuto por persona, porque se puede probar la contraseña actual.'
    si: politica
    no: e429
  - id: e429
    carril: API
    tipo: error
    texto: Responde 429
  - id: politica
    carril: API
    tipo: decision
    texto: ¿La nueva cumple la política?
    detalle: La misma regla de la recuperación (`NewPassword`). Aquí también rechaza el nombre de la persona, además del correo.
    si: actual
    no: e422a
  - id: e422a
    carril: API
    tipo: error
    texto: Responde 422 por campo
  - id: actual
    carril: Base de datos
    tipo: decision
    texto: ¿La actual es correcta?
    si: distinta
    no: e422b
  - id: e422b
    carril: Base de datos
    tipo: error
    texto: Responde 422
  - id: distinta
    carril: API
    tipo: decision
    texto: ¿Es distinta de la actual?
    si: guarda
    no: e422c
  - id: e422c
    carril: API
    tipo: error
    texto: Responde 422
  - id: guarda
    carril: Base de datos
    texto: Guarda la nueva y cierra las demás sesiones
    detalle: 'Con Argon2id. Avisa por el canal personal: las pantallas abiertas en otros equipos se cierran al instante; esta sigue abierta.'
    va: aviso
  - id: aviso
    carril: Correo
    texto: Aviso de contraseña cambiada
    va: listo
  - id: listo
    carril: Navegador
    tipo: fin
    texto: Sigue en Mi cuenta con el aviso
    detalle: '"Tu contraseña cambió. Cerramos tus sesiones en otros equipos."'
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `TOO_MANY_REQUESTS` | 429 | Más de 5 intentos en un minuto | Hiciste demasiados intentos seguidos. Espera {wait} y vuelve a intentarlo. |
| `CURRENT_PASSWORD_INVALID` | 422 | La contraseña actual no coincide | La contraseña actual no es correcta. |
| `PASSWORD_SAME_AS_CURRENT` | 422 | La nueva es igual a la actual | La contraseña nueva debe ser distinta de la actual. |
| `PASSWORD_BREACHED`, `PASSWORD_REPETITIVE`, `PASSWORD_SEQUENTIAL`, `PASSWORD_PERSONAL` | 422 | La nueva no cumple la política | Los mismos mensajes de [Recuperar la contraseña](recuperar-contrasena.md). |

## Dónde está en el código

- [`ChangePassword`](../../../backend/app/Modules/Auth/Application/Password/ChangePassword.php): comprobación, cambio, cierre de sesiones y aviso.
- [`ChangePasswordRequest`](../../../backend/app/Modules/Auth/Http/ChangePasswordRequest.php) y [`NewPassword`](../../../backend/app/Modules/Auth/Http/Rules/NewPassword.php): la política.
- [`ChangePasswordDialog`](../../../frontend/src/modules/auth/presentation/account/ChangePasswordDialog.vue) y [`NewPasswordFields`](../../../frontend/src/modules/auth/presentation/password/NewPasswordFields.vue).
- Explicación del módulo: [Módulo Auth](../../backend/auth.md).
