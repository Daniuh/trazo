---
title: "ADR-0022: Logs del sistema: solo eventos relevantes, agrupados por huella"
description: "Logs del sistema: solo eventos relevantes, agrupados por huella"
audience: tecnico
area: decisiones
order: 32
---

# ADR-0022: Logs del sistema: solo eventos relevantes, agrupados por huella

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
El super admin necesita ver las fallas reales y los riesgos de seguridad sin perderse entre eventos normales.

## Alternativas evaluadas
- Registrar todo.
- Registrar solo los eventos relevantes, agrupados.

## Decisión
**Se registra:**
- Fallas invisibles: errores no controlados, jobs fallidos y colas acumuladas.
- Problemas de infraestructura.
- Eventos de seguridad: bloqueos de cuenta, fuerza bruta y accesos denegados repetidos.
- Cambios críticos: roles, usuarios y acciones de sistema.

**No se registra:** tokens expirados, errores de validación, 404 normales ni intentos de login aislados.

**Cómo se guarda:**
- Los eventos iguales se agrupan por huella, con un contador, la primera y la última vez que ocurrieron, la severidad y el estado.
- Tablas `system_events` (con el catálogo `system_event_types`) y `admin_actions`.

## Alternativas descartadas
- **Registrar todo:** el ruido esconde lo importante y llena el almacenamiento.

## Consecuencias
- Todo lo que aparece en la vista de logs merece atención.

## Cómo se construyó
Los detalles (qué se registra, agrupación en una sola sentencia, eventos sin datos sensibles y fin del ruido de las reglas de negocio en el log) están en el [ADR-0059](0059-sistema-en-la-practica.md).
