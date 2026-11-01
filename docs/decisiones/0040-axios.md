---
title: "ADR-0040: Axios como base del cliente HTTP"
description: "Axios como base del cliente HTTP"
audience: tecnico
area: decisiones
tags: [frontend]
order: 50
---

# ADR-0040: Axios como base del cliente HTTP

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
La prueba exige manejo centralizado de peticiones, interceptores y manejo de token. El cliente también debe normalizar errores, aplicar tiempos de espera y reintentar lecturas de forma segura.

## Alternativas evaluadas
- `fetch` nativo con un envoltorio propio.
- Axios.

## Decisión
Axios, envuelto en un único cliente (`shared/http`). Sus interceptores de petición y respuesta son exactamente el mecanismo que pide la prueba, y su manejo de tiempos de espera, cancelación y errores está probado en producción por años.

## Alternativas descartadas
- **fetch:** no tiene interceptores ni tiempo de espera propios; habría que construir y mantener esa capa, que es justo lo que Axios ya resuelve.

## Consecuencias
- Una dependencia más (pequeña y muy usada), auditada por `npm audit` y Dependabot.
- Las vistas no dependen de Axios: solo lo conoce `shared/http`, así que cambiarlo afectaría un único lugar.

## Dónde está en el código
- [`frontend/src/shared/http/client.ts`](../../frontend/src/shared/http/client.ts)
