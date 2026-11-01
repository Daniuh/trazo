---
title: "ADR-0057: Tiempo real en la práctica: canales por alcance, avisos sin datos y Reverb detrás del gateway"
description: "Tiempo real en la práctica: canales por alcance, avisos sin datos y Reverb detrás del gateway"
audience: tecnico
area: decisiones
order: 67
---

# ADR-0057: Tiempo real en la práctica: canales por alcance, avisos sin datos y Reverb detrás del gateway

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
El [ADR-0024](0024-tiempo-real.md) ya había fijado lo esencial: Reverb, canales privados y mensajes sin datos que obligan a volver a consultar la API. Al construirlo había que decidir:

- Cómo se reparten los avisos entre canales.
- Si una persona recibe el aviso de su propio cambio.
- Qué hace cada pantalla al recibir un aviso.
- Cómo llega el navegador a Reverb y cómo se protege.
- Qué pasa con la sesión cuando cambian los permisos o se cierra.
- Qué versión de Reverb instalar.

## Alternativas evaluadas
Reparto de avisos:
1. **Canales por alcance: `incidents.all`, `incidents.unassigned` y un canal personal `users.{id}`.**
2. Un canal por persona, avisando uno por uno a todos los que pueden ver el incidente.

Avisos propios:
1. **No avisar a la pestaña que hizo el cambio (`toOthers` con la cabecera `X-Socket-ID`).**
2. Avisar a todos y que el front descarte los suyos.

Sesión:
1. **Aviso `session.changed` sin datos; el front vuelve a pedir `/auth/me`.**
2. Avisos distintos para "permisos cambiados" y "sesión cerrada".

Llegada al servidor:
1. **Por el gateway en `/ws`, mismo dominio, sin puerto propio.**
2. Publicar el puerto de Reverb.

Configuración del front:
1. **La clave pública se pide a la API (`GET /realtime`) al conectarse.**
2. Fijarla al compilar el front (`VITE_REVERB_APP_KEY`).

Versión de Reverb:
1. **Reverb 1.12, bajando Guzzle de 8.3 a 7.16.**
2. Soketi, un servidor compatible escrito en Node.
3. Esperar a que Reverb acepte Guzzle 8.

## Decisión
- **Canales por alcance.** Repiten la regla de visibilidad del listado. Cada cambio se avisa así:
  - Siempre al canal general.
  - Al de sin asignar, si lo estaba antes o queda así.
  - Al canal personal de quien lo creó y de quien estaba o queda asignado.

  Quien escucha cada canal lo decide `Channels::allows` con los permisos efectivos. La firma de cada suscripción pasa por la API con el token de la sesión; el token a medio MFA no sirve.
- **`toOthers`.** El cliente HTTP manda el `X-Socket-ID` de la pestaña, y Laravel no le envía el aviso de su propio cambio.
- **Aviso de asignación.** Por el canal personal de quien recibe el incidente, salvo que se lo haya tomado a sí mismo. El front pide el código y el título a la API para mostrar "Te asignaron INC-000123", con "Abrir".
- **Sesión.** Un solo aviso, `session.changed`. Lo envían:
  - Users: cambio de rol, de permisos, de datos y de estado, restablecer el MFA o el acceso, y editar un rol (a todas sus personas).
  - Auth: cambio y recuperación de contraseña.

  El front vuelve a pedir `/auth/me`: si la sesión sigue, actualiza permisos y menú; si no, la API responde `UNAUTHENTICATED` y se cierra con su aviso. Un solo aviso basta, porque la API ya sabe distinguir los casos.
- **Gateway.** `/ws/app/<clave>` llega a Reverb como `/app/<clave>`. Reverb no publica puerto. Además:
  - Acepta solo el dominio de la app (`REVERB_ALLOWED_ORIGINS`).
  - No deja que los navegadores se manden eventos entre sí.
  - Limita cada mensaje a 2 KB y cada conexión a 60 mensajes por minuto.
