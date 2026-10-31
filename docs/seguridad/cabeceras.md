---
title: Cabeceras de seguridad, CSP y CORS
description: Qué cabeceras envía el gateway en cada respuesta, la política de seguridad de contenido en local y en producción, HSTS y por qué CORS está cerrado.
audience: tecnico
area: seguridad
order: 3
---

# Cabeceras de seguridad, CSP y CORS

Todas las respuestas pasan por el gateway (Nginx), que agrega las cabeceras de seguridad. Así la SPA, la API y el tiempo real comparten la misma política ([ADR-0060](../decisiones/0060-pasada-de-calidad.md)).

## Cabeceras

| Cabecera | Valor | Para qué |
|---|---|---|
| `Content-Security-Policy` | Ver abajo | Solo se ejecuta y se carga lo del propio origen |
| `Strict-Transport-Security` | `max-age=63072000; includeSubDomains` | Solo producción y por HTTPS: el navegador no vuelve a intentar HTTP |
| `X-Content-Type-Options` | `nosniff` | El navegador no adivina el tipo de un archivo |
| `X-Frame-Options` | `DENY` | Nadie muestra la app dentro de otra página (lo mismo que `frame-ancestors 'none'`, para navegadores viejos) |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Las rutas internas no se filtran a otros sitios |
| `Permissions-Policy` | `camera=(), microphone=(), geolocation=(), payment=()` | La app no pide nada de eso |
| `Cross-Origin-Opener-Policy` | `same-origin` | Otra pestaña no puede manejar la de Trazo |

## Política de seguridad de contenido (CSP)

```
default-src 'self'; script-src 'self'; style-src <según el entorno>; img-src 'self' data:; font-src 'self';
connect-src 'self'; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; manifest-src 'self'
```

- **Scripts:** solo los archivos del propio origen. Sin scripts en línea ni `eval`, en local ni en producción. Un XSS que lograra inyectar HTML no podría ejecutar nada.
- **Estilos:**
  - Producción: `'self'`. El build los entrega en archivos `.css`.
  - Local: `'self' 'unsafe-inline'`. Vite inyecta los estilos con JavaScript para la recarga en caliente.

  La diferencia vive en la variable `$csp_style_src` de `upstream.dev.conf` y `upstream.prod.conf`.
- **`connect-src 'self'`** cubre la API y el websocket del tiempo real (`/ws`), que van por el mismo origen.
- **`img-src data:`** queda abierto para los íconos internos de Quasar.
- **Comprobada sin violaciones** en las dos formas. En el build de producción servido con la CSP estricta, el recorrido de pantallas (incluidos el editor del dashboard y el QR del MFA) y 9 E2E reales no dejaron ninguna violación. En local, la E2E de accesibilidad revisa cada pantalla y falla si la consola reporta una.

## HSTS

En producción, el TLS termina en el proxy delante del gateway, que indica `X-Forwarded-Proto: https`. Con ese valor el gateway envía HSTS; en HTTP no lo envía. En local no hay HSTS: sería HTTP. `daniuh.dev` ya exige HTTPS por estar en la lista de precarga de `.dev`.

## CORS

Cerrado (`config/cors.php` sin rutas): ninguna respuesta lleva `Access-Control-Allow-Origin`, así que ningún otro sitio puede leer la API desde un navegador. La SPA no lo necesita, porque vive en el mismo origen. La configuración por defecto de Laravel enviaba `*`; ZAP lo marcó y una prueba (`CorsTest`) evita que vuelva.

## Dónde está en el código

- [`docker/nginx/snippets/security-headers.conf`](../../docker/nginx/snippets/security-headers.conf)
- [`docker/nginx/upstream.dev.conf`](../../docker/nginx/upstream.dev.conf) y [`upstream.prod.conf`](../../docker/nginx/upstream.prod.conf)
- [`backend/config/cors.php`](../../backend/config/cors.php) y [`tests/Feature/Shared/CorsTest.php`](../../backend/tests/Feature/Shared/CorsTest.php)
- Revisión en E2E: [`e2e/accessibility.spec.ts`](../../frontend/e2e/accessibility.spec.ts)
