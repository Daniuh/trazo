---
title: "ADR-0087: Cuentas por rol creadas desde el entorno del servidor"
description: "Dos juegos de cuentas (evaluación y propias), una por rol, creados también en producción con contraseñas que solo viven en el entorno"
audience: tecnico
area: decisiones
order: 97
---

# ADR-0087: Cuentas por rol creadas desde el entorno del servidor

- **Estado:** Aceptada.
- **Fecha:** 2026-11-01

## Contexto
Quienes evalúan Trazo van a entrar al sitio desplegado. Necesitan una cuenta por rol, entregada como correo y contraseña. Su verificación en dos pasos tiene que configurarse en su propio teléfono, así que las cuentas no pueden traerla hecha. El responsable del sitio también necesita cuentas propias de cada rol, de respaldo.

Además, en producción no se creaba ninguna cuenta: las de demostración (`*@trazo.test`, con una contraseña publicada en el README) se omiten a propósito. El sitio desplegado habría arrancado sin nadie que pudiera entrar.

## Alternativas evaluadas
- Crear las cuentas a mano después de desplegar, desde la base o con `tinker`.
- Un comando de consola para crear el primer Super admin, y el resto desde la pantalla de administración.
- Un seeder que crea juegos de cuentas por rol a partir de variables del entorno.

## Decisión
`AccountSetSeeder`, dentro de la siembra normal (corre en cada arranque y también en producción), crea dos juegos:

- **Evaluación** (`EVALUATION_EMAIL_PATTERN`, `EVALUATION_PASSWORD`) y **propias** (`OWNER_EMAIL_PATTERN`, `OWNER_PASSWORD`, `OWNER_LABEL`).
- Una cuenta por rol. El correo sale del patrón con `{rol}`. La contraseña es la misma en el juego y pasa por la regla de la API (`NewPassword`).
- Sin verificación en dos pasos: el primer ingreso muestra el QR, con la gracia de un ingreso de la política aprobada.
- Solo crea las que faltan y nunca pisa cambios posteriores.
- Un juego con una variable vacía no se crea. Uno mal configurado se avisa en el registro de `init` y se omite, sin detener el arranque.

## Alternativas descartadas
- **A mano:** no queda registrado cómo se hizo, y repetir el despliegue en otro servidor depende de la memoria de alguien.
- **Comando para el primer Super admin y el resto desde la pantalla:** las cuentas nuevas desde la administración nacen con una contraseña al azar y un correo para crearla. Sirve para personas reales, no para entregar "correo y contraseña" de cinco roles.
- **Contraseñas en el código o en el README:** el repositorio es público. Cualquiera podría entrar al sitio desplegado antes que quien evalúa y quedarse con su verificación.

## Consecuencias
- El sitio desplegado nace con cuentas con las que entrar, sin pasos manuales.
- Las contraseñas solo existen en el entorno del servidor. Gitleaks y el `.env.example` sin valores cuidan que no lleguen al repositorio.
- **Quien entra primero configura la verificación.** Por eso las credenciales de evaluación se entregan poco antes de usarlas, y el Super admin puede restablecer una verificación si hace falta.
- La contraseña es igual para las cinco cuentas de un juego. Es aceptable para un periodo de evaluación; para personas reales se usa la pantalla de administración.

## Dónde está en el código
- [`AccountSetSeeder.php`](../../backend/database/seeders/AccountSetSeeder.php) y [`config/trazo.php`](../../backend/config/trazo.php).
- [`AccountSetSeederTest.php`](../../backend/tests/Feature/Database/AccountSetSeederTest.php).
- Operación: [cuentas de evaluación y propias](../operacion/cuentas.md).
