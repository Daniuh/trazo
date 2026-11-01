---
title: "ADR-0035: Documentación en Markdown, con ADR y trazabilidad al código"
description: "Documentación en Markdown, con ADR y trazabilidad al código"
audience: tecnico
area: decisiones
order: 45
---

# ADR-0035: Documentación en Markdown, con ADR y trazabilidad al código

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
La documentación debe servir al equipo de soporte, a los directivos y al equipo técnico. Además, será la fuente de una futura web estática.

## Alternativas evaluadas
- Una wiki externa.
- Documentación dentro del repositorio.
- Un Markdown escrito a mano por cada función.

## Decisión
La documentación vive en `docs/`, dentro del repositorio:
- **Organización:** por público y, en la parte técnica, por área y por módulo.
- **Formato:** cada página lleva frontmatter con metadatos, y los diagramas se escriben en Mermaid.
- **Decisiones:** se registran como ADR, con las alternativas evaluadas y los motivos.
- **Trazabilidad:** cada página técnica incluye una sección "Dónde está en el código", y cada archivo de código indica qué documento lo explica.
- **Funciones:** su referencia se escribe en el código (PHPDoc y TSDoc) y se genera de forma automática.
- **Enlaces:** el CI verifica los enlaces internos.
- **Alcance:** la documentación forma parte de cada tarea.

## Alternativas descartadas
- **Wiki externa:** se desincroniza del código y no se versiona junto con él.
- **Markdown por función:** repite lo que ya dice el código y se desactualiza rápido.

## Consecuencias
- La documentación cambia en el mismo commit que el código que describe.
