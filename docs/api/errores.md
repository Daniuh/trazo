---
title: Errores de la API
description: Formato de los errores (RFC 9457) y catálogo de códigos generales y de validación.
audience: tecnico
area: api
order: 3
---

# Errores de la API

Todos los errores salen con `Content-Type: application/problem+json` siguiendo el estándar RFC 9457. El backend entrega **códigos**; el frontend los traduce a mensajes claros desde su catálogo. Nunca se devuelven trazas, consultas SQL ni nombres de clases.

## Formato

```json
{
  "type": "urn:trazo:error:validation-failed",
  "title": "Validation failed",
  "status": 422,
  "code": "VALIDATION_FAILED",
  "errors": {
    "title": [{ "code": "TOO_LONG", "params": { "max": "80" } }],
    "email": [{ "code": "INVALID_EMAIL" }]
  },
  "trace_id": "01JB7Q3W0Z5K8R2M4N6P9T1V3X"
}
```

| Campo | Siempre | Descripción |
|---|---|---|
| `type` | Sí | Identificador del tipo de error |
| `title` | Sí | Descripción corta en inglés, para quien desarrolla |
| `status` | Sí | Estado HTTP |
| `code` | Sí | Código estable que el front traduce |
| `params` | No | Datos para construir el mensaje (p. ej. estados de una transición) |
| `errors` | No | Errores por campo, cada uno con su `code` y sus `params` |
| `trace_id` | Sí | Identificador de la petición; coincide con la cabecera `X-Request-Id` |

Los 429 y los 423 incluyen `Retry-After`, y los 405 incluyen `Allow`.

## Códigos generales

| Código | Estado | Cuándo |
|---|---|---|
| `MALFORMED_REQUEST` | 400 | La petición no se puede interpretar |
| `UNAUTHENTICATED` | 401 | No hay sesión o expiró |
| `FORBIDDEN_ACTION` | 403 | No tiene permiso para esa acción |
| `RESOURCE_NOT_FOUND` | 404 | No existe o no puede verlo |
| `METHOD_NOT_ALLOWED` | 405 | El método HTTP no aplica a esa ruta |
| `VALIDATION_FAILED` | 422 | Uno o más campos no son válidos (ver `errors`) |
| `TOO_MANY_REQUESTS` | 429 | Superó el límite de peticiones |
| `SERVICE_UNAVAILABLE` | 503 | Modo mantenimiento |
| `INTERNAL_ERROR` | 500 | Error inesperado (el detalle queda solo en los logs) |

Los códigos de negocio de cada módulo (por ejemplo `INVALID_STATUS_TRANSITION`) se documentan en su módulo. Los de sesión (`INVALID_CREDENTIALS` y `ACCOUNT_LOCKED`) están en [Módulo Auth](../backend/auth.md#errores), y los de incidentes en [Módulo Incidents](../backend/incidents.md#errores).

## Códigos de validación por campo

| Código | Regla | Parámetros |
|---|---|---|
| `REQUIRED` | Campo obligatorio | — |
| `MUST_BE_TEXT`, `MUST_BE_INTEGER`, `MUST_BE_NUMBER`, `MUST_BE_BOOLEAN`, `MUST_BE_LIST` | Tipo de dato | — |
| `INVALID_EMAIL`, `INVALID_DATE`, `INVALID_IDENTIFIER` | Formato | — |
| `TOO_SHORT` / `TOO_LONG` | Mínimo / máximo | `min` / `max` |
| `OUT_OF_RANGE` | Entre dos valores | `min`, `max` |
| `WRONG_SIZE` | Tamaño exacto | `size` |
| `INVALID_OPTION` | Valor fuera de la lista permitida | — |
| `INVALID_REFERENCE` | Referencia a algo que no existe | — |
| `ALREADY_TAKEN` | Valor que debe ser único | — |
| `CONFIRMATION_MISMATCH` | La confirmación no coincide | — |
| `DATE_TOO_EARLY` / `DATE_TOO_LATE` | Fecha fuera del rango permitido | `date` |
| `NOT_ALLOWED` | Campo que no se puede enviar | — |

Una regla no listada se expone con su nombre en mayúsculas (`ACTIVE_URL`). Las reglas propias implementan `HasErrorCode` para definir su código. Si una regla puede fallar por varios motivos (por ejemplo, `NotWeakPassword`), implementa `HasErrorCodes`: falla con el código como mensaje y declara todos los posibles, y el formateador usa el que corresponda.

## Dónde está en el código

- [`ErrorCode.php`](../../backend/app/Modules/Shared/Http/Errors/ErrorCode.php): códigos generales y su estado.
- [`ProblemDetails.php`](../../backend/app/Modules/Shared/Http/Errors/ProblemDetails.php): construcción de la respuesta.
- [`ValidationErrorFormatter.php`](../../backend/app/Modules/Shared/Http/Errors/ValidationErrorFormatter.php): códigos de validación.
- [`ApiExceptionRenderer.php`](../../backend/app/Modules/Shared/Http/Errors/ApiExceptionRenderer.php): conversión de cualquier excepción.
- Decisión: [ADR-0006](../decisiones/0006-contrato-api.md).
