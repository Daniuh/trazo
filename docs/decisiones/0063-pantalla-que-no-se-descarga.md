---
title: "ADR-0063: Pantalla que no se descarga"
description: "Pantalla que no se descarga: un aviso global con Recargar y un mensaje propio, sin pedir códigos de soporte inexistentes"
audience: tecnico
area: decisiones
tags: [frontend]
order: 73
---

# ADR-0063: Pantalla que no se descarga

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Cada pantalla de la SPA se descarga al abrirla por primera vez (rutas con carga diferida). Si esa descarga falla, la navegación falla. Pasa sobre todo después de publicar una versión nueva: quien tenía Trazo abierto pide archivos de la versión anterior, que ya no existen. También pasa si la conexión se corta justo al cambiar de pantalla.

En la verificación desde cero apareció así: tras ingresar, el login mostró "No pudimos completar la acción. Si se repite, comparte este código con soporte: **sin código**". Había dos problemas:

1. El error no tenía código ni mensaje propio, así que caía en el genérico.
2. El genérico pedía un código de soporte que no existe cuando la falla no llega al servidor.

Además, los enlaces del menú (`RouterLink`) descartan los errores de navegación: al pulsar una opción, simplemente no pasaba nada.

## Alternativas evaluadas
- Un aviso global en `router.onError` con **Recargar**, más un código propio en `toApiError`.
- Recargar la página sola, una vez, al detectar la falla.
- Mantener los archivos de versiones anteriores en el servidor durante un tiempo.
- Solo corregir el mensaje genérico.

## Decisión
- **Código propio:** `isScreenLoadError` reconoce la falla por el mensaje de cada navegador (Chrome, Firefox, Safari y la precarga de CSS de Vite). `toApiError` la convierte en `SCREEN_LOAD_FAILED`, con un mensaje que explica las dos causas posibles y pide recargar.
- **Aviso global:** `installScreenLoadNotice` escucha `router.onError` y muestra un aviso de error persistente con **Recargar**, que abre la pantalla que se buscaba (con su filtro en la URL). Cubre el menú y cualquier navegación.
- **Sin "sin código":** si la falla no trae código de soporte, cualquier mensaje que lo pida se cambia por uno que no lo menciona (`UNKNOWN_ERROR`). El mensaje que sí lo pide pasa a `UNKNOWN_ERROR_TRACED`.

## Alternativas descartadas
- **Recargar sola:** es lo más transparente, pero cambia la pantalla sin que la persona lo pida y puede perder lo que estaba escribiendo. Si la causa es la red, además entra en un ciclo de recargas que hay que cortar con marcas en el almacenamiento. Queda como mejora propuesta, con un límite de una recarga y solo si no hay cambios sin guardar.
- **Conservar versiones anteriores:** depende del despliegue (CDN o volumen compartido entre versiones) y no cubre el corte de red. Se documenta como mejora del despliegue.
- **Solo el mensaje:** el menú seguiría sin hacer nada al pulsarlo.

## Consecuencias
- Tras una publicación, quien tenía Trazo abierto ve un aviso claro y vuelve a la pantalla buscada con un clic.
- En las pantallas que esperan una navegación y capturan el error (login, verificación en dos pasos, recuperación de contraseña), el mensaje puede verse a la vez en la pantalla y en el aviso. Los dos dicen lo mismo, y solo pasa en este caso poco frecuente.
- Ningún mensaje vuelve a pedir un código de soporte que no existe.

## Dónde está en el código
- [`frontend/src/shared/http/apiError.ts`](../../frontend/src/shared/http/apiError.ts): `isScreenLoadError` y `SCREEN_LOAD_FAILED`.
- [`frontend/src/app/router/screenLoadNotice.ts`](../../frontend/src/app/router/screenLoadNotice.ts) y [`frontend/src/app/App.vue`](../../frontend/src/app/App.vue): aviso global.
- [`frontend/src/shared/errors/catalog.ts`](../../frontend/src/shared/errors/catalog.ts) y [`generalMessages.ts`](../../frontend/src/shared/errors/generalMessages.ts): mensajes.
- [`frontend/src/shared/ui/feedback/useFeedback.ts`](../../frontend/src/shared/ui/feedback/useFeedback.ts): `notifyError` con acción.
- Mensajes para soporte: [catálogo de errores](../soporte/catalogo-de-errores.md).
