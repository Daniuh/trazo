---
title: "ADR-0041: El CI prueba el backend dentro del stack de Docker"
description: "El CI prueba el backend dentro del stack de Docker y audita dependencias según su alcance"
audience: tecnico
area: decisiones
order: 51
---

# ADR-0041: El CI prueba el backend dentro del stack de Docker

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
[ADR-0034](0034-ci-cd.md) define un CI en GitHub Actions con trabajos de back, front, e2e y seguridad. Falta decidir cómo corre cada uno. El backend necesita MySQL con parámetros concretos (charset, zona horaria y FULLTEXT desde 3 letras) y la base `trazo_test`. Además, la prueba pide que el proyecto se levante con Docker.

## Alternativas evaluadas
1. **PHP instalado en el runner** (`setup-php`) y MySQL como *service container* de GitHub Actions.
2. **PHP instalado en el runner** y MySQL lanzado a mano con `docker run` y los mismos parámetros.
3. **El stack de Docker completo** (`docker compose up --wait`) y las pruebas dentro del contenedor `app`, igual que en local.

## Decisión
**Alternativa 3.** El trabajo de backend levanta el mismo stack que cualquier desarrollador y corre `composer check` dentro del contenedor. También comprueba que el gateway responda.

**Complementos:**
- **Imágenes de producción.** Un trabajo aparte construye las imágenes `prod`.
- **Auditoría de dependencias según su alcance:**
  - Bloquean el CI las del backend, las del frontend que llegan al navegador y las de la raíz.
  - Las herramientas de desarrollo del frontend se reportan como aviso, sin bloquear.
- **Gitleaks en el pre-commit.** Corre sobre los cambios preparados, además del escaneo del historial en el CI.
- **Seguridad del flujo:**
  - Acciones fijadas por hash.
  - Permisos de solo lectura.
  - Checkout sin credenciales persistidas.

## Alternativas descartadas
- **Service container de MySQL:** GitHub Actions no permite pasar parámetros de arranque a un *service container*, así que MySQL correría sin la configuración del proyecto. Las pruebas pasarían en un entorno distinto al real.
- **`docker run` a mano:** repite en el flujo los parámetros de MySQL que ya están en el compose, y cualquier cambio tendría que hacerse en dos lugares. Además, no prueba que el stack arranque.
- **Bloquear por cualquier aviso de npm:** un aviso sin corrección en una herramienta de lint frenaría todas las fusiones, sin que el producto publicado corra ningún riesgo.
- **Ignorar los avisos de desarrollo:** quedarían ocultos. Por eso se muestran en cada corrida.

## Consecuencias
- Lo que pasa en el CI pasa en local con los mismos comandos, y al revés.
- Cada PR verifica, además, que `docker compose up` deja la aplicación funcionando.
- El trabajo de backend tarda más (construye las imágenes), pero corre en paralelo con el resto.
- El hook de secretos necesita Docker encendido, igual que el de formato del backend.
