---
title: API
description: Convenciones de la API REST y acceso a su referencia.
audience: tecnico
area: api
order: 1
---

# API

Explica las convenciones comunes a todos los endpoints (formato de respuestas, errores, paginación, filtros y ordenamiento) y enlaza a la referencia OpenAPI generada desde el código.

## Referencia OpenAPI

El contrato de la API se genera desde el código con Scramble (rutas, parámetros, validaciones y respuestas):

- **En la app:** `GET /api/v1/docs` lo entrega en JSON, solo con sesión y el permiso `system.health.view` (el Super admin). Las rutas públicas de Scramble están apagadas y su interfaz visual no se usa: carga scripts de una CDN y la [CSP](../seguridad/cabeceras.md) no lo permite.
- **En esta documentación:** [`openapi.json`](openapi.json) es una copia exportada. Se abre con cualquier visor (Postman, Insomnia, Swagger Editor) y es la entrada del [escaneo activo de ZAP](../seguridad/zap.md).

Para regenerar la copia después de cambiar la API:

```bash
docker compose -p trazo exec app php artisan scramble:export --path=storage/app/openapi.json
```

Después, mover `backend/storage/app/openapi.json` a `docs/api/openapi.json`. El contrato usa la sesión por token Bearer y describe los errores en el formato de [Errores](errores.md).

## Dónde está en el código

- [`ApiDocsController`](../../backend/app/Modules/System/Http/ApiDocsController.php) y la configuración del contrato en [`SystemServiceProvider`](../../backend/app/Modules/System/Infrastructure/SystemServiceProvider.php)