- **Clave por la API.** La misma imagen del front sirve en stage y en producción, aunque cada uno tenga su propia clave. El secreto nunca sale del back.
- **Pocas peticiones al abrir.** Las firmas de todos los canales van en una sola petición (`channels`, hasta 10). La configuración se guarda por pestaña, y la conexión arranca 1,5 s después de abrir la app.
  - Con una firma por canal, cada carga de pantalla sumaba hasta cuatro peticiones a PHP. En el Docker local (una CPU para la API), eso bastó para que otras respuestas superaran su tiempo de espera: la suite E2E pasó de 6 a 33 minutos y varias pruebas fallaron.
- **Por la cola y tras confirmar.** Los avisos van por la cola (Redis y el worker) y solo si la transacción se confirma.
- **Reverb 1.12.** Era la última versión y exige `guzzlehttp/psr7` 2.x, mientras Guzzle 8 exige la 3.x. Laravel 13 acepta Guzzle 7.8 o superior, y solo usamos Guzzle en la consulta de contraseñas filtradas. Se instaló Guzzle 7.16, rama que sigue recibiendo versiones, y `composer audit` no reporta nada.

## Alternativas descartadas
- **Un canal por persona:** cada cambio obliga a calcular quiénes pueden verlo. Un incidente sin asignar lo ven todos los agentes, así que serían muchos mensajes por cada cambio.
- **Avisar a todos y descartar en el front:** el front tendría que recordar qué cambió él mismo, y un aviso propio podría llegar antes que la respuesta de la API.
- **Avisos de sesión separados:** el front tendría que interpretar cada caso, y la API ya lo resuelve con `/auth/me`.
- **Publicar el puerto de Reverb:** otro puerto abierto en el servidor y otro certificado. Por el gateway, la conexión usa el mismo dominio y el mismo TLS.
- **Clave al compilar:** obliga a una imagen del front distinta por entorno.
- **Soketi:** agrega una tecnología al stack y cambia una decisión ya aprobada.
- **Esperar:** detiene la fase sin una fecha conocida.

## Consecuencias
- Los cambios de otras personas se ven en segundos y por el canal no viaja ningún dato. Quien recibe un aviso de algo que ya no puede ver, consulta y la API le responde que no existe.
- Si alguien pierde un permiso, deja de recibir los avisos de ese canal al instante, porque el front se da de baja al recibir sus permisos nuevos. Aunque quedara escuchando, solo recibiría ids.
- Al reconectarse, cada pantalla vuelve a consultar lo que muestra, por si se perdió un aviso. Si Reverb no está disponible, la app funciona igual: sin avisos.
- Guzzle queda en la rama 7. Cuando Reverb acepte la 8, Dependabot propondrá la actualización.
- Un servicio más en Docker (`reverb`), con sus límites y su healthcheck.

## Dónde está en el código
- Back: [`backend/app/Modules/Realtime/`](../../backend/app/Modules/Realtime/), [`Shared/Application/Ports/SessionSignals.php`](../../backend/app/Modules/Shared/Application/Ports/SessionSignals.php) y [`IncidentChanged`](../../backend/app/Modules/Incidents/Application/Events/IncidentChanged.php)
- Configuración: [`config/reverb.php`](../../backend/config/reverb.php), [`config/broadcasting.php`](../../backend/config/broadcasting.php), [`docker-compose.yml`](../../docker-compose.yml) y [`docker/nginx/gateway.conf`](../../docker/nginx/gateway.conf)
- Front: [`frontend/src/modules/realtime/`](../../frontend/src/modules/realtime/) y [`shared/http/socketId.ts`](../../frontend/src/shared/http/socketId.ts)
- Pruebas: [`RealtimeTest.php`](../../backend/tests/Feature/Realtime/RealtimeTest.php), [`ChannelsTest.php`](../../backend/tests/Unit/Realtime/ChannelsTest.php) y [`e2e/realtime.spec.ts`](../../frontend/e2e/realtime.spec.ts)
