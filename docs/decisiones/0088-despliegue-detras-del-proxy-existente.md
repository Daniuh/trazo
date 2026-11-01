---
title: "ADR-0088: Despliegue detrás del proxy de TLS que ya existe en el servidor"
description: "Trazo no publica puertos: el Caddy del servidor le pasa su subdominio por una red compartida, sin reiniciar nada de los otros proyectos"
audience: tecnico
area: decisiones
tags: [operacion]
order: 98
---

# ADR-0088: Despliegue detrás del proxy de TLS que ya existe en el servidor

- **Estado:** Aceptada.
- **Fecha:** 2026-11-01

## Contexto
El VPS de Contabo ([ADR-0026](0026-despliegue.md)) ya tiene otros proyectos corriendo. Uno de ellos trae un Caddy que ocupa los puertos 80 y 443, saca los certificados de Let's Encrypt y sirve también `daniuh.dev`. Solo un proceso puede escuchar en esos puertos, y esos proyectos no se pueden interrumpir. Además, `.dev` exige HTTPS siempre.

## Alternativas evaluadas
1. **Usar el Caddy existente** con un bloque para `trazo.daniuh.dev` y una red de Docker compartida con el gateway de Trazo.
2. **Un proxy propio de Trazo** (Caddy o Nginx con Certbot) en otro puerto, por ejemplo 8443.
3. **Sacar el Caddy a un proyecto neutral** que sirva a todos los sitios del servidor.
4. **Publicar el gateway en un puerto del servidor** y que Caddy llegue a él por la IP del host.

## Decisión
Se elige la **1**:
- `docker-compose.prod.yml` quita el puerto del gateway y lo conecta a la red `trazo-proxy` (nombre fijo, subred propia) con el alias `trazo-gateway`.
- El contenedor de Caddy se conecta a esa red en caliente (`docker network connect`), sin reiniciarlo, y su configuración se recarga (`caddy reload`), que no corta las conexiones y conserva la anterior si la nueva falla.
- No se toca el compose del otro proyecto: así su arranque nunca depende de que Trazo exista.
- El gateway toma la IP del visitante de `X-Forwarded-For` solo si la petición viene de `trazo-proxy` (`real-ip.prod.conf`).

## Alternativas descartadas
- **2:** un puerto distinto de 443 obliga a escribirlo en la dirección y muchas redes lo bloquean.
- **3:** es lo más limpio a largo plazo, pero exige mover el Caddy de otro proyecto y reiniciarlo, con corte para sus sitios. Queda como mejora propuesta.
- **4:** con el firewall del servidor apagado, el puerto quedaría abierto a internet sin HTTPS, y Caddy necesitaría cambios en su compose para llegar al host.

## Consecuencias
- Trazo no expone nada propio a internet: todo entra por HTTPS a través de Caddy.
- Si el contenedor de Caddy se vuelve a crear, pierde la red `trazo-proxy` y Trazo deja de responder hasta correr de nuevo `scripts/deploy/deploy.sh`, que lo reconecta. Los otros sitios no se ven afectados.
- Los límites por IP (login, recuperación de contraseña) cuentan a cada visitante, no al proxy.
- Detalle operativo en [Despliegue](../operacion/despliegue.md).
