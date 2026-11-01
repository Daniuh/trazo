---
title: "ADR-0070: Rechazar contraseñas predecibles aunque sean largas"
description: "Además del largo y de las filtraciones, se rechazan las contraseñas repetitivas, secuenciales o con datos de la persona o de la marca"
audience: tecnico
area: decisiones
tags: [seguridad]
order: 80
---

# ADR-0070: Rechazar contraseñas predecibles aunque sean largas

- **Estado:** Aceptada. Complementa al [ADR-0016](0016-contrasenas.md).
- **Fecha:** 2026-10-31

## Contexto
La política exigía de 12 a 128 caracteres y que la contraseña no estuviera filtrada: una lista local de las 10.000 más comunes y Have I Been Pwned. Una contraseña como `ddddddddddddddddd` cumple el largo y no está en la lista local. Solo la frenaba Have I Been Pwned si la tenía registrada, y si ese servicio no respondía, se aceptaba.

La guía del NIST SP 800-63B pide bloquear lo predecible: caracteres repetidos, secuencias y palabras del contexto, como el nombre del servicio o de la persona. También pide no exigir reglas de composición.

## Alternativas evaluadas
- Reglas propias de patrones en el dominio, sin dependencias: repetición, secuencias y datos de la persona o de la marca.
- zxcvbn: un medidor de fortaleza por puntaje, en el back y en el front.
- Reglas de composición: exigir mayúsculas, números y símbolos.
- Dejarlo como estaba y confiar en Have I Been Pwned.

## Decisión
- **`WeakPasswordPatterns` en el dominio** revisa tres motivos, cada uno con su propio código de error y su propio mensaje:
  - `PASSWORD_REPETITIVE`: menos de 5 caracteres distintos o un bloque repetido de principio a fin.
  - `PASSWORD_SEQUENTIAL`: tramos de 4 o más caracteres seguidos del alfabeto, los números o las filas del teclado (hacia adelante o hacia atrás) que ocupan la mitad o más de la contraseña.
  - `PASSWORD_PERSONAL`: contiene el correo, el nombre o la marca (`trazo`, `psicol`), sin distinguir mayúsculas ni tildes. Los pedazos de menos de 4 letras no cuentan.
- **Al recuperar la contraseña** solo se usa el correo que trae la petición. Buscar el nombre antes de validar el pase permitiría probar nombres contra una cuenta ajena.
- **Orden de las revisiones:** largo, confirmación, patrones y, al final, la filtración, cortando en la primera falla. Una contraseña predecible no llega a consultarse en Have I Been Pwned.
- **El front no copia las reglas.** Muestra el requisito "Sin repeticiones, secuencias ni tus datos personales (lo revisamos al guardar)" y, si falla, el mensaje del motivo que devuelve la API.
- **La validación admite reglas con varios códigos** (`HasErrorCodes`): la regla falla con el código como mensaje y declara todos los posibles.
- **La contraseña de demostración pasa a ser `Cometa.Demo.2026`**, porque `Trazo.Demo.2026` contiene la marca. La aplicación no debe entregar una clave que sus propias reglas consideran débil, y las pruebas E2E necesitan devolver las cuentas a su contraseña desde la pantalla.

## Alternativas descartadas
- **zxcvbn:** es más completo, pero suma una dependencia en el back y en el front. Las tecnologías nuevas van como mejora propuesta en el anexo de la prueba técnica, no dentro de la entrega. Queda en [mejoras propuestas](../mejoras/propuestas.md).
- **Reglas de composición:** el NIST las desaconseja porque producen contraseñas previsibles (`Clave2026!`) y molestan sin mejorar la seguridad.
- **Confiar solo en Have I Been Pwned:** depende de un servicio externo y no conoce el contexto de la persona ni de la aplicación.

## Consecuencias
- Las contraseñas largas pero obvias se rechazan aunque el servicio externo no responda.
- Cada rechazo dice qué pasó y qué hacer, sin mensajes genéricos.
- Las contraseñas que ya existen no se revisan otra vez; la regla aplica al cambiarlas o recuperarlas.
- Las cuentas demo de una instalación existente conservan su contraseña anterior, porque el seeder no la cambia. En local se actualizan una vez a mano.

## Dónde está en el código
- [`backend/app/Modules/Auth/Domain/Password/WeakPasswordPatterns.php`](../../backend/app/Modules/Auth/Domain/Password/WeakPasswordPatterns.php): `weakness`.
- [`backend/app/Modules/Auth/Domain/Password/PasswordWeakness.php`](../../backend/app/Modules/Auth/Domain/Password/PasswordWeakness.php): motivos y códigos.
- [`backend/app/Modules/Auth/Http/Rules/NotWeakPassword.php`](../../backend/app/Modules/Auth/Http/Rules/NotWeakPassword.php) y [`NewPassword.php`](../../backend/app/Modules/Auth/Http/Rules/NewPassword.php).
- [`backend/app/Modules/Shared/Http/Errors/HasErrorCodes.php`](../../backend/app/Modules/Shared/Http/Errors/HasErrorCodes.php) y [`ValidationErrorFormatter.php`](../../backend/app/Modules/Shared/Http/Errors/ValidationErrorFormatter.php).
- [`backend/tests/Unit/Auth/WeakPasswordPatternsTest.php`](../../backend/tests/Unit/Auth/WeakPasswordPatternsTest.php) y [`backend/tests/Feature/Auth/PasswordTest.php`](../../backend/tests/Feature/Auth/PasswordTest.php).
- [`frontend/src/modules/auth/domain/password.ts`](../../frontend/src/modules/auth/domain/password.ts) y [`frontend/src/modules/auth/errors.ts`](../../frontend/src/modules/auth/errors.ts).
