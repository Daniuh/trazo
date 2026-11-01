---
title: "ADR-0002: CQRS ligero: escrituras por dominio, lecturas optimizadas"
description: "CQRS ligero: escrituras por dominio, lecturas optimizadas"
audience: tecnico
area: decisiones
order: 12
---

# ADR-0002: CQRS ligero: escrituras por dominio, lecturas optimizadas

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
El sistema debe operar con grandes volúmenes sin degradarse. Las pantallas de mayor carga (listado con filtros y dashboard) solo leen datos, mientras que las escrituras deben respetar reglas estrictas, como las transiciones de estado.

## Alternativas evaluadas
- Que todo pase por el mismo camino de dominio y repositorios.
- CQRS completo, con modelos de lectura separados y sincronización por eventos.
- CQRS ligero: la misma base de datos, pero caminos distintos para leer y para escribir.

## Decisión
CQRS ligero:

- **Escrituras:** pasan por casos de uso que aplican las reglas del dominio.
- **Lecturas pesadas:** usan *query services* dedicados, con consultas optimizadas, eager loading e índices pensados para ellas.

## Alternativas descartadas
- **Un solo camino:** obliga a que las lecturas atraviesen capas que no aportan nada al consultar, y dificulta optimizar.
- **CQRS completo:** suma una segunda base o proyección, consistencia eventual y más infraestructura, sin una necesidad real en este alcance.

## Consecuencias
- El listado y el dashboard se optimizan sin tocar las reglas de negocio.
- Si el volumen crece, el lado de lectura puede apuntar a una réplica solo con configuración.
