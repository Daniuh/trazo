---
title: "ADR-0062: Correos cifrados en la cola"
description: "Correos cifrados en la cola: el contenido de cada correo pendiente o fallido es ilegible sin la clave de la aplicación"
audience: tecnico
area: decisiones
order: 72
---

# ADR-0062: Correos cifrados en la cola

- **Estado:** Aceptada
- **Fecha:** 2026-10-26

## Contexto
Todos los correos de Trazo salen por la cola, para que la respuesta no espere al servidor de correo. Mientras esperan, quedan serializados en Redis. Si fallan tres veces, quedan guardados en la tabla `failed_jobs`. Al documentar esa tabla se revisó qué llevan:

- `PasswordResetCode`: el nombre de la persona y **el código de recuperación de 6 dígitos**, en texto plano.
- `SecurityNotice`, `AccountNotice` y `AccountWelcome`: el nombre, la dirección de destino y el detalle del aviso.

El código de recuperación vence en 15 minutos y es de un solo uso, así que el riesgo era bajo. Aun así, quien leyera Redis o una copia de la base podía usarlo dentro de ese plazo. Además, chocaba con una regla del proyecto: nada sensible en texto plano.

## Alternativas evaluadas
- Cifrar los correos en cola con `ShouldBeEncrypted` de Laravel.
- Mandar el código de recuperación sin cola, en la misma petición.
- Que el correo lleve solo el id de la solicitud y lea el código al enviarse.
- Dejarlo como estaba y documentar el riesgo.

## Decisión
Los cuatro correos implementan `ShouldBeEncrypted`. Laravel cifra el trabajo completo con la `APP_KEY` (AES-256-CBC con MAC) antes de ponerlo en la cola, y lo descifra el worker al ejecutarlo. Lo que queda en Redis o en `failed_jobs` es ilegible sin la clave, que vive solo en el entorno.

Una prueba de arquitectura recorre `app/` y falla si una clase que extiende `Mailable` y va por la cola no implementa `ShouldBeEncrypted`. Así un correo nuevo no puede olvidarlo.

## Alternativas descartadas
- **Sin cola:** la petición quedaría esperando al servidor de correo (segundos con Gmail), y una falla del correo se convertiría en un error para la persona. Además, el tiempo de respuesta revelaría si el correo existe, que es justo lo que el endpoint oculta al responder siempre 202.
- **Solo el id en el correo:** el código se guarda únicamente como HMAC y no se puede recuperar, así que habría que guardarlo en claro en otra parte. Se mueve el problema, no se resuelve.
- **Dejarlo documentado:** el arreglo es una línea por correo y no tiene costo de operación.

## Consecuencias
- El contenido de los correos pendientes o fallidos no se puede leer ni en Redis ni en una copia de la base.
- El panel de sistema sigue mostrando el nombre del trabajo fallido: Laravel lo guarda fuera de la parte cifrada.
- Si se rota la `APP_KEY`, los correos que estén en la cola en ese momento ya no se pueden descifrar y fallan. Antes de rotarla hay que esperar a que la cola se vacíe. Queda anotado en la operación del despliegue.
- Los avisos de tiempo real no se cifran: solo llevan el id del incidente.

## Dónde está en el código
- [`PasswordResetCode`](../../backend/app/Modules/Auth/Infrastructure/Mail/PasswordResetCode.php) y [`SecurityNotice`](../../backend/app/Modules/Auth/Infrastructure/Mail/SecurityNotice.php).
- [`AccountNotice`](../../backend/app/Modules/Users/Infrastructure/Mail/AccountNotice.php) y [`AccountWelcome`](../../backend/app/Modules/Users/Infrastructure/Mail/AccountWelcome.php).
- [`tests/Arch/ArchitectureTest.php`](../../backend/tests/Arch/ArchitectureTest.php): "todo correo que sale por la cola viaja cifrado".
- Tabla: [failed_jobs](../base-de-datos/diccionario/failed-jobs.md).
