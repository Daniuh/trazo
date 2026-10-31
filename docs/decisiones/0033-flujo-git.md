---
title: "ADR-0033: Flujo de Git con cuatro ramas"
description: "Flujo de Git con cuatro ramas"
audience: tecnico
area: decisiones
order: 43
---

# ADR-0033: Flujo de Git con cuatro ramas

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
El código debe avanzar por etapas de validación claras, con un historial legible y sin atajos.

## Alternativas evaluadas
- Trabajar directamente sobre una rama principal.
- GitFlow completo, con ramas de release y hotfix.
- Cuatro ramas fijas.

## Decisión
- **Ramas de trabajo:** `feature/`, `fix/`, `docs/` y `chore/`, con el área y el tema en el nombre.
- **Promoción del código:**
  1. `dev`: integración y QA.
  2. `stage`: preproducción desplegada.
  3. `main`: producción.
- **Siempre por Pull Request:** sin push directo y sin saltar etapas. Las correcciones urgentes siguen el mismo camino.
- **Fusión:** squash de las ramas de trabajo a `dev`, y merge commit en las promociones.
- **Commits:** Conventional Commits, con el tipo y el ámbito en inglés y la descripción en español.
- **Versiones:** SemVer en `main`, con un CHANGELOG generado por git-cliff.
- **Repositorio:** público en GitHub.

## Alternativas descartadas
- **Rama única:** no tiene etapas de validación.
- **GitFlow completo:** las ramas de release y hotfix suman complejidad. Las ramas `hotfix/*` quedan propuestas como mejora.

## Consecuencias
- Cada funcionalidad queda como un único commit en `dev`.
- Cada versión se puede rastrear hasta sus cambios.
