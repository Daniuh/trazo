---
title: "ADR-0049: La sesión solo se cierra cuando la API la rechaza"
description: "La sesión solo se cierra cuando la API la rechaza"
audience: tecnico
area: decisiones
order: 59
---

# ADR-0049: La sesión solo se cierra cuando la API la rechaza

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Al recargar la página, la guarda de rutas pide el usuario a `GET /auth/me`. Si esa consulta fallaba por cualquier motivo, la app borraba el token y llevaba al login.

Con una red lenta o caída, o con el sistema en mantenimiento, la persona perdía una sesión que seguía vigente y tenía que volver a ingresar sin razón. Ya era la regla del cliente HTTP: solo el código `UNAUTHENTICATED` cierra la sesión ([ADR-0042](0042-sesion-y-bloqueo.md)).

## Alternativas evaluadas
1. Mantener el comportamiento: cualquier falla lleva al login.
2. Ante una falla distinta de `UNAUTHENTICATED`, dejar pasar a la ruta sin usuario cargado.
3. **Ante una falla distinta de `UNAUTHENTICATED`, conservar el token y mostrar una pantalla de sin conexión** con el mensaje del catálogo y "Reintentar".

## Decisión
Alternativa 3. `restore()` devuelve tres resultados (`signed-in`, `signed-out` y `unreachable`). Con `unreachable`, la guarda lleva a `/sin-conexion?volver=…`, y "Reintentar" vuelve a esa ruta, lo que dispara de nuevo la comprobación.

## Alternativas descartadas
- **Llevar siempre al login:** cierra sesiones válidas y confunde ("¿se me cerró la sesión?"). Además, el mensaje del login no explica que el problema es la conexión.
- **Dejar pasar sin usuario:** el menú, los permisos y las acciones dependen del usuario; la pantalla quedaría a medias.

## Consecuencias
- Un mantenimiento (503) o una red caída muestran su propio mensaje claro, no el del login.
- El token sigue en `sessionStorage` hasta que la API lo rechace o venza solo.
