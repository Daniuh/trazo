---
title: "ADR-0089: Incidentes de muestra en el sitio de evaluación"
description: "En producción los incidentes de demostración se cargan solo con una variable explícita, repartidos entre las cuentas por rol"
audience: tecnico
area: decisiones
tags: [operacion]
order: 99
---

# ADR-0089: Incidentes de muestra en el sitio de evaluación

- **Estado:** Aceptada.
- **Fecha:** 2026-11-01

## Contexto
El seeder de volumen (600 incidentes con su historial) nunca corría en producción. El sitio desplegado es, por ahora, el que se usa para evaluar Trazo: quien entra con una [cuenta por rol](0087-cuentas-por-rol-desde-el-entorno.md) encontraría el resumen, el listado y el historial vacíos, y la prueba pide al menos 500 incidentes.

## Alternativas evaluadas
1. **Una variable explícita** (`SEED_INCIDENTS_IN_PRODUCTION`, apagada por defecto) que permite el seeder en producción.
2. **Arrancar vacío** y que quien evalúe cree sus propios incidentes.
3. **Cargar los datos a mano** una vez, con un comando en el servidor.
4. **Desplegar con `APP_ENV` distinto de producción.**

## Decisión
Se elige la **1**. Con la variable activa, el mismo `IncidentVolumeSeeder` de siempre reparte los incidentes entre las personas activas del servidor (las cuentas por rol): quien atiende sale de quien tiene `incidents.status.assigned` y quien asigna, de `incidents.assign`. Si no hay ninguna de las dos, el seeder avisa y no carga nada, en vez de fallar el arranque.

## Alternativas descartadas
- **2:** la primera impresión sería una pantalla vacía y no se vería el volumen que pide la prueba.
- **3:** un paso manual que se olvida y no queda en el código ni se prueba.
- **4:** apagaría a la vez las protecciones de producción (secretos obligatorios, sin usuarios de demostración, sin carga perezosa permitida).

## Consecuencias
- Por defecto nada cambia: producción sigue sin datos de demostración.
- El seeder es idempotente: completa hasta el volumen pedido y no vuelve a cargar en cada arranque. Los incidentes que creen las personas cuentan dentro de ese volumen.
- Cuando el sitio deje de ser de evaluación, se apaga la variable; los incidentes ya cargados se quedan hasta que alguien los elimine.
- Las pruebas de producción del seeder corren `db:seed --force`, como el arranque del contenedor: sin `--force`, en producción no corre nada y la prueba no comprobaría lo que dice.
