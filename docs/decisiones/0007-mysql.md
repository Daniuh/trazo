---
title: "ADR-0007: MySQL 8.4 LTS como base de datos"
description: "MySQL 8.4 LTS como base de datos"
audience: tecnico
area: decisiones
order: 17
---

# ADR-0007: MySQL 8.4 LTS como base de datos

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
La prueba permite MySQL o MariaDB. Se requieren FULLTEXT, restricciones CHECK, JSON y buen soporte en la nube.

## Alternativas evaluadas
- MySQL 8.4 LTS.
- MariaDB 11 LTS.

## Decisión
MySQL 8.4 LTS, por tres razones:
- Es una versión con soporte extendido.
- Es el motor más disponible en servicios administrados (AWS RDS, Google Cloud SQL, Azure).
- Es el motor de referencia de Laravel.

Se configura con el charset `utf8mb4_0900_ai_ci`, que no distingue tildes ni mayúsculas en las búsquedas, y con fechas en UTC.

## Alternativas descartadas
- **MariaDB:** cumple los requisitos, pero ofrece menos opciones administradas en la nube, y en sus funciones recientes es cada vez menos compatible con MySQL.

## Consecuencias
- Las pruebas corren contra MySQL real, no contra SQLite, para no esconder diferencias de comportamiento.
