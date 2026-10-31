---
title: Módulo Realtime
description: Avisos en tiempo real sin datos por canales privados de Reverb, autorización de cada canal según los permisos y avisos de sesión.
audience: tecnico
area: backend
module: realtime
order: 8
---

# Módulo Realtime

Avisa a las pantallas abiertas que algo cambió, para que vuelvan a consultar la API. **Por el canal nunca viaja un dato del negocio:** solo ids y el tipo de aviso. La API decide qué puede ver cada persona cuando el front vuelve a consultar ([ADR-0024](../decisiones/0024-tiempo-real.md) y [ADR-0057](../decisiones/0057-tiempo-real-en-la-practica.md)).

## Endpoints

Exigen sesión completa: un token de ingreso a medias (falta el MFA) responde `401`.

| Método | Ruta | Respuesta |
|---|---|---|
| `GET` | `/api/v1/realtime` | `{ key, path }`: la clave pública de Reverb y la ruta del gateway (`/ws`). El secreto nunca sale |
| `POST` | `/api/v1/broadcasting/auth` | Firma las suscripciones de una pestaña en una sola petición: recibe `socket_id` (`"123.456"`) y `channels` (hasta 10, `private-…`) y responde `{ canal: { auth } }`, con `null` en los canales que la persona no puede escuchar |

Las firmas van juntas porque cada petición a PHP cuesta, y al abrir la app se piden todas a la vez. Con una por canal, la carga de cada pantalla sumaba hasta cuatro peticiones y, con la API limitada a una CPU en Docker, eso alcanzaba para que otras respuestas superaran su tiempo de espera.

## Canales

| Canal | Quién lo escucha | Qué recibe |
|---|---|---|
| `incidents.all` | `incidents.view.any` | Cambios de todos los incidentes |
| `incidents.unassigned` | `incidents.view.unassigned` | Cambios de incidentes que estaban o quedan sin asignar |
| `users.{ULID}` | Solo esa persona | Cambios de lo que creó o tiene asignado, "te asignaron" y "tu sesión cambió" |

La regla es `Channels::allows` (dominio), con los permisos efectivos de la persona. `Channels::forIncident` decide a qué canales va cada cambio:

- Siempre al general.
- Al de sin asignar, si lo estaba antes o queda así. Un incidente recién creado solo cuenta por cómo quedó.
- Al canal personal de quien lo creó y de quien estaba o queda asignado, sin repetir.

## Avisos

| Nombre | Canal | Contenido | Cuándo |
|---|---|---|---|
| `incident.changed` | Los de `forIncident` | `{ id, change }` (`created`, `updated`, `deleted`, `restored`) | Cada `IncidentChanged` |
| `incident.assigned` | `users.{asignado}` | `{ id }` | El incidente quedó asignado a otra persona que no es quien hizo el cambio |
| `session.changed` | `users.{persona}` | `{}` | Cambio de rol, de permisos, de datos o de estado; restablecer el MFA o el acceso; editar su rol; cambiar o recuperar la contraseña |

- **Por la cola y tras confirmar** (`Signal`, `afterCommit`): no frenan la respuesta y nunca salen de un cambio que al final se deshizo.
- **Sin el aviso propio:** `incident.changed` sale con `toOthers()`. La pestaña que hizo el cambio manda su `X-Socket-ID` y no lo recibe.

### De dónde salen

- **Incidentes:** el repositorio emite `IncidentChanged` con el id, el ULID, qué pasó, quién lo creó y a quién estaba y queda asignado. Lo escuchan la caché del dashboard y `BroadcastIncidentChange`.
- **Sesión:** los casos de uso de Users y de Auth llaman al puerto compartido `SessionSignals`. Lo implementa `BroadcastSessionSignals`, que traduce los ids a canales personales.

## Configuración

- **Reverb** corre en su propio contenedor y la app le habla por la red interna (`http://reverb:8080`). Detalles de operación en [Tiempo real](../operacion/tiempo-real.md).
- **Pruebas:** `BROADCAST_CONNECTION=null` y la cola `sync`. Las pruebas revisan los avisos con `Event::fake`. Para la firma de canales cambian a `reverb` con una clave de prueba: Reverb firma sin necesidad del servidor.

## Capas

| Capa | Pieza | Qué hace |
|---|---|---|
| Domain | `Channels` | Nombres de canal, quién escucha cada uno y a cuáles va un cambio |
| Shared/Application | `Ports/SessionSignals` | "La sesión de estas personas cambió"; lo usan Users y Auth |
| Infrastructure | `Events/Signal` (+ `IncidentSignal`, `AssignedSignal`, `SessionSignal`) | Los avisos que salen por Reverb |
| Infrastructure | `BroadcastIncidentChange`, `BroadcastSessionSignals` | Traducen eventos y ids a avisos y canales |
| Infrastructure | `RealtimeServiceProvider` | Autorización de los canales, oyente e implementación del puerto |
| Http | `RealtimeController`, `routes.php` | Configuración de conexión y firma de suscripciones (una copia de la petición por canal para `Broadcast::auth`) |
| Http | `Requests/ChannelAuthRequest`, `Rules/SocketId` | Valida el id de la conexión y la lista de canales |

## Dónde está en el código

- [`backend/app/Modules/Realtime/`](../../backend/app/Modules/Realtime/)
- [`backend/app/Modules/Shared/Application/Ports/SessionSignals.php`](../../backend/app/Modules/Shared/Application/Ports/SessionSignals.php)
- [`backend/app/Modules/Incidents/Application/Events/IncidentChanged.php`](../../backend/app/Modules/Incidents/Application/Events/IncidentChanged.php)
- Configuración: [`config/reverb.php`](../../backend/config/reverb.php) y [`config/broadcasting.php`](../../backend/config/broadcasting.php)
- Pruebas: [`tests/Feature/Realtime/RealtimeTest.php`](../../backend/tests/Feature/Realtime/RealtimeTest.php) y [`tests/Unit/Realtime/ChannelsTest.php`](../../backend/tests/Unit/Realtime/ChannelsTest.php)
