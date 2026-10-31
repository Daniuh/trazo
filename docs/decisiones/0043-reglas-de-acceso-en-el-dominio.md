---
title: "ADR-0043: Reglas de acceso a incidentes en una sola clase de dominio"
description: "Reglas de acceso a incidentes en una sola clase de dominio"
audience: tecnico
area: decisiones
order: 53
---

# ADR-0043: Reglas de acceso a incidentes en una sola clase de dominio

- **Estado:** Aceptada
- **Fecha:** 2026-10-22

## Contexto
Quién puede hacer qué con un incidente depende de varias cosas a la vez: los permisos granulares (`own`, `assigned`, `unassigned`, `any`), quién lo creó, quién lo atiende, el estado (inicial o final) y si está eliminado.

Además, la API debe decirle al front qué acciones están permitidas (`actions`), y esa respuesta tiene que coincidir siempre con lo que la API acepta después. Al editar se sumaron reglas aprobadas:

- El solicitante edita solo mientras el incidente está Abierto.
- En los estados finales nadie edita.

## Alternativas evaluadas
1. **Policies de Laravel** con la lógica dentro, y `actions` calculado aparte en el recurso.
2. **Policies para el permiso y validaciones de estado dispersas** en cada caso de uso.
3. **Una clase de dominio** (`IncidentAccess`) que responde, para cada acción, si se puede y, si no, por qué (`Denial`). Los casos de uso y el campo `actions` la consultan.

## Decisión
**Alternativa 3.**

- **`IncidentAccess::denial()`** devuelve `null` o un motivo:
  - No puede verlo → 404.
  - Su rol no lo permite → 403.
  - Estado final → 409 `INCIDENT_FINALIZED`.
  - Ya lo están atendiendo → 409 `INCIDENT_EDIT_LOCKED`.
  - Ya está asignado → 409 `INCIDENT_ALREADY_ASSIGNED`.
- **`IncidentGate`**, en la capa de aplicación, carga el incidente y aplica la regla antes de cada caso de uso.
- **`actions`** se arma con la misma clase. Así, lo que el front muestra y lo que la API permite no pueden diferir.

## Alternativas descartadas
- **Policies con la lógica:** atan las reglas al framework. Además, `actions` tendría que repetir cada regla, y cada repetición es una oportunidad de que el botón aparezca pero la API lo rechace.
- **Reglas dispersas:** cada caso de uso decidiría a su manera. Distinguir "no tienes permiso" de "ya está cerrado" requeriría duplicar condiciones.

## Consecuencias
- Las reglas se prueban como lógica pura, sin base de datos ni HTTP: una prueba unitaria recorre la matriz completa.
- **Cada rechazo tiene un motivo específico,** así que el front muestra un mensaje claro y no un "no tienes permiso" genérico.
- **Cambiar una regla es tocar una sola clase.**
- No se usan las Policies de Laravel para incidentes. Si otro módulo las necesita, puede adaptar esta clase.
