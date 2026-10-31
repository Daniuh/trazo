---
title: "ADR-0020: El backend como única autoridad"
description: "El backend como única autoridad"
audience: tecnico
area: decisiones
order: 30
---

# ADR-0020: El backend como única autoridad

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Cualquier regla que se valide solo en el navegador se puede saltar manipulando las peticiones.

## Alternativas evaluadas
- Duplicar las reglas en el front y en el back.
- Reglas solo en el back, con el front reflejando lo que el back permite.

## Decisión
- **Qué vive en el backend:** las reglas de negocio, los permisos, los cálculos (como "vencido" o la fecha sugerida) y los límites.
- **Acciones permitidas:** cada recurso incluye `actions`, con lo que el usuario puede hacer. El front solo muestra u oculta controles según eso.
- **Validación en el front:** solo valida formularios para dar retroalimentación inmediata.

## Alternativas descartadas
- **Duplicar reglas:** crea dos fuentes de verdad que terminan contradiciéndose, además de código repetido.

## Consecuencias
- Manipular el front no otorga ningún permiso.
- Cambiar una regla es un solo cambio, en un solo lugar.
