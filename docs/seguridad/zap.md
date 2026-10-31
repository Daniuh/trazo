---
title: Escaneo con OWASP ZAP
description: Cómo correr el escaneo pasivo de la SPA y el activo de la API con ZAP en el Docker local, y qué encontró.
audience: tecnico
area: seguridad
order: 4
---

# Escaneo con OWASP ZAP

ZAP corre como perfil opcional del Docker local (`zap`): `docker compose up -d` no lo levanta. Solo se usa contra el stack local; los reportes quedan en `zap-reports/`, fuera de Git ([ADR-0060](../decisiones/0060-pasada-de-calidad.md)).

## Escaneo pasivo de la SPA

Recorre la app y revisa cabeceras, cookies y respuestas sin atacar nada:

```bash
docker compose -p trazo --profile zap run --rm zap zap-baseline.py -t http://gateway:8080 -r baseline.html -J baseline.json -I -j
```

> En Git Bash para Windows, antepón `MSYS_NO_PATHCONV=1` a los comandos de ZAP: si no, convierte las rutas `/zap/...` en rutas de Windows.

## Escaneo activo de la API

Ataca cada ruta del [contrato OpenAPI](../api/openapi.json) con la sesión de una cuenta de prueba. **Cambia datos de demostración:** conviene respaldar la base antes y restaurarla al terminar.

1. Subir el límite general de la API, para que ZAP no reciba solo respuestas 429:

   ```bash
   API_RATE_LIMIT=100000 docker compose -p trazo up -d --wait app
   ```

2. Emitir un token de prueba. Solo existe en desarrollo y no pasa por el MFA:

   ```bash
   docker compose -p trazo exec app php artisan trazo:dev-token superadmin@trazo.test
   ```

3. Correr el plan, con el token en `TRAZO_TOKEN`:

   ```bash
   API_RATE_LIMIT=100000 docker compose -p trazo --profile zap run --rm --no-deps -e TRAZO_TOKEN=<token> zap zap.sh -cmd -autorun /zap/config/api-scan.yaml
   ```

4. Volver el límite a 120 (`docker compose -p trazo up -d app`), revocar el token y restaurar los datos.

**Qué hace el plan** (`docker/zap/api-scan.yaml`):

- Importa el contrato y agrega el token a cada petición.
- Excluye cerrar sesión, que revocaría el token del escaneo, y el contrato, que tarda varios segundos en generarse.
- Ataca durante un máximo de 45 minutos y escribe `api.html` y `api.json` desde dentro de ZAP.

Se usa el plan de automatización porque el script `zap-api-scan.py` se cortaba al bajar las alertas.

## Resultados (25 de octubre de 2026)

| Escaneo | Alerta | Riesgo | Qué se hizo |
|---|---|---|---|
| Pasivo | Non-Storable Content | Informativa | Correcto: las respuestas de la API no se guardan en caché |
| Pasivo y activo | ZAP is Out of Date | Baja | Aviso de la herramienta: la versión está fijada (2.16.1) a propósito |
| Activo | Cross-Domain Misconfiguration (`Access-Control-Allow-Origin: *`) | Media | **Corregido:** CORS cerrado ([cabeceras](cabeceras.md#cors)) |
| Activo | CSP: style-src unsafe-inline | Media | Solo en local, por Vite. En producción la CSP es estricta y está comprobada |
| Activo | Authentication Request Identified | Informativa | ZAP reconoce el login y la recuperación; nada que corregir |
| Activo | Sensitive Information in URL (`filter[user]`) | Informativa | Falso positivo: es el ULID de la persona del filtro, no un dato secreto |

**Ninguna alerta alta:** sin inyección SQL, XSS, inclusión de rutas, ejecución remota ni fallas de control de acceso detectadas en 163 rutas con sus variantes. El escaneo activo llegó al tope de 45 minutos; una corrida más larga podría probar más variantes por parámetro.

## Dónde está en el código

- Perfil: [`docker-compose.override.yml`](../../docker-compose.override.yml) (servicio `zap`)
- Plan: [`docker/zap/api-scan.yaml`](../../docker/zap/api-scan.yaml)
- Token de prueba: [`DevTokenCommand`](../../backend/app/Modules/Auth/Infrastructure/Console/DevTokenCommand.php)
- Límite configurable: [`AppServiceProvider`](../../backend/app/Providers/AppServiceProvider.php) (`trazo.api_rate_limit`)
