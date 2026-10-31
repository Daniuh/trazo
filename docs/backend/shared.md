---
title: Módulo Shared
description: Piezas transversales de la API: excepciones de dominio, errores y identificador de petición.
audience: tecnico
area: backend
module: shared
order: 2
---

# Módulo Shared

Reúne lo que usan todos los módulos y no pertenece a ningún dominio en particular.

## Excepciones de dominio

Las reglas de negocio no conocen HTTP: lanzan una excepción de dominio con un **código estable** y, si hace falta, los parámetros que necesita el mensaje.

| Excepción | Cuándo usarla | Respuesta |
|---|---|---|
| `ResourceNotFoundException` | El recurso no existe **o** quien consulta no puede verlo | 404 |
| `ConflictException` | Choca con el estado actual (transición inválida, versión desactualizada) | 409 |
| `RuleViolationException` | Los datos son válidos en forma pero rompen una regla de negocio | 422 |

```php
throw new ConflictException('INVALID_STATUS_TRANSITION', ['from' => 'closed', 'to' => 'open']);
```

Cada módulo define sus propios códigos en su capa de dominio. La traducción a estado HTTP ocurre en un único lugar: el renderizador de errores.

## Errores de la API

Toda excepción que llega a la API, sea de negocio, de validación, del framework o inesperada, pasa por `ApiExceptionRenderer` y sale en formato RFC 9457 sin detalles técnicos. El formato completo y la lista de códigos están en [Errores de la API](../api/errores.md).

## Identificador de petición

Cada petición recibe un identificador que:

- se devuelve en la cabecera `X-Request-Id`,
- aparece como `trace_id` en los errores,
- se agrega al contexto de todos los logs de esa petición.

Así, cuando alguien reporta un problema con su identificador, se encuentra en los logs sin exponer nada técnico. Si el cliente envía su propio `X-Request-Id` se reutiliza, pero solo si tiene un formato seguro (letras, números y guiones, de 8 a 64 caracteres); si no, se genera un ULID nuevo.

## JSON en toda la API

Las peticiones a `/api/*` se tratan siempre como JSON, aunque el cliente no envíe la cabecera `Accept`. Por eso incluso una ruta inexistente responde con un error JSON y no con una página HTML.

## Dónde está en el código

- [`Domain/Exceptions/`](../../backend/app/Modules/Shared/Domain/Exceptions/): `DomainException` y sus tres tipos.
- [`Http/Errors/ApiExceptionRenderer.php`](../../backend/app/Modules/Shared/Http/Errors/ApiExceptionRenderer.php): conversión de excepciones a errores.
- [`Http/Errors/ValidationErrorFormatter.php`](../../backend/app/Modules/Shared/Http/Errors/ValidationErrorFormatter.php): reglas de validación a códigos.
- [`Http/Middleware/AssignRequestId.php`](../../backend/app/Modules/Shared/Http/Middleware/AssignRequestId.php) y [`ForceJsonResponse.php`](../../backend/app/Modules/Shared/Http/Middleware/ForceJsonResponse.php).
- Pruebas: [`tests/Feature/Shared/`](../../backend/tests/Feature/Shared/).
