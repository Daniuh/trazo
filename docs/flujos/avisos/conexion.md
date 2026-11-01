---
title: Conexión y canales privados
description: Cómo el navegador se conecta al tiempo real por el gateway, qué canales puede escuchar cada persona y cómo se autoriza cada uno.
audience: tecnico
area: flujos
module: realtime
order: 1
---

# Conexión y canales privados

El tiempo real usa Reverb (WebSocket) detrás del mismo gateway que sirve la app, en `/ws`: no hay otro puerto ni otro dominio. Todos los canales son privados y sus mensajes no traen datos, solo el id del incidente y el tipo de cambio; el navegador vuelve a pedir a la API lo que necesita, con sus permisos de siempre. Si el tiempo real no está disponible, la app sigue funcionando igual.

| Canal | Quién lo escucha |
|---|---|
| `incidents.all` | Quien tiene `incidents.view.any` |
| `incidents.unassigned` | Quien tiene `incidents.view.unassigned` |
| `users.{ULID}` | Solo esa persona: lo que creó o tiene asignado, su sesión y sus notificaciones |

```flujo
carriles: [Navegador, API, Reverb]
pasos:
  - id: espera
    carril: Navegador
    tipo: inicio
    texto: Espera 1,5 s tras abrir la app
    detalle: Primero carga lo que la pantalla necesita; después se conecta.
    va: config
  - id: config
    carril: Navegador
    texto: Pide GET /realtime
    detalle: La clave pública y la ruta `/ws`, que cambian por entorno sin reconstruir el front.
    va: hay
  - id: hay
    carril: API
    tipo: decision
    texto: ¿Sesión completa?
    detalle: El token de un ingreso a medias (falta el MFA) no sirve aquí.
    si: conecta
    no: sigue
  - id: sigue
    carril: Navegador
    tipo: error
    texto: Sigue sin tiempo real
  - id: conecta
    carril: Reverb
    texto: Acepta el WebSocket por /ws
    detalle: 'Solo desde el origen de la app (`REVERB_ALLOWED_ORIGINS`), mensajes de hasta 2 KB, 60 por minuto, sin eventos de cliente.'
    va: canales
  - id: canales
    carril: Navegador
    texto: Calcula sus canales según sus permisos
    detalle: '`channelsFor`: el general si ve todo, el de sin asignar si puede verlos, y siempre el personal.'
    va: firma
  - id: firma
    carril: Navegador
    texto: Pide POST /broadcasting/auth una vez
    detalle: Todas las suscripciones de la pestaña se firman en una sola petición, porque cada petición a PHP cuesta.
    va: permite
  - id: permite
    carril: API
    tipo: decision
    texto: ¿Cada canal está permitido?
    detalle: '`Channels::allows`, la misma regla de visibilidad del listado. Devuelve una firma por canal permitido y vacío en los demás.'
    si: entra
    no: nulo
  - id: nulo
    carril: API
    tipo: error
    texto: Ese canal queda sin firma
  - id: entra
    carril: Reverb
    texto: Suscribe con la firma
    va: escucha
  - id: escucha
    carril: Navegador
    tipo: fin
    texto: Escucha y publica cada aviso en el bus
    detalle: 'Si cambian sus permisos, entra a los canales nuevos y sale de los que ya no aplican. Al reconectarse emite `reconnected` y cada pantalla vuelve a consultar lo visible.'
```

## Qué ve la persona

El tiempo real no muestra errores propios: si no se puede conectar o se cae, la app funciona igual, solo que los cambios de otras personas se ven al recargar o al navegar.

## Dónde está en el código

- [`Channels`](../../../backend/app/Modules/Realtime/Domain/Channels.php): canales y quién puede escucharlos.
- [`RealtimeController`](../../../backend/app/Modules/Realtime/Http/RealtimeController.php): configuración y firma en una sola petición.
- [`config/reverb.php`](../../../backend/config/reverb.php): orígenes, tamaño y límite de mensajes.
- [`useRealtimeConnection`](../../../frontend/src/modules/realtime/application/useRealtimeConnection.ts) y [`echoClient`](../../../frontend/src/modules/realtime/infrastructure/echoClient.ts).
- Explicación: [Módulo Realtime](../../backend/realtime.md) y [Tiempo real en operación](../../operacion/tiempo-real.md).
