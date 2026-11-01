---
title: "ADR-0067: Pantallas precompiladas en desarrollo"
description: "Vite compila al arrancar el layout y cada pantalla (server.warmup), para que la primera visita no tarde ni haga fallar la E2E"
audience: tecnico
area: decisiones
order: 77
---

# ADR-0067: Pantallas precompiladas en desarrollo

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
En desarrollo, Vite compila cada archivo la primera vez que el navegador lo pide. Con Docker sobre Windows y los archivos montados, la primera visita a una pantalla podía tardar más de 20 segundos: el `.vue`, sus componentes y todo el SCSS de los tokens. Las pruebas E2E que llegaban primero a una pantalla fallaban a ratos por tiempo, sin que hubiera un error real.

## Alternativas evaluadas
- `server.warmup` de Vite con el layout y las páginas de cada módulo.
- Subir los tiempos de espera de Playwright.
- Correr la E2E contra el build de producción.

## Decisión
`server.warmup.clientFiles` con `main.ts`, `AppShell.vue` y todas las `*Page.vue` de los módulos. Al arrancar, Vite compila de una vez el código y el SCSS de cada pantalla. La primera visita ya no espera: medido en local, las páginas responden en milisegundos después del arranque.

Es una opción estándar de Vite, solo afecta al desarrollo, y una pantalla nueva entra sola si su nombre termina en `Page.vue`.

## Alternativas descartadas
- **Más tiempo de espera en Playwright:** esconde la lentitud en vez de quitarla y alarga cada corrida. Una prueba que de verdad se cuelga tardaría más en fallar.
- **E2E contra el build:** sería más rápida, pero dejaría de probar el entorno que usa quien desarrolla. Además, exige reconstruir en cada cambio. Queda como opción para el CI si alguna vez hace falta.

## Consecuencias
- El arranque de Vite consume un poco más de CPU durante los primeros segundos.
- La E2E deja de tener fallas intermitentes por la primera compilación.

## Dónde está en el código
- [`frontend/vite.config.ts`](../../frontend/vite.config.ts): `server.warmup`.
