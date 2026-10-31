---
title: Idempotencia
description: Cómo evita la API que un doble clic o un reintento de red creen registros duplicados.
audience: tecnico
area: api
order: 4
---

# Idempotencia

Las rutas de creación aceptan la cabecera `Idempotency-Key`. Si el mismo pedido llega dos veces con la misma clave, por un doble clic o porque la red reintentó, la API devuelve la respuesta original **sin crear nada nuevo**.

| Situación | Respuesta |
|---|---|
| Primera vez con la clave | Se procesa normalmente y, si sale bien, se guarda la respuesta durante 24 horas |
| Misma clave y mismos datos | La respuesta original, con la cabecera `Idempotent-Replayed: true` |
| Misma clave y **otros** datos | 409 `IDEMPOTENCY_KEY_REUSED` |
| Dos peticiones simultáneas con la misma clave | La segunda recibe 409 `IDEMPOTENCY_IN_PROGRESS` |
| Clave con formato inválido | 422 `INVALID_IDEMPOTENCY_KEY` (letras, números y guiones, de 8 a 64 caracteres) |
| Sin cabecera | Se procesa normalmente, sin protección |

## Detalles

- **La clave es por persona y por ruta:** dos usuarios que, por casualidad, usen la misma clave nunca chocan.
- **Solo se recuerdan los éxitos.** Si el pedido falla (por ejemplo, un error de validación), se puede corregir y reintentar con la misma clave.
- **Los datos se comparan por huella** (SHA-256 del cuerpo). El cuerpo completo no se guarda dos veces.
- **El front genera una clave nueva por cada intento de creación** y la reutiliza solo si reintenta ese mismo envío.

Hoy aplica a `POST /incidents`.

## Dónde está en el código

- [`EnsureIdempotency.php`](../../backend/app/Modules/Shared/Http/Middleware/EnsureIdempotency.php)
- Pruebas: [`tests/Feature/Incidents/CreateIncidentTest.php`](../../backend/tests/Feature/Incidents/CreateIncidentTest.php)
- Decisión: [ADR-0037](../decisiones/0037-idempotencia.md)
