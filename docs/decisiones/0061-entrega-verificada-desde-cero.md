---
title: "ADR-0061: Entrega verificada desde cero"
description: "Entrega verificada desde cero: clon limpio, rutas largas en Windows, volumen de node_modules y carga diferida estricta"
audience: tecnico
area: decisiones
tags: [operacion]
order: 71
---

# ADR-0061: Entrega verificada desde cero

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
La prueba exige que el proyecto sea ejecutable. Que la suite pase en el equipo de trabajo no lo garantiza: ese equipo tiene imágenes, volúmenes y dependencias de semanas anteriores. Antes de publicar se repitió la instalación como la haría alguien nuevo: un clon en una carpeta vacía, las imágenes construidas sin caché y solo los pasos del README. El resultado quedó en el [checklist de cumplimiento](../producto/cumplimiento-de-la-prueba.md#verificación-desde-cero).

La verificación encontró tres cosas:

1. En Windows, un clon dentro de una carpeta profunda supera el límite de 260 caracteres por ruta y Git deja el checkout incompleto.
2. En local, `node_modules` del front vive en un volumen anónimo, para no pisarlo con la carpeta montada. Compose reutiliza ese volumen al recrear el contenedor, así que después de un `git pull` con dependencias nuevas, `docker compose up -d --build` construye la imagen correcta pero el contenedor sigue usando las dependencias viejas.
3. Las pruebas de N+1 cuentan consultas solo en los listados que tienen una prueba propia. Una pantalla nueva sin esa prueba podía introducir un N+1 sin que nada fallara.
4. La prueba E2E del aviso en el formulario de edición fallaba una de cada tres o cuatro veces. Esperaba la primera respuesta de `/broadcasting/auth`, que podía venir de la pantalla anterior (`page.goto` recarga la app) o del canal personal, que se confirma antes que el de incidentes. Cuando el cambio salía antes de que la pantalla nueva se suscribiera, el aviso nunca llegaba.

## Alternativas evaluadas
Para las rutas largas:
- Acortar los nombres de carpetas y archivos del backend.
- Documentar en el README dónde clonar y cómo activar `core.longpaths`.

Para el volumen de `node_modules`:
- Actualizar con `docker compose up -d --build --renew-anon-volumes`.
- Que el contenedor del front compare el `package-lock.json` con lo instalado y corra `npm ci` al arrancar si cambió.
- Montar `node_modules` en un volumen con nombre.

Para el N+1:
- Agregar pruebas que cuenten consultas en cada pantalla.
- Activar `Model::preventLazyLoading()` fuera de producción.
- Activarlo también en producción.

## Decisión
- **Rutas largas:** nota en la instalación del README. Recomienda clonar cerca de la raíz del disco o activar `git config --global core.longpaths true`.
- **Volumen de `node_modules`:** la sección "Actualizar" del README usa `--renew-anon-volumes`. Es una opción estándar de Compose, no agrega lógica al arranque y no toca los datos, que viven en volúmenes con nombre.
- **N+1:** `Model::preventLazyLoading(! $this->app->isProduction())` en `AppServiceProvider`. En desarrollo y en las pruebas, leer una relación que no se pidió con `with()` lanza una excepción. Toda la suite pasa a detectar un N+1, sin escribir una prueba por pantalla.
- **Prueba de tiempo real:** `gotoSubscribed` abre la ruta y espera el mensaje `subscription_succeeded` de Reverb para el canal que importa, y solo del WebSocket abierto en esa ruta. La espera se basa en un hecho (la suscripción confirmada) y no en un tiempo.

## Alternativas descartadas
- **Acortar nombres:** los nombres largos son los de las capas y los casos de uso (`Application/Password/RequestPasswordReset.php`). Acortarlos pierde claridad para resolver un problema que una línea de configuración ya resuelve.
- **`npm ci` al arrancar:** automatiza la actualización, pero suma lógica al arranque del contenedor y alarga cada inicio. El caso es raro (cambian las dependencias del front) y una opción documentada lo cubre.
- **Volumen con nombre:** tiene el mismo problema que el anónimo, porque un volumen con contenido tampoco se rellena con la imagen nueva.
- **Una prueba de conteo por pantalla:** depende de que alguien la escriba. La carga diferida estricta cubre todo el código que ejecuta la suite.
- **Una espera fija antes de provocar el cambio** (o reintentos automáticos de Playwright): esconde la carrera en vez de resolverla y alarga cada corrida.
- **Carga diferida estricta en producción:** un caso que la suite no cubra haría fallar la petición del usuario. En producción es preferible que una pantalla se vuelva lenta a que se caiga.

## Consecuencias
- Quien evalúe en Windows tiene la solución en el mismo lugar donde aparece el error.
- Quien actualice el proyecto no ve una pantalla rota por dependencias viejas del front.
- Cualquier relación nueva debe pedirse con `with()` o `load()`; si no, la suite falla con el nombre del modelo y la relación.
- La verificación desde cero conviene repetirla antes de cada entrega importante.

## Dónde está en el código
- [`backend/app/Providers/AppServiceProvider.php`](../../backend/app/Providers/AppServiceProvider.php): `Model::preventLazyLoading()`.
- [`README.md`](../../README.md): instalación en Windows y sección "Actualizar".
- [`docker-compose.dev.yml`](../../docker-compose.dev.yml): volumen anónimo `/app/node_modules` del front (estaba en el override hasta el [ADR-0068](0068-modo-final-por-defecto.md)).
- [`frontend/e2e/realtime.spec.ts`](../../frontend/e2e/realtime.spec.ts): `gotoSubscribed`.
