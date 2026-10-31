---
title: "ADR-0023: Acciones de sistema dentro del alcance de la aplicación"
description: "Acciones de sistema dentro del alcance de la aplicación"
audience: tecnico
area: decisiones
order: 33
---

# ADR-0023: Acciones de sistema dentro del alcance de la aplicación

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
El super admin necesita resolver desde la interfaz problemas operativos comunes, como workers detenidos, una caché desactualizada o jobs fallidos.

## Alternativas evaluadas
- Dar a la aplicación acceso al socket de Docker para reiniciar contenedores.
- Acciones que la aplicación ejecuta dentro de su propio alcance.

## Decisión
**Acciones disponibles:**
- Reiniciar workers.
- Reiniciar el servidor de tiempo real.
- Reintentar jobs.
- Limpiar caché.
- Activar o desactivar el modo mantenimiento.
- Consultar el panel de salud.

**Protecciones:**
- Permiso validado en el backend.
- Reautenticación reciente.
- Confirmación explícita.
- Límite de frecuencia.
- Registro de cada ejecución.

Reiniciar la base de datos o los contenedores es una operación de infraestructura y queda fuera de la aplicación.

## Alternativas descartadas
- **Acceso a `docker.sock`:** equivale a dar control total del servidor a la aplicación. Si la aplicación se compromete, se compromete todo el servidor.

## Consecuencias
- Los problemas operativos más frecuentes se resuelven sin acceso al servidor y sin abrir un riesgo crítico.
