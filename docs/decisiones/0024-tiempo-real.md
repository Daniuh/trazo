---
title: "ADR-0024: Tiempo real con Reverb y mensajes sin datos"
description: "Tiempo real con Reverb y mensajes sin datos"
audience: tecnico
area: decisiones
order: 34
---

# ADR-0024: Tiempo real con Reverb y mensajes sin datos

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Los listados y el dashboard deben reflejar los cambios que hacen otros usuarios, sin exponer información a quien no tiene permiso para verla.

## Alternativas evaluadas
- Consultas periódicas (polling).
- Servicios externos de websockets.
- Laravel Reverb.

## Decisión
- **Servidor:** Reverb, en su propio contenedor.
- **Canales:** privados, autorizados según los permisos de cada usuario.
- **Mensajes sin datos:** solo llevan el identificador y el tipo de evento. El front vuelve a consultar la API, que aplica los permisos.
- **Sin saltos en pantalla:** en el listado, los cambios se anuncian con un aviso para actualizar, en lugar de mover filas mientras alguien lee.

## Alternativas descartadas
- **Polling:** carga constante y sin inmediatez.
- **Servicios externos:** dependencia de terceros, con costo y con datos fuera del sistema.

## Consecuencias
- Por el canal de websockets no viaja ningún dato, así que ningún dato puede filtrarse por esa vía.
