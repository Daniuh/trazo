---
title: "ADR-0030: Dashboard personalizable guardado en el servidor"
description: "Dashboard personalizable guardado en el servidor"
audience: tecnico
area: decisiones
order: 40
---

# ADR-0030: Dashboard personalizable guardado en el servidor

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Cada usuario debe poder organizar su dashboard: mover, cambiar de tamaño, quitar y agregar widgets. Debe encontrarlo igual desde cualquier equipo, y nada de esto puede afectar la seguridad.

## Alternativas evaluadas
- Guardar el diseño en el navegador.
- Guardar el diseño como un JSON libre en el servidor.
- Usar tablas relacionales, con los topes definidos en un catálogo.

## Decisión
- **Tablas:**
  - `dashboard_widgets` es el catálogo: define los tamaños mínimos y máximos, la posición por defecto y el permiso requerido de cada widget.
  - `user_dashboard_widgets` guarda la posición y el tamaño de cada widget para cada usuario.
- **Validación en el backend:** comprueba los topes y los permisos. El diseño guardado solo decide dónde va cada widget, nunca qué datos se ven.
- **Edición:** se activa con un modo "Personalizar", solo en portátil y monitor, sobre una grilla de 12 columnas con `grid-layout-plus`.
- **Tablet y celular:** los widgets se acomodan solos, respetando el orden que eligió el usuario.

## Alternativas descartadas
- **En el navegador:** el diseño se pierde al cambiar de equipo.
- **JSON libre:** no se puede validar ni relacionar con FKs.

## Consecuencias
- La personalización es completa, sin abrir una vía para ver datos no autorizados.
