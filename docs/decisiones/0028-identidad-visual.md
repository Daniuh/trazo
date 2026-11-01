---
title: "ADR-0028: Identidad visual de Trazo"
description: "Identidad visual de Trazo"
audience: tecnico
area: decisiones
order: 38
---

# ADR-0028: Identidad visual de Trazo

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
El producto necesita una identidad propia, sobria y profesional, vinculada a la de Psicol.

## Alternativas evaluadas
- Nombres: Trazo, Trazol, Psitrazo, Atiende, Ciclo y Resolvia.
- Tres variantes de isotipo para la "o".

## Decisión
- **Nombre:** **Trazo**, por trazabilidad.
- **Logo:** la "o" se reemplaza por un aro de tres trazos gruesos con puntas redondeadas y pequeños espacios entre ellos, que representan el ciclo de vida del incidente. El mismo aro funciona como ícono y como favicon.
- **Paleta:** tomada de la "o" del logo de Psicol.
  - Azul `#03A8D5`, verde `#86BB24` y naranja `#FF9829`, con texto `#161616`.
  - Ninguno de los tres alcanza contraste AA con texto blanco, así que los botones y los textos usan tonos oscurecidos de la misma escala.
  - Un rojo armonizado se reserva para errores y para la prioridad crítica.
- **Tipografía:** Inter, como única familia y autoalojada.
- **Íconos:** propios, en SVG, sobre una grilla de 24, con trazo de 1,5 px, puntas redondeadas y sin relleno.
- **Pantalla de carga:** el nombre con el aro girando.
  - Solo en cargas de pantalla completa.
  - Respeta la preferencia de reducir movimiento.
  - No aparece en cargas muy breves.

## Alternativas descartadas
- **Trazol y Psitrazo:** suenan a mezcla forzada. "Trazo" es más claro, y la "o" ya conserva el vínculo con Psicol.
- **Variantes B y C del aro:** la de trazo fino se pierde a 16 px, y el aro continuo resulta más rígido.

## Consecuencias
- La identidad se apoya en tokens, así que cualquier ajuste se hace en un solo lugar.
