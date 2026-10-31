---
title: "ADR-0036: Exportación en CSV"
description: "Exportación en CSV"
audience: tecnico
area: decisiones
order: 46
---

# ADR-0036: Exportación en CSV

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Se menciona como deseable la exportación a Excel o CSV.

## Alternativas evaluadas
- Archivo `.xlsx` nativo, generado con una librería.
- CSV en streaming.

## Decisión
CSV generado en streaming:
- Codificado en UTF-8 con BOM, para que Excel muestre bien las tildes.
- Con los mismos filtros del listado.
- Con límite de frecuencia.

## Alternativas descartadas
- **`.xlsx`:** agrega una dependencia y consume más memoria con volúmenes grandes. Queda propuesto como mejora.

## Consecuencias
- Exportar grandes volúmenes no carga la memoria del servidor.
