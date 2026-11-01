---
title: "ADR-0060: Pasada de calidad: CSP estricta, CORS cerrado, contrato OpenAPI, ZAP y cobertura exigida"
description: "Pasada de calidad: CSP estricta, CORS cerrado, contrato OpenAPI, ZAP y cobertura exigida"
audience: tecnico
area: decisiones
tags: [operacion]
order: 70
---

# ADR-0060: Pasada de calidad: CSP estricta, CORS cerrado, contrato OpenAPI, ZAP y cobertura exigida

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Con las funcionalidades terminadas, faltaba una revisión de conjunto: escaneo de seguridad con ZAP, accesibilidad de todas las pantallas, rendimiento real y cobertura medida. Además, varias piezas aprobadas seguían sin construir:

- La política de seguridad de contenido (CSP).
- HSTS para producción.
- El contrato OpenAPI con Scramble.
- El perfil de ZAP.
- La medición de cobertura en el back.

También quedaban pendientes de fases anteriores: la guía visual desactualizada, tres flujos sin E2E y la suite E2E, que no se podía repetir seguido por el límite de la recuperación de contraseña.

## Alternativas evaluadas
CSP:
1. **Estricta en producción (sin nada en línea) y, en local, solo con estilos en línea para Vite.**
2. La misma CSP relajada en los dos entornos.
3. Solo documentarla.

Contrato OpenAPI:
1. **Solo el JSON en `/api/v1/docs`, con el permiso de sistema, y una copia exportada en `docs/api/openapi.json`.**
2. La interfaz visual de Scramble, abriendo la CSP en esa ruta.
3. Copiar la interfaz visual dentro del proyecto para servirla sin CDN.

Escaneo activo de ZAP:
1. **Plan de automatización de ZAP, que escribe los reportes desde dentro.**
2. El script `zap-api-scan.py`.

Repetir la E2E sin esperar al límite de 15 minutos:
1. **Contadores de seguridad en su propia base de Redis y un comando de desarrollo que vacía solo esa base.**
2. `cache:clear` antes de la suite.
3. Cuentas distintas en cada corrida.

Cobertura del front:
1. **El 90 % para dominio y aplicación de cada módulo; las pantallas, con la E2E.**
2. El 80 % general también en Vitest, con pruebas de componentes para cada pantalla.

## Decisión
- **CSP en el gateway:**
  - `default-src 'self'`, `script-src 'self'` (sin `unsafe-inline` ni `eval`).
  - `connect-src 'self'`, que incluye el websocket de `/ws`.
  - `img-src 'self' data:`, `frame-ancestors 'none'`, `object-src 'none'`, `base-uri` y `form-action` en `'self'`.
  - Solo cambia `style-src`: `'self'` en producción y `'self' 'unsafe-inline'` en local, porque Vite inyecta los estilos con JavaScript. La diferencia vive en `upstream.dev.conf` y `upstream.prod.conf`, que ya separaban los entornos.
  - Comprobada sin violaciones en desarrollo y sobre el build con la CSP de producción: recorrido de pantallas y 9 E2E reales (dashboard con su editor, incidentes y MFA con el QR).
- **HSTS** de dos años con subdominios, solo cuando la petición llega por HTTPS (según `X-Forwarded-Proto` del proxy de TLS) y solo en producción.
- **CORS cerrado** (`config/cors.php` sin rutas). ZAP marcó `Access-Control-Allow-Origin: *`, que es la configuración por defecto de Laravel. Con token Bearer no se aprovecha, pero la SPA vive en el mismo origen y no lo necesita.
- **Contrato OpenAPI:** Scramble lo genera desde el código. Sus rutas públicas quedan apagadas; `GET /api/v1/docs` lo entrega solo con `system.health.view`. La copia exportada sirve para el escaneo de ZAP y para la futura web de documentación.
- **ZAP como perfil `zap`** del override local, solo a mano:
  - Escaneo pasivo de la SPA.
  - Escaneo activo de la API con el plan de automatización (`docker/zap/api-scan.yaml`), con un token de prueba (`trazo:dev-token`, solo en desarrollo) y el límite general subido con `API_RATE_LIMIT`.

  El script `zap-api-scan.py` se descartó: se corta al bajar las alertas por la API interna de ZAP.
- **Contadores de seguridad separados:** límites de frecuencia y bloqueos del login en la base 2 de Redis (`cache.limiter = limits`). `trazo:reset-limits`, solo en desarrollo, la vacía, y la E2E lo corre al empezar (`globalSetup`). La caché de datos y el modo mantenimiento nunca se tocan.
- **Cobertura exigida en el CI:**
  - Back: `composer coverage` con PCOV (solo en la imagen de desarrollo) y `tests/coverage-check.php`. Exige 90 % en las capas Domain y Application y 80 % en general.
  - Front: umbral de Vitest de 90 % para `modules/*/{domain,application}`.
