---
title: 'Sesión: vencimiento, inactividad y sin conexión'
description: Qué pasa al abrir o recargar Trazo y en cada petición, cuando la sesión vence, se queda sin uso, desactivan la cuenta o el servidor no responde.
audience: tecnico
area: flujos
module: auth
order: 6
---

# Sesión: vencimiento, inactividad y sin conexión

Cada vez que se abre o se recarga Trazo, el navegador comprueba la sesión con la API antes de mostrar una pantalla. Solo la API puede decir que la sesión terminó. Una falla de red, un 502/504 del gateway o el modo mantenimiento no cierran la sesión: llevan a una pantalla de espera con "Reintentar".

```flujo
carriles: [Persona, Navegador, API, Base de datos]
pasos:
  - id: abre
    carril: Persona
    tipo: inicio
    texto: Abre o recarga una página de Trazo
    va: token
  - id: token
    carril: Navegador
    tipo: decision
    texto: ¿Hay un token guardado?
    detalle: El token de la sesión vive en memoria y en `sessionStorage` (se borra al cerrar la pestaña). El de un ingreso a medias nunca se guarda.
    si: me
    no: login
  - id: login
    carril: Navegador
    tipo: fin
    texto: Va a /ingresar con ?volver=
  - id: me
    carril: Navegador
    texto: Pide GET /auth/me
    va: responde
  - id: responde
    carril: Navegador
    tipo: decision
    texto: ¿Respondió la API?
    detalle: Sin red, con 502 o 504 sin cuerpo de la API (`SERVER_UNREACHABLE`) o en mantenimiento (503), la sesión se conserva.
    si: valida
    no: sinconexion
  - id: sinconexion
    carril: Navegador
    tipo: error
    texto: /sin-conexion con Reintentar
  - id: valida
    carril: API
    tipo: decision
    texto: ¿Token vigente, usado hace menos de 1 h y cuenta activa?
    detalle: 'Vence a las 8 h (`SESSION_MAX_MINUTES`) y se cierra tras 1 h sin uso (`SESSION_IDLE_MINUTES`) o si la cuenta está desactivada. Un token a medias solo pasa en las rutas del segundo paso.'
    si: uso
    no: e401
  - id: e401
    carril: API
    tipo: error
    texto: Responde 401 UNAUTHENTICATED
    va: borra
  - id: borra
    carril: Navegador
    tipo: fin
    texto: Borra el token y va al login con aviso
    detalle: El cliente HTTP solo cierra la sesión ante el código `UNAUTHENTICATED`, no ante cualquier 401 (un código MFA incorrecto también es 401 y no debe sacar a nadie). Guarda la página actual en `?volver=`.
  - id: uso
    carril: Base de datos
    texto: Anota el uso del token
    detalle: Sanctum actualiza `last_used_at`; de ahí se mide la hora sin uso. Cada día se borran los tokens vencidos hace más de 24 h.
    va: muestra
  - id: muestra
    carril: Navegador
    tipo: fin
    texto: Muestra la pantalla pedida
    detalle: Con el menú y los botones según los permisos que trae `/auth/me`.
```

La misma comprobación del token ocurre en cada petición mientras la persona usa Trazo: si una respuesta llega con `UNAUTHENTICATED`, el navegador hace lo del paso 7. Además, por el canal personal de tiempo real, una desactivación o un cierre de sesiones desde otro lugar llega al instante: la pantalla se cierra con aviso sin esperar a la siguiente petición (ver [Módulo Auth](../../backend/auth.md) y [Tiempo real](../../operacion/tiempo-real.md)).

## Qué ve la persona

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `UNAUTHENTICATED` | 401 | La sesión venció, quedó sin uso 1 h, la cerraron o desactivaron la cuenta | Tu sesión terminó. Inicia sesión de nuevo para continuar. |
| `NETWORK_ERROR` | — | El navegador no pudo conectarse | No pudimos conectarnos. Revisa tu conexión a internet y vuelve a intentarlo. |
| `SERVER_UNREACHABLE` | 502 / 504 | Respondió el gateway, no la API | El servidor de Trazo no está respondiendo en este momento. Vuelve a intentarlo en unos minutos; si sigue igual, avisa a soporte. |
| `SERVICE_UNAVAILABLE` | 503 | Trazo está en mantenimiento | Trazo está en mantenimiento. Vuelve a intentarlo en unos minutos. |

En la pantalla sin conexión se lee además "Tu sesión sigue abierta: al reintentar vuelves a donde estabas."

## Dónde está en el código

- [`AuthServiceProvider`](../../../backend/app/Modules/Auth/Infrastructure/AuthServiceProvider.php): la regla de cada token (`authenticateAccessTokensUsing`).
- [`SanctumSessionTokens`](../../../backend/app/Modules/Auth/Infrastructure/SanctumSessionTokens.php) y [`config/sanctum.php`](../../../backend/config/sanctum.php): emisión y vencimiento; limpieza diaria en [`routes/console.php`](../../../backend/routes/console.php).
- [`authGuard`](../../../frontend/src/modules/auth/application/authGuard.ts) y [`useSessionStore`](../../../frontend/src/modules/auth/application/useSessionStore.ts) (`restore`): comprobación al navegar.
- [`apiError.ts`](../../../frontend/src/shared/http/apiError.ts): códigos del navegador para red, gateway y mantenimiento.
- [`ConnectionErrorPage`](../../../frontend/src/modules/auth/presentation/ConnectionErrorPage.vue): la pantalla con "Reintentar".
