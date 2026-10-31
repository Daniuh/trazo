---
title: "ADR-0003: Frontend modular por funcionalidad con capas"
description: "Frontend modular por funcionalidad con capas"
audience: tecnico
area: decisiones
order: 13
---

# ADR-0003: Frontend modular por funcionalidad con capas

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
El frontend debe mantener la misma separación entre negocio y presentación que el backend, escalar con nuevas pantallas y permitir cambiar la librería de interfaz con el menor impacto posible.

## Alternativas evaluadas
- Estructura por tipo de archivo (`views/`, `components/`, `stores/`, `services/`).
- Feature-Sliced Design estricto.
- Módulos por funcionalidad, con capas domain, application, infrastructure y presentation.

## Decisión
Módulos por funcionalidad (`auth`, `incidents`, `dashboard`, `admin`, `system`), cada uno con sus capas. Se completan con:

- `app/`: arranque, router y guards.
- `shared/`: cliente HTTP, componentes base y utilidades.
- `design/`: tokens y tema.

Las vistas nunca llaman a la API. Pasan por la capa de aplicación, que usa repositorios con mappers entre la API y el dominio. Las rutas se cargan de forma diferida por módulo.

## Alternativas descartadas
- **Por tipo de archivo:** una funcionalidad queda repartida en muchas carpetas, y las vistas terminan acopladas a la API.
- **Feature-Sliced Design:** su número de capas es desproporcionado para el tamaño del proyecto y eleva la curva de entrada.

## Consecuencias
- Un cambio en el contrato de la API se resuelve en el mapper correspondiente.
- La librería de interfaz queda confinada a la capa de presentación.
