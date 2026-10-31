---
title: Cuentas de evaluación y propias
description: Cómo nacen en el despliegue las cuentas por rol para quienes evalúan y las del responsable, y cómo entregarlas.
audience: tecnico
area: operacion
order: 7
---

# Cuentas de evaluación y propias

En producción no se crean las cuentas de demostración (`*@trazo.test`). Para que el sitio desplegado tenga con quién entrar, `AccountSetSeeder` crea **dos juegos de cuentas, una por rol**, a partir de variables que solo existen en el entorno del servidor ([ADR-0087](../decisiones/0087-cuentas-por-rol-desde-el-entorno.md)).

| Juego | Para quién | Variables |
|---|---|---|
| Evaluación | Quienes evalúan Trazo | `EVALUATION_EMAIL_PATTERN`, `EVALUATION_PASSWORD` |
| Propias | El responsable del sitio, de respaldo | `OWNER_EMAIL_PATTERN`, `OWNER_PASSWORD`, `OWNER_LABEL` |

## Cómo se arman

- **Una cuenta por rol:** Super admin, Administrador, Supervisor, Agente y Solicitante.
- **Correo:** el patrón lleva `{rol}`, que se reemplaza por `superadmin`, `admin`, `supervisor`, `agente` o `solicitante`. Con una cuenta de Gmail se puede usar el "+", y todos los correos llegan al mismo buzón:
  - `naranjobenavidesd+eval-{rol}@gmail.com` → `naranjobenavidesd+eval-supervisor@gmail.com`, etc.
  - `naranjobenavidesd+{rol}@gmail.com` → `naranjobenavidesd+agente@gmail.com`, etc.
- **Nombre:** la etiqueta del juego como nombres ("Evaluación" o `OWNER_LABEL`) y el rol como apellido: "Evaluación Supervisor".
- **Contraseña:** la misma para las cinco cuentas de un juego. Tiene que cumplir la política de la API (12 o más caracteres, no predecible y no filtrada). Si no la cumple, o si el patrón no lleva `{rol}`, el arranque lo avisa en el registro de `init` y ese juego no se crea; el sitio arranca igual.
- **Sin verificación en dos pasos:** en el primer ingreso, Trazo muestra el QR para escanearlo con la aplicación de autenticación. Se puede posponer una vez; el segundo ingreso lo exige.
- **Solo crea las que faltan.** Si alguien cambió su contraseña o configuró su verificación, volver a desplegar no lo pisa.
- **Si una variable está vacía, ese juego no se crea.** En local se dejan vacías: ahí están las cuentas de demostración del README.

## Cómo entregarlas

1. Al desplegar, definir las variables en el entorno del servidor (nunca en el repositorio, que es público).
2. Entregar a quien evalúa **solo el correo y la contraseña** de cada rol. El QR lo escanea en su propio teléfono al entrar.
3. **Entregarlas poco antes de la presentación.** Quien entra primero con esa contraseña configura la verificación, así que conviene que pase el menor tiempo posible entre la entrega y el uso.
4. Si alguien pierde el acceso o el QR quedó en otro teléfono, el Super admin (con una cuenta propia) restablece su verificación o su acceso desde su ficha.

## Dónde está en el código

- Seeder: [`AccountSetSeeder.php`](../../backend/database/seeders/AccountSetSeeder.php).
- Configuración: [`config/trazo.php`](../../backend/config/trazo.php) (`account_sets`).
- Variables: [`.env.example`](../../.env.example) y [`docker-compose.yml`](../../docker-compose.yml).
- Pruebas: [`AccountSetSeederTest.php`](../../backend/tests/Feature/Database/AccountSetSeederTest.php).
