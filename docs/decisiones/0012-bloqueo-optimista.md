---
title: "ADR-0012: Bloqueo optimista en incidentes"
description: "Bloqueo optimista en incidentes"
audience: tecnico
area: decisiones
tags: [datos]
order: 22
---

# ADR-0012: Bloqueo optimista en incidentes

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Varias personas pueden editar el mismo incidente al mismo tiempo. Sin control, el último en guardar pisa los cambios de los demás sin que nadie lo note.

## Alternativas evaluadas
- Que gane el último en guardar.
- Bloqueo pesimista (marcar el registro como "en edición").
- Bloqueo optimista con versión.

## Decisión
Bloqueo optimista con la columna `lock_version`:
- Cada edición envía la versión que tenía el usuario.
- Si la versión cambió mientras tanto, la API responde 409 con `INCIDENT_MODIFIED_BY_OTHER`.
- El front ofrece recargar antes de continuar.

## Alternativas descartadas
- **Gana el último:** pierde información en silencio.
- **Bloqueo pesimista:** deja registros bloqueados si alguien abandona la edición, y exige mecanismos para liberarlos.

## Consecuencias
- Ningún cambio se pierde sin aviso, y nadie queda bloqueado.
