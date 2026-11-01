# Trazo

Sistema de gestión de solicitudes e incidentes: creación y seguimiento de casos, prioridades, control del ciclo de vida, filtros avanzados e indicadores, preparado para operar con grandes volúmenes de datos.

| Parte | Tecnología |
|---|---|
| API | PHP 8.4, Laravel 13, Sanctum |
| Interfaz | Vue 3, Quasar, TypeScript |
| Datos | MySQL 8.4, Redis 8.4 |
| Ejecución | Docker Compose, Nginx |

## Contenido

1. [Requisitos](#requisitos)
2. [Instalación](#instalación)
3. [Credenciales de prueba](#credenciales-de-prueba)
4. [Variables de entorno](#variables-de-entorno)
5. [Migraciones](#migraciones)
6. [Seeders](#seeders)
7. [Pruebas](#pruebas)
8. [Servicios y puertos](#servicios-y-puertos)
9. [Respaldos](#respaldos)
10. [Actualizar](#actualizar)
11. [Detener y limpiar](#detener-y-limpiar)
12. [Problemas frecuentes](#problemas-frecuentes)
13. [Documentación](#documentación)

## Requisitos

- **Docker** con Compose v2 (Docker Desktop en Windows y macOS, o Docker Engine en Linux).
- **Git.**
- Unos **4 GB de memoria libres** para los contenedores.
- Los puertos **8088** (aplicación) y **8025** (correo de prueba) libres. Si están ocupados, se cambian con una variable (ver [Problemas frecuentes](#problemas-frecuentes)).

No hace falta instalar PHP, Composer, Node ni MySQL en el equipo: todo corre dentro de los contenedores.

## Instalación

```bash
git clone <url-del-repositorio> trazo
cd trazo
docker compose up -d
```

> **En Windows:** clonar cerca de la raíz del disco (por ejemplo `C:\proyectos\trazo`). Algunas rutas del backend son largas y, dentro de una carpeta muy profunda, superan el límite de 260 caracteres de Windows: Git muestra `Filename too long` y deja el clon incompleto. Si no se puede cambiar la carpeta, activar las rutas largas antes de clonar con `git config --global core.longpaths true`.

Eso es todo. La primera vez tarda unos minutos, porque construye las imágenes, instala las dependencias y carga los datos de prueba. Las siguientes veces arranca en segundos.

Cuando termine, abrir **http://localhost:8088** e ingresar con cualquiera de las [credenciales de prueba](#credenciales-de-prueba).

Para ver si todo está listo:

```bash
docker compose ps
```

Los servicios deben aparecer como `healthy` (el worker y el scheduler, como `running`), incluido `reverb`, el de tiempo real. `init` no aparece: prepara la base de datos y termina; se ve con `docker compose ps -a` como `exited (0)`.

**No hace falta crear un `.env`.** Los valores por defecto sirven para correr en local. Solo si se quiere cambiar algo (un puerto, el volumen de datos de prueba), se copia `.env.example` a `.env` y se ajusta.

## Credenciales de prueba

Todos los usuarios tienen la misma contraseña: **`Trazo.Demo.2026`**

| Correo | Rol | Qué puede hacer |
|---|---|---|
| `superadmin@trazo.test` | Super admin | Todos los permisos: lo del supervisor, administrar a todas las personas (incluidos los administradores), restablecer la verificación en dos pasos o el acceso de cualquiera, crear y editar roles, ver toda la auditoría, revisar la salud y los eventos del sistema y ejecutar sus acciones (reiniciar procesos, limpiar caché, mantenimiento) |
| `admin@trazo.test` | Administrador | Crear y administrar a las personas por debajo de su nivel: datos, rol, permisos por persona, activar y desactivar. Ve la auditoría de la administración y sus propios incidentes |
| `supervisor@trazo.test` | Supervisor | Ver, editar, asignar, eliminar y restaurar cualquier incidente, cambiar su estado, ver el resumen general y la auditoría de incidentes |
| `agente1@trazo.test`<br>`agente2@trazo.test`<br>`agente3@trazo.test` | Agente | Atender los incidentes asignados, tomar los que no tienen responsable y cambiar su estado |
| `solicitante1@trazo.test`<br>`solicitante2@trazo.test`<br>`solicitante3@trazo.test` | Solicitante | Crear incidentes, seguir los suyos y editarlos mientras siguen en estado Abierto |

Estos usuarios **solo existen fuera de producción**: el seeder no los crea cuando `APP_ENV=production`.

### Verificación en dos pasos

Todos los roles la exigen, también en local, para que funcione igual que en producción. Hace falta una aplicación de autenticación en el teléfono (Google Authenticator, Microsoft Authenticator, Authy o cualquier otra):

1. **Primer ingreso** con cada cuenta: Trazo ofrece configurarla, pero se puede posponer con "Configurar más tarde".
2. **Desde el segundo ingreso** hay que configurarla: se escanea el código QR con la aplicación, se escribe el código de 6 dígitos que muestra y se guardan los 8 códigos de recuperación.
3. **Después,** cada ingreso pide el código de la aplicación. Si no se tiene el teléfono a mano, sirve uno de los códigos de recuperación (cada uno una sola vez).

Desde **Mi cuenta** (menú de la persona, arriba a la derecha) se ve su estado y se pueden generar códigos de recuperación nuevos. Cada cambio llega como aviso al correo de la cuenta; en local, a Mailpit (http://localhost:8025).

### Contraseña

- **¿La olvidaste?** En el login, "¿Olvidaste tu contraseña?" envía un código de 6 dígitos al correo (vale 15 minutos; en local llega a Mailpit). Si la cuenta tiene la verificación en dos pasos, también pide el código de la aplicación o uno de recuperación. Al terminar se cierran todas las sesiones abiertas.
- **Cambiarla:** desde Mi cuenta, con la contraseña actual. Se cierran las demás sesiones y la actual sigue abierta.
- **Requisitos:** de 12 a 128 caracteres y que no aparezca en filtraciones conocidas ni entre las más usadas.
- En los dos casos llega un aviso "Tu contraseña cambió". Si cambias la contraseña de un usuario de demostración, ya no entrará con `Trazo.Demo.2026`.

### Administración de usuarios

En el menú **Usuarios** (Super admin y Administrador):

- **Crear una cuenta:** nombre, correo y rol. No se define contraseña: a la persona le llega un correo de bienvenida (en local, a Mailpit) para crearla con "¿Olvidaste tu contraseña?".
- **Jerarquía:** cada quien administra solo a las personas por debajo de su rol. El Administrador no toca a otro Administrador ni al Super admin.
- **Permisos por persona:** en la ficha de cada persona, "Editar permisos" concede uno adicional o quita uno de su rol, sin crear roles nuevos.
- **Acciones delicadas:** dar un rol o un permiso de administración, o restablecer la verificación en dos pasos o el acceso de alguien, piden el código de **tu** aplicación de autenticación. Por eso, quien administra debe tenerla activa.

En el menú **Roles** (Super admin):

- **Crear un rol:** código (no cambia después), nombre, nivel único entre 1 y 99, permisos (se pueden copiar de otro rol) y si exige la verificación en dos pasos.
- **Editar:** los cambios aplican de inmediato a todas las personas con ese rol; antes de guardar se muestra a cuántas afecta. El rol Super admin no se edita.
- **Eliminar:** solo un rol creado que nadie tenga. Los cinco roles base no se eliminan.

### Tiempo real

Con dos ventanas abiertas (por ejemplo, el supervisor en una y un agente en otra), lo que cambia una persona aparece en la otra sin recargar:

- **Listado:** un aviso "Hay N incidentes con cambios de otras personas" con "Actualizar"; las filas no se mueven mientras lees.
- **Detalle:** se actualiza solo, con su historial. **Edición:** avisa que otra persona cambió el incidente.
- **Inicio:** las cifras se refrescan solas.
- **"Te asignaron INC-…"** con "Abrir" cuando alguien te asigna un incidente.
- **Permisos y sesión:** si te cambian el rol o los permisos, el menú se ajusta solo; si te desactivan, la sesión se cierra al instante.

### Auditoría

En el menú **Auditoría** se ve quién hizo qué, cuándo y desde dónde (IP y navegador):

- **Incidentes** (Supervisor y Super admin): cada cambio de cada incidente, también de los eliminados.
- **Administración** (Administrador y Super admin): lo que se hizo sobre personas y roles. El Administrador ve lo suyo y lo que toca a quien está por debajo de su nivel.
- Sin fechas muestra los últimos 30 días. Se filtra por persona, acción, incidente (por su código), persona o rol afectado, y se avanza con "Cargar más".
- Los incidentes de demostración se crean con el seeder, así que su historial no tiene IP. Los cambios que hagas tú sí la traen.

### Exportación

En **Incidentes**, el Supervisor y el Super admin pueden descargar en CSV lo que tienen filtrado: **Exportar** en la barra (en celular y tablet, en el menú ⋯).

- El archivo abre directo en Excel en español (separador punto y coma, tildes bien y fechas en hora de Colombia).
- Hasta 50.000 incidentes por archivo y 5 exportaciones por minuto.
- Cada exportación queda en **Auditoría → Administración**, con sus filtros y el número de filas.

### Sistema

El Super admin (`superadmin@trazo.test`) tiene el menú **Sistema**:

- **Salud:** el estado de la base de datos, Redis, la cola, los workers, las tareas programadas, el tiempo real y el disco, actualizado cada 30 segundos.
  - Desde ahí reinicia workers o el tiempo real, limpia la caché de datos, reintenta jobs fallidos y activa el modo mantenimiento.
  - Cada acción pide el código de la aplicación de autenticación.
- **Eventos:** fallas y alertas de seguridad agrupadas (por ejemplo, un ingreso bloqueado tras 5 contraseñas incorrectas), con cuántas veces pasó y su estado.
- Con el modo mantenimiento activo, las demás cuentas ven "Trazo está en mantenimiento" y el Super admin sigue trabajando con un aviso arriba.

> Por seguridad, después de 5 intentos fallidos con el mismo correo el ingreso se pausa 1 minuto (y más tiempo si se insiste). Lo mismo con 5 códigos incorrectos seguidos: hay que volver a poner la contraseña. Es el comportamiento esperado.

## Variables de entorno

Están todas en [`.env.example`](.env.example), con su valor por defecto para local.

| Variable | Por defecto | Para qué sirve |
|---|---|---|
| `TRAZO_HTTP_PORT` | `8088` | Puerto del equipo donde se publica la aplicación |
| `TRAZO_MAIL_UI_PORT` | `8025` | Puerto del buzón de correo de prueba (Mailpit), solo en local |
| `APP_ENV` | `local` | Entorno: `local` o `production` |
| `APP_URL` | `http://localhost:8088` | Dirección pública de la aplicación |
| `APP_KEY` | vacía | Clave de cifrado de Laravel. En local se genera sola y se guarda en un volumen. **En producción es obligatoria** |
| `SESSION_MAX_MINUTES` | `480` | Duración máxima de una sesión (8 horas) |
| `SESSION_IDLE_MINUTES` | `60` | La sesión se cierra tras este tiempo sin uso |
| `SEED_INCIDENTS` | `600` | Incidentes de prueba que se cargan (la prueba técnica pide al menos 500) |
| `SYSTEM_EVENTS_RETENTION_DAYS` | `90` | Días que se guardan los eventos del sistema ya resueltos |
| `API_RATE_LIMIT` | `120` | Peticiones por minuto a la API por persona. Solo se sube a mano para un escaneo o una prueba de carga local |
| `DB_DATABASE`, `DB_USERNAME` | `trazo` | Base de datos y usuario de MySQL |
| `DB_PASSWORD`, `DB_ROOT_PASSWORD` | `*_local_only` | Contraseñas de MySQL. **Solo para local**: en producción se definen otras |
| `REDIS_PASSWORD` | `trazo_local_only` | Contraseña de Redis. **Solo para local** |
| `MAIL_HOST`, `MAIL_PORT` | `mailpit`, `1025` | Servidor de correo. En local los correos llegan a Mailpit |
| `MAIL_USERNAME`, `MAIL_PASSWORD` | vacías | Credenciales del servidor de correo (solo en producción) |
| `MAIL_FROM_ADDRESS` | `no-reply@trazo.local` | Remitente de los correos |
| `REVERB_APP_ID`, `REVERB_APP_KEY` | `trazo`, `trazo-local-key` | Identificación del servidor de tiempo real. La clave es pública |
| `REVERB_APP_SECRET` | `trazo_local_only` | Firma las suscripciones al tiempo real. **Solo para local**: en producción se define otro |
| `REVERB_ALLOWED_ORIGINS` | `localhost` | Dominios desde los que se aceptan conexiones de tiempo real (sin `http://`) |
| `BACKUP_ENCRYPTION_KEY` | vacía | Llave que cifra los respaldos de la base. En local se genera sola. **En producción es obligatoria** y se guarda fuera del servidor |
| `BACKUP_AT`, `BACKUP_TIMEZONE` | `03:00`, `America/Bogota` | Hora del respaldo diario |
| `BACKUP_RETENTION_DAYS` | `7` | Días de respaldos que se guardan |

El resto de la configuración (conexiones a MySQL y Redis, colas, caché) está fija en [`docker-compose.yml`](docker-compose.yml), porque no cambia entre equipos. El repositorio es público: ningún secreto real se guarda en él.

## Migraciones

**Se ejecutan solas.** Cada vez que se levanta el stack, el servicio `init` corre las migraciones pendientes antes de que arranque la API. Si alguna falla, la API no arranca y el error queda en `docker compose logs init`.

Comandos útiles:

```bash
# Ver qué migraciones están aplicadas
docker compose exec app php artisan migrate:status

# Aplicar migraciones pendientes a mano
docker compose exec app php artisan migrate
```

Cada tabla tiene su migración y su ficha en el [diccionario de datos](docs/base-de-datos/index.md). Las migraciones solo agregan: nunca se edita una que ya se aplicó.

## Seeders

**También se ejecutan solos**, justo después de las migraciones. Son idempotentes: se pueden correr muchas veces sin duplicar nada ni pisar lo que alguien haya cambiado.

| Seeder | Qué carga |
|---|---|
| `IncidentCatalogSeeder` | Prioridades, estados y sus transiciones permitidas, y tipos de actividad del historial |
| `AccessControlSeeder` | Roles, permisos y qué permisos tiene cada rol |
| `DashboardWidgetSeeder` | Los widgets del dashboard con sus tamaños |
| `DemoUserSeeder` | Los 8 usuarios de prueba (no en producción) |
| `IncidentVolumeSeeder` | 600 incidentes de prueba con su historial completo (no en producción) |

Los incidentes de prueba cumplen lo que pide la prueba técnica: **más de 500, de varios usuarios y con todos los estados y prioridades.** Llegan a lo largo de los últimos 60 días, como en una mesa de ayuda real: lo reciente sigue abierto, lo antiguo ya se atendió y algunos quedaron vencidos. Cada uno trae su historial (creación, asignación y cambios de estado). Una prueba automática verifica todo esto.

Comandos útiles:

```bash
# Volver a correr los seeders (agrega solo lo que falta)
docker compose exec app php artisan db:seed

# Empezar de cero: BORRA TODOS LOS DATOS y vuelve a cargar los de prueba
docker compose exec app php artisan migrate:fresh --seed

# Cargar más volumen, por ejemplo 100.000 incidentes (alrededor de un minuto)
docker compose exec -e SEED_INCIDENTS=100000 app php artisan db:seed --class=IncidentVolumeSeeder
```

Más detalle en [Seeders](docs/base-de-datos/seeders.md) y en [Rendimiento](docs/base-de-datos/rendimiento.md), donde están las mediciones con 100.000 incidentes.

## Pruebas

**API** (Pest, dentro del contenedor y contra una base aparte, `trazo_test`):

```bash
# Solo las pruebas
docker compose exec app composer test

# Pruebas, estilo (Pint) y análisis estático (Larastan nivel 8)
docker compose exec app composer check

# Pruebas con cobertura: exige 90 % en dominio y aplicación y 80 % en general
docker compose exec app composer coverage
```

**Interfaz** (requiere Node 24 en el equipo):

```bash
cd frontend
npm ci

# Tipos, lint, estilos y pruebas unitarias
npm run check

# Pruebas unitarias con cobertura: exige 90 % en dominio y aplicación
npm run test:coverage

# Pruebas de extremo a extremo en las cuatro vistas, con revisión de accesibilidad.
# Necesitan el stack levantado y, la primera vez, los navegadores de Playwright: npx playwright install chromium
npm run test:e2e

# Acciones de sistema reales (reinician workers y Reverb): aparte de la suite normal
npm run test:e2e:ops
```

La seguridad se revisa además con OWASP ZAP, como perfil opcional del Docker: ver [Escaneo con OWASP ZAP](docs/seguridad/zap.md) y [OWASP Top 10](docs/seguridad/owasp-top-10.md).

Las pruebas de extremo a extremo hacen de "teléfono" para la verificación en dos pasos: guardan la clave de cada cuenta de prueba en `frontend/e2e/.auth/` (no se versiona) y calculan sus códigos. Si esa carpeta se borra, las cuentas que ya tenían la verificación activa no se pueden usar en las pruebas hasta recargar los datos (`docker compose exec app php artisan migrate:fresh --seed`).

La [estrategia de pruebas](docs/calidad/estrategia-de-pruebas.md) explica qué cubre cada una.

## Servicios y puertos

| Dirección | Qué es |
|---|---|
| http://localhost:8088 | La aplicación |
| http://localhost:8088/api/v1/... | La API que usa la interfaz |
| ws://localhost:8088/ws/... | Tiempo real: los cambios de otras personas llegan sin recargar |
| http://localhost:8025 | Mailpit: buzón donde llegan los correos en local |

Solo el gateway publica un puerto. MySQL y Redis viven en una red interna, sin acceso desde fuera. El detalle de cada contenedor está en [Docker](docs/operacion/docker.md).

## Respaldos

El servicio `backup` copia la base cada día a las 3:00 (hora de Bogotá), comprimida y cifrada, y guarda los últimos 7 días. Corre solo desde que se levanta el stack; el primer respaldo aparece a los pocos segundos.

```bash
# Ver los respaldos
docker compose exec backup trazo-backup list

# Hacer uno ahora
docker compose exec backup trazo-backup run
```

Cómo probarlos y restaurarlos: [Respaldos](docs/operacion/respaldos.md).

## Actualizar

Después de traer cambios del repositorio, levantar el stack reconstruyendo las imágenes, por si cambiaron sus archivos o las dependencias. Las migraciones y los seeders nuevos se aplican solos al arrancar.

```bash
git pull
docker compose up -d --build --renew-anon-volumes
```

`--renew-anon-volumes` hace falta porque, en local, las dependencias del front (`node_modules`) viven en un volumen anónimo que Compose reutiliza al recrear el contenedor: sin esa opción, la imagen nueva trae las dependencias actualizadas, pero el contenedor sigue usando las anteriores. No toca los datos: la base, la caché y la clave están en volúmenes con nombre.

## Detener y limpiar

```bash
# Detener (los datos se conservan)
docker compose stop

# Detener y quitar los contenedores (los datos se conservan)
docker compose down

# Quitar también los datos: la base, la caché, las claves generadas y los respaldos
docker compose down -v
```

Estos comandos solo afectan a Trazo: todos sus contenedores, redes y volúmenes llevan el prefijo `trazo`.

## Problemas frecuentes

**El puerto 8088 u 8025 ya está en uso.** Crear un `.env` con otro puerto y volver a levantar:

```bash
cp .env.example .env
# editar TRAZO_HTTP_PORT (y APP_URL con el mismo puerto) o TRAZO_MAIL_UI_PORT
docker compose up -d
```

**La primera carga tarda o la página muestra "No pudimos cargar Trazo".** La API todavía está preparando la base de datos. Esperar a que `docker compose ps` muestre `app` como `healthy` y pulsar "Reintentar".

**Algo no arranca.** Revisar el registro del servicio, por ejemplo `docker compose logs init` o `docker compose logs app`.

## Documentación

La carpeta [`docs/`](docs/index.md) explica el producto y cada decisión técnica.

- [Índice general](docs/index.md)
- [Arquitectura](docs/arquitectura/index.md)
- [Registro de decisiones](docs/decisiones/index.md): cada elección con sus alternativas y por qué se descartaron
- [Mejoras aplicadas](docs/mejoras/aplicadas.md) y [propuestas](docs/mejoras/propuestas.md): lo que va más allá de la prueba técnica
- [Flujo de trabajo con Git](docs/operacion/flujo-de-trabajo.md)

Las clases y funciones se documentan en el código (PHPDoc y TSDoc). Para generar el sitio navegable, que queda en `docs-build/`:

```bash
# Backend, con phpDocumentor
docker compose --profile docs run --rm phpdoc

# Frontend, con TypeDoc (desde frontend/)
npm run docs:ts
```

| Carpeta | Contenido |
|---|---|
| [`backend/`](backend/) | API REST en Laravel |
| [`frontend/`](frontend/) | Interfaz en Vue 3 + Quasar |
| [`docker/`](docker/) | Imágenes y configuración de los contenedores |
| [`docs/`](docs/index.md) | Documentación funcional y técnica |
