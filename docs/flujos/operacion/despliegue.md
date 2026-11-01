---
title: Despliegue y actualización
description: Cómo llega una versión de main al servidor, detrás del proxy de TLS que ya existe, sin tocar los otros proyectos que corren ahí.
audience: tecnico
area: flujos
module: operacion
order: 3
---

# Despliegue y actualización

Trazo corre en un VPS con el mismo compose de siempre más `docker-compose.prod.yml`. En ese servidor hay otros proyectos y un proxy (Caddy) que ya tiene los puertos 80 y 443 y los certificados. Trazo no publica puertos: el proxy le pasa las peticiones de su dominio por una red de Docker compartida, `trazo-proxy`.

```flujo
carriles: [Operación, Servidor, Docker de Trazo, Proxy de TLS, Visitante]
pasos:
  - id: actualiza
    carril: Operación
    tipo: inicio
    texto: git pull --ff-only en el servidor
    detalle: 'Solo avance rápido sobre `main`: si el historial no coincide, se detiene en lugar de mezclar.'
    va: env
  - id: env
    carril: Servidor
    tipo: decision
    texto: ¿Existe el .env?
    detalle: 'Se crea una sola vez con `scripts/deploy/new-env.sh`: permisos 600 y secretos aleatorios generados en el servidor. El correo y las cuentas por rol se escriben a mano.'
    si: build
    no: sinenv
  - id: sinenv
    carril: Servidor
    tipo: error
    texto: Se detiene con "Falta el .env"
  - id: build
    carril: Docker de Trazo
    texto: up -d --build --wait
    detalle: 'Con `-p trazo` y los archivos base y de producción. Si falta un secreto obligatorio, Compose no arranca. `--wait` espera a que todo esté sano.'
    va: init
  - id: init
    carril: Docker de Trazo
    texto: init migra y siembra
    detalle: Migraciones solo aditivas; los datos, las llaves y los respaldos viven en volúmenes y no se tocan.
    va: sano
  - id: sano
    carril: Docker de Trazo
    tipo: decision
    texto: ¿Todo quedó sano?
    si: red
    no: logs
  - id: logs
    carril: Operación
    tipo: error
    texto: Revisa ps y logs del servicio que falló
  - id: red
    carril: Proxy de TLS
    tipo: decision
    texto: ¿El proxy ya está en trazo-proxy?
    detalle: 'El nombre del contenedor del proxy sale de `TRAZO_PROXY_CONTAINER`.'
    si: listo
    no: conecta
  - id: conecta
    carril: Proxy de TLS
    texto: Lo conecta a la red sin reiniciarlo
    detalle: '`docker network connect`: si alguien recreó el proxy desde su propio proyecto, perdió la red; conectarlo de nuevo es seguro y no corta nada.'
    va: listo
  - id: listo
    carril: Visitante
    tipo: fin
    texto: Entra por HTTPS al gateway de Trazo
    detalle: 'El proxy termina TLS y pasa a `trazo-gateway:8080`. El gateway pone las cabeceras de seguridad (CSP y HSTS, porque el proxy avisa que llegó por HTTPS) y solo cree la IP del visitante si viene de `trazo-proxy`.'
```

La IP real importa: los límites del ingreso y de la recuperación de contraseña cuentan por IP, y sin ella todas las personas compartirían la del proxy y se bloquearían entre sí.

## Reglas de convivencia

- Todo lo de Trazo se maneja con `docker compose -p trazo …`.
- A los otros proyectos no se les reinicia ni recrea nada. Al proxy solo se le agrega una red y se le recarga la configuración, que no corta las conexiones abiertas.

## Dónde está en el código

- [`deploy.sh`](../../../scripts/deploy/deploy.sh) y [`new-env.sh`](../../../scripts/deploy/new-env.sh).
- [`docker-compose.prod.yml`](../../../docker-compose.prod.yml): secretos obligatorios, gateway sin puerto y red `trazo-proxy`.
- [`real-ip.prod.conf`](../../../docker/nginx/real-ip.prod.conf): de qué red se acepta la IP del visitante.
- Guía completa: [Despliegue](../../operacion/despliegue.md). Decisión: [ADR-0088](../../decisiones/0088-despliegue-detras-del-proxy-existente.md).
