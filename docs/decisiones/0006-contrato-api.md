---
title: "ADR-0006: Contrato de la API: REST versionada, respuestas y errores"
description: "Contrato de la API: REST versionada, respuestas y errores"
audience: tecnico
area: decisiones
order: 16
---

# ADR-0006: Contrato de la API: REST versionada, respuestas y errores

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
El front y cualquier integración futura necesitan un contrato estable, predecible y seguro, con errores que permitan mostrar mensajes claros sin exponer detalles técnicos.

## Alternativas evaluadas
- Respuestas y errores con formato libre por endpoint.
- JSON:API completo.
- REST con un formato propio uniforme y errores en el estándar RFC 9457 (*Problem Details*).

## Decisión
- **Base de la API:** REST en `/api/v1`, con respuestas formadas por `data` y `meta`.
- **Errores:** en RFC 9457. Cada caso lleva su propio `code` y un `trace_id`, y los errores por campo traen su `code` y sus `params`.
- **Mensajes al usuario:** el backend entrega códigos, y el frontend los traduce a mensajes claros desde un catálogo único.
- **Identificadores y formatos:**
  - ULID públicos.
  - Catálogos referenciados por `code`.
  - Fechas ISO 8601 en UTC.
  - `X-Request-Id` en cada respuesta.
- **Acciones permitidas:** cada recurso incluye `actions`, con lo que el usuario puede hacer sobre él.

## Alternativas descartadas
- **Formato libre:** cada endpoint termina siendo distinto, y el front tiene que manejar cada caso por separado.
- **JSON:API completo:** es más verboso de lo necesario, y su ecosistema en Laravel suma complejidad sin un beneficio claro.

## Consecuencias
- El front maneja los errores de toda la aplicación en un solo lugar.
- Los textos visibles para el usuario viven en un solo lugar y quedan listos para traducirse.
- La referencia OpenAPI se genera desde el código con Scramble.
