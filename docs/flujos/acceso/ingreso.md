---
title: Ingreso con verificación en dos pasos
description: Desde que la persona escribe su correo hasta que entra, con el límite de intentos, el bloqueo progresivo, la contraseña y el código de la aplicación autenticadora.
audience: tecnico
area: flujos
module: auth
order: 1
---

# Ingreso con verificación en dos pasos

El ingreso tiene dos pasos. Primero el correo y la contraseña. Si la cuenta tiene la verificación en dos pasos (MFA) activa, la API entrega un token a medias que solo sirve para enviar el código de la aplicación. Si no la tiene, el ingreso sigue en [Configurar el MFA](configurar-mfa.md), porque todos los roles la exigen tras un ingreso de gracia.

```flujo
carriles: [Persona, Navegador, API, Redis, Base de datos]
pasos:
  - id: escribe
    carril: Persona
    tipo: inicio
    texto: Escribe correo y contraseña
    detalle: En `/ingresar`. Si llegó desde una página interna, la dirección queda en `?volver=` para regresar a ella al final (solo rutas internas, `safeRedirect`).
    va: envia
  - id: envia
    carril: Navegador
    texto: Envía POST /auth/login
    detalle: El navegador solo revisa que los campos no estén vacíos y que el correo tenga forma de correo; las reglas de verdad están en la API.
    va: limite
  - id: limite
    carril: API
    tipo: decision
    texto: ¿Menos de 5 intentos en el minuto?
    detalle: Límite `login`, 5 por minuto por correo + IP. Si se pasa, responde 429 con `Retry-After`.
    si: pausa
    no: e429
  - id: e429
    carril: API
    tipo: error
    texto: Responde 429
  - id: pausa
    carril: Redis
    tipo: decision
    texto: ¿Correo en pausa?
    detalle: Los fallos se cuentan por la huella SHA-256 del correo normalizado, exista o no la cuenta, para no revelar qué correos están registrados.
    si: e423
    no: compara
  - id: e423
    carril: Redis
    tipo: error
    texto: Responde 423
  - id: compara
    carril: Base de datos
    texto: Compara la contraseña con Argon2id
    detalle: Si el correo no existe, compara contra un hash de relleno para que la respuesta tarde lo mismo y no delate nada.
    va: coincide
  - id: coincide
    carril: API
    tipo: decision
    texto: ¿Coincide y la cuenta está activa?
    si: limpia
    no: falla
  - id: falla
    carril: Redis
    tipo: decision
    texto: Suma un fallo. ¿Llegó a 5, 10, 15 o 20+?
    detalle: Al llegar a 5, 10, 15 y 20 fallos seguidos, el correo queda en pausa 1 min, 5 min, 15 min y 1 h; desde ahí, cada fallo vuelve a pausar 1 h. El contador se borra con un ingreso correcto o 24 h después del primer fallo. Solo las pausas quedan en el registro y en los eventos del sistema (`LoginLocked`); un fallo aislado no.
    si: e423b
    no: e401
  - id: e423b
    carril: Redis
    tipo: error
    texto: Pausa y responde 423
  - id: e401
    carril: Redis
    tipo: error
    texto: Responde 401
  - id: limpia
    carril: Redis
    texto: Pone en cero el contador de fallos
    va: siguiente
  - id: siguiente
    carril: API
    tipo: decision
    texto: ¿La cuenta tiene MFA activo?
    detalle: '`MfaGate` decide el siguiente paso: con MFA, verificar el código; sin MFA, configurarlo (sugerido mientras quede el ingreso de gracia, obligatorio después).'
    si: reto
    no: configurar
  - id: configurar
    carril: Navegador
    tipo: fin
    texto: Sigue en Configurar el MFA
    detalle: Respuesta `setup_suggested` (sesión completa y se sugiere configurarlo) o `mfa_setup` (token a medias; debe configurarlo para entrar).
  - id: reto
    carril: Base de datos
    texto: Token a medias de 5 min, solo para el código
    detalle: Token de Sanctum con la habilidad `mfa`. Fuera de las rutas del segundo paso y del cierre de sesión, la API lo rechaza.
    va: pide
  - id: pide
    carril: Navegador
    texto: Pide el código en /ingresar/verificar
    detalle: El token a medias vive solo en memoria, nunca en `sessionStorage`. Si se recarga la página, hay que volver a ingresar.
    va: codigo
  - id: codigo
    carril: Persona
    texto: Escribe el código de su aplicación
    detalle: También puede usar uno de sus 8 códigos de recuperación; usarlo le envía un aviso por correo.
    va: verifica
  - id: verifica
    carril: API
    tipo: decision
    texto: ¿Código válido y sin usar?
    detalle: 'Límite `mfa`: 5 por minuto por persona. Acepta el código de la ventana actual y de las vecinas (±30 s) y no acepta uno ya usado: guarda el último paso de tiempo usado con una actualización condicional.'
    si: sesion
    no: intentos
  - id: intentos
    carril: API
    tipo: decision
    texto: ¿Quinto código fallido en este ingreso?
    detalle: Los fallos se cuentan por ingreso (por token a medias). Al quinto, revoca el token y registra el evento `MfaAttemptsExceeded`.
    si: e401b
    no: e422
  - id: e401b
    carril: API
    tipo: error
    texto: Cierra el ingreso y responde 401
  - id: e422
    carril: API
    tipo: error
    texto: Responde 422 con lo que queda
  - id: sesion
    carril: Base de datos
    texto: Revoca el token a medias y crea la sesión
    detalle: 'Token de sesión con prefijo `trazo_`: dura como máximo 8 h y se cierra tras 1 h sin uso o si desactivan la cuenta. Guarda `last_login_at`.'
    va: entra
  - id: entra
    carril: Navegador
    tipo: fin
    texto: Entra a la página de origen o al inicio
    detalle: Vuelve a `?volver=` si era una ruta interna; si no, al inicio.
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `TOO_MANY_REQUESTS` | 429 | Más de 5 intentos en un minuto con el mismo correo e IP | Hiciste demasiados intentos seguidos. Espera {wait} y vuelve a intentarlo. |
| `ACCOUNT_LOCKED` | 423 | El correo está en pausa, o este fallo llegó a un escalón (5, 10, 15 o 20+) | Por seguridad, pausamos el ingreso con este correo después de varios intentos fallidos. Vuelve a intentarlo en {wait}. |
| `INVALID_CREDENTIALS` | 401 | El correo no existe, la contraseña no coincide o la cuenta está desactivada (no dice cuál) | El correo o la contraseña no son correctos. Revísalos e inténtalo de nuevo. |
| `MFA_CODE_INVALID` | 422 | El código no corresponde; trae `attempts_left` | El código no es correcto. Escribe el que muestra ahora tu aplicación de autenticación. Te quedan N intentos. |
| `MFA_CODE_ALREADY_USED` | 422 | El código ya se usó en esa ventana de 30 s | Ese código ya se usó. Espera a que tu aplicación muestre uno nuevo (cambia cada 30 segundos) y escríbelo. |
| `MFA_RECOVERY_CODE_INVALID` | 422 | El código de recuperación no existe o ya se usó | Ese código de recuperación no es válido o ya se usó. Revisa que lo hayas escrito completo, con sus 10 caracteres. |
| `MFA_TOO_MANY_ATTEMPTS` | 401 | Quinto código fallido en el mismo ingreso | Por seguridad cerramos este ingreso después de varios códigos incorrectos. Vuelve a ingresar con tu correo y contraseña. |
| `MFA_CHALLENGE_EXPIRED` | 401 | Pasaron más de 5 minutos desde la contraseña (la API responde `UNAUTHENTICATED`) | Pasó demasiado tiempo desde que escribiste tu contraseña. Vuelve a ingresar para continuar. |

El mensaje de datos incorrectos es el mismo para un correo que no existe y para una contraseña equivocada: es una excepción aprobada a la regla de errores específicos, para no revelar qué correos están registrados.

## Dónde está en el código

- [`LoginUser`](../../../backend/app/Modules/Auth/Application/LoginUser.php): límite de fallos, contraseña y paso siguiente.
- [`LockoutPolicy`](../../../backend/app/Modules/Auth/Domain/LockoutPolicy.php) y los escalones en [`config/trazo.php`](../../../backend/config/trazo.php).
- [`MfaGate`](../../../backend/app/Modules/Auth/Domain/Mfa/MfaGate.php) y [`LoginStep`](../../../backend/app/Modules/Auth/Domain/Mfa/LoginStep.php): qué sigue después de la contraseña.
- [`VerifyMfa`](../../../backend/app/Modules/Auth/Application/Mfa/VerifyMfa.php) y [`TotpCheck`](../../../backend/app/Modules/Auth/Application/Mfa/TotpCheck.php): segundo paso y protección contra repetición.
- [`AuthServiceProvider`](../../../backend/app/Modules/Auth/Infrastructure/AuthServiceProvider.php): límites `login` y `mfa`, y la regla del token a medias.
- [`useSessionStore`](../../../frontend/src/modules/auth/application/useSessionStore.ts), [`LoginPage`](../../../frontend/src/modules/auth/presentation/LoginPage.vue) y [`MfaVerifyPage`](../../../frontend/src/modules/auth/presentation/mfa/MfaVerifyPage.vue): pantallas y token a medias en memoria.
- Mensajes: [`errors.ts`](../../../frontend/src/modules/auth/errors.ts).
- Explicación del módulo: [Módulo Auth](../../backend/auth.md).
