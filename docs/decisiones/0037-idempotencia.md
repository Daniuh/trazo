---
title: "ADR-0037: Idempotency-Key en la creación de incidentes"
description: "Idempotency-Key en la creación de incidentes"
audience: tecnico
area: decisiones
order: 47
---

# ADR-0037: Idempotency-Key en la creación de incidentes

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Un doble clic o un reintento de red pueden crear incidentes duplicados.

## Alternativas evaluadas
- Bloquear el botón solo en el front.
- Una clave de idempotencia validada en el backend.

## Decisión
- **Front:** envía una cabecera `Idempotency-Key` única por cada intento de creación, y bloquea el botón mientras la petición está en curso.
- **Backend:** guarda temporalmente la respuesta asociada a esa clave. Si vuelve a recibir la misma clave, devuelve la respuesta original sin crear otro registro.

## Alternativas descartadas
- **Solo el front:** no protege de los reintentos de red ni de clientes distintos al navegador.

## Consecuencias
- Crear un incidente es seguro ante reintentos, como ocurre en las APIs de pago.