- **Accesibilidad:**
  - Una E2E recorre todas las rutas en las cuatro vistas con axe y sin violaciones de CSP.
  - Otra recorre con el teclado los flujos principales.
  - Se agregó "Saltar al contenido" (WCAG 2.4.1).
  - Se corrigió el foco que no volvía al botón después de cerrar el diálogo del código (WCAG 2.4.3), en el panel de sistema y en la ficha de una persona.
- **Pruebas de operación** (`@operacion`, `npm run test:e2e:ops`): reiniciar workers y el tiempo real contra los contenedores reales. Fuera de la suite normal, porque reinician servicios compartidos.

## Alternativas descartadas
- **CSP relajada en los dos entornos:** abriría la puerta a estilos inyectados en producción solo por comodidad del desarrollo.
- **Solo documentarla:** era una mejora aprobada y la app ya era compatible.
- **Interfaz de Scramble con la CSP abierta:** cargaría scripts de una CDN externa (unpkg) en una ruta de la API. Es un riesgo de cadena de suministro para algo que se puede leer con cualquier visor.
- **Copiar la interfaz dentro del proyecto:** suma unos 2 MB de JavaScript ajeno para mantener. Queda como mejora propuesta.
- **`cache:clear` antes de la suite:** borra también la caché de datos y la marca de mantenimiento.
- **Cuentas distintas por corrida:** cada una necesita su MFA configurado y deja datos sueltos.
- **80 % general en Vitest:** obligaría a escribir pruebas de componentes que repiten lo que la E2E ya prueba en las cuatro vistas, con el navegador real.

## Consecuencias
- **Escaneo activo:** ninguna alerta alta; la única media real (CORS) quedó cerrada. La alerta de la CSP en local es esperada y la de producción está comprobada.
- **Cobertura:**
  - Back: 97,7 % en dominio y aplicación y 97,0 % en general.
  - Front: 96,4 % en dominio y aplicación.
  - Al medirla apareció el endpoint `GET /permissions` sin pruebas, y se cubrió.
- **Rendimiento:**
  - Carga inicial de unos 175 kB comprimidos y CLS 0 en todas las pantallas.
  - LCP de 1,1 s en portátil y de 1,6 a 3,0 s en un celular lento simulado.
  - El slow query log no tiene ninguna consulta de la app de más de 1 s.
  - La lista de eventos del sistema bajó de 97 a 35 ms con 50.000 eventos.
- **La prueba de operación encontró un error real:** "Reiniciar" el tiempo real respondía 500, porque Reverb solo registra sus comandos en la consola. Ahora se escribe directo la señal que Reverb revisa (`laravel:reverb:restart`).
- **Al revisar los jobs fallidos** se vio que los workers se deben reiniciar después de desplegar código nuevo; si no, procesan jobs que no conocen. Quedó anotado en la operación del panel.
- El front ya no tiene una meta de cobertura "general" en Vitest: la cobertura de las pantallas es la E2E.

## Dónde está en el código
- Cabeceras: [`docker/nginx/snippets/security-headers.conf`](../../docker/nginx/snippets/security-headers.conf), [`upstream.dev.conf`](../../docker/nginx/upstream.dev.conf), [`upstream.prod.conf`](../../docker/nginx/upstream.prod.conf) y [`config/cors.php`](../../backend/config/cors.php).
- OpenAPI: [`ApiDocsController`](../../backend/app/Modules/System/Http/ApiDocsController.php) y [`docs/api/openapi.json`](../api/openapi.json).
- ZAP: perfil en [`docker-compose.override.yml`](../../docker-compose.override.yml) y plan en [`docker/zap/api-scan.yaml`](../../docker/zap/api-scan.yaml).
- Contadores: [`config/cache.php`](../../backend/config/cache.php), [`ResetLimitsCommand`](../../backend/app/Modules/Shared/Infrastructure/Console/ResetLimitsCommand.php) y [`DevTokenCommand`](../../backend/app/Modules/Auth/Infrastructure/Console/DevTokenCommand.php).
- Cobertura: [`tests/coverage-check.php`](../../backend/tests/coverage-check.php), [`frontend/vite.config.ts`](../../frontend/vite.config.ts) y [`ci.yml`](../../.github/workflows/ci.yml).
- Accesibilidad y operación: [`e2e/accessibility.spec.ts`](../../frontend/e2e/accessibility.spec.ts) y [`e2e/operations.spec.ts`](../../frontend/e2e/operations.spec.ts).
