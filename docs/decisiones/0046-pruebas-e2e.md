---
title: "ADR-0046: Pruebas E2E en las cuatro vistas, con axe bloqueante y sesión por la API"
description: "Pruebas E2E en las cuatro vistas, con axe bloqueante y sesión por la API"
audience: tecnico
area: decisiones
order: 56
---

# ADR-0046: Pruebas E2E en las cuatro vistas, con axe bloqueante y sesión por la API

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
[ADR-0031](0031-estrategia-de-pruebas.md) definió Playwright con axe-core para los flujos críticos. Al construirlo quedaban tres puntos abiertos:

- Cómo cubrir las cuatro vistas.
- Qué hacer con los hallazgos de accesibilidad.
- Cómo abrir sesión sin chocar con el límite real del login (5 intentos por minuto por correo e IP).

## Alternativas evaluadas
Vistas:
1. Una sola vista (portátil), con revisiones manuales de las demás.
2. **Un proyecto de Playwright por vista,** con los mismos flujos.

Accesibilidad:
1. Reportar los hallazgos sin bloquear.
2. **Fallar la prueba ante cualquier hallazgo** de WCAG 2.1 A y AA.

Sesión:
1. Llenar el formulario de login en cada prueba.
2. Desactivar el límite de intentos cuando corren las pruebas.
3. **Abrir la sesión por la API una vez por usuario** y dejar el token donde lo guarda la app (`sessionStorage`), antes de cargar la página.

## Decisión
- **Un proyecto por vista:** celular (375 × 812, táctil), tablet (768 × 1024, táctil), portátil (1280 × 800) y monitor (1680 × 1000).
- **axe bloqueante.**
- **Sesión por la API,** guardada por usuario dentro de cada proceso de pruebas. El formulario de login se prueba aparte, con sus propios casos.
- Una prueba a la vez (`workers: 1`), porque todas comparten la base de datos y el límite de intentos.

## Alternativas descartadas
- **Una sola vista:** la mitad de la interfaz (panel de filtros, tarjetas, menú compacto) cambia por vista. Lo comprobó la primera corrida: el menú compacto de tablet tenía enlaces sin nombre accesible, algo que en portátil no se ve.
- **axe solo informativo:** los avisos que no bloquean se acumulan y nadie los atiende.
- **Login por formulario en cada prueba:** más lento y, con cuatro vistas, supera el límite de 5 intentos por minuto.
- **Desactivar el límite en las pruebas:** sería un interruptor de seguridad que podría quedar activo por error fuera de las pruebas.

## Consecuencias
- Cada flujo nuevo corre cuatro veces; la suite completa tarda unos minutos.
- En Docker sobre Windows cada petición a Laravel tarda un par de segundos (el código se lee desde una carpeta montada), así que los tiempos de espera son holgados. En el CI, sobre Linux, la suite va más rápido.
- La primera corrida encontró y corrigió dos problemas de accesibilidad: el nombre del enlace del logo no coincidía con su texto visible, y los enlaces del menú compacto no tenían nombre.
