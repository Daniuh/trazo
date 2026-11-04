---
title: Registro de decisiones
description: Índice de los registros de decisión (ADR) del proyecto.
audience: tecnico
area: decisiones
order: 1
---

# Registro de decisiones

Cada decisión importante del proyecto queda registrada con su contexto, las alternativas evaluadas, lo que se eligió, por qué se descartaron las demás opciones y sus consecuencias. Las nuevas decisiones siguen la [plantilla](plantilla.md).

| ADR | Decisión |
|---|---|
| [ADR-0001](0001-monolito-modular.md) | Monolito modular con capas limpias en el backend |
| [ADR-0002](0002-cqrs-ligero.md) | CQRS ligero: escrituras por dominio, lecturas optimizadas |
| [ADR-0003](0003-frontend-modular.md) | Frontend modular por funcionalidad con capas |
| [ADR-0004](0004-typescript.md) | TypeScript en el frontend |
| [ADR-0005](0005-quasar.md) | Quasar como librería de interfaz |
| [ADR-0006](0006-contrato-api.md) | Contrato de la API: REST versionada, respuestas y errores |
| [ADR-0007](0007-mysql.md) | MySQL 8.4 LTS como base de datos |
| [ADR-0008](0008-catalogos.md) | Estado y prioridad como tablas catálogo |
| [ADR-0009](0009-sin-polimorficas.md) | Sin relaciones polimórficas: toda referencia es una FK |
| [ADR-0010](0010-ulid.md) | ULID público y BIGINT interno |
| [ADR-0011](0011-borrado-y-restricciones.md) | Borrado lógico, usuarios desactivados y FK con RESTRICT |
| [ADR-0012](0012-bloqueo-optimista.md) | Bloqueo optimista en incidentes |
| [ADR-0013](0013-busqueda-fulltext.md) | Búsqueda por texto con índice FULLTEXT |
| [ADR-0014](0014-crecimiento.md) | Estrategia de crecimiento por etapas, sin particionamiento |
| [ADR-0015](0015-autenticacion-sanctum.md) | Autenticación con Laravel Sanctum y token Bearer |
| [ADR-0016](0016-contrasenas.md) | Argon2id y política de contraseñas |
| [ADR-0017](0017-mfa-totp.md) | MFA con TOTP para todos los usuarios |
| [ADR-0018](0018-recuperacion-contrasena.md) | Recuperación de contraseña por correo y TOTP |
| [ADR-0019](0019-cifrado.md) | Cifrado en tránsito y en reposo |
| [ADR-0020](0020-backend-autoridad.md) | El backend como única autoridad |
| [ADR-0021](0021-permisos-granulares.md) | Roles construidos sobre permisos granulares |
| [ADR-0022](0022-logs-relevantes.md) | Logs del sistema: solo eventos relevantes, agrupados por huella |
| [ADR-0023](0023-acciones-de-sistema.md) | Acciones de sistema dentro del alcance de la aplicación |
| [ADR-0024](0024-tiempo-real.md) | Tiempo real con Reverb y mensajes sin datos |
| [ADR-0025](0025-docker.md) | Contenedores: gateway de origen único y una sola imagen de Laravel |
| [ADR-0026](0026-despliegue.md) | Despliegue en VPS con el mismo docker compose |
| [ADR-0027](0027-diseno-por-tokens.md) | Diseño por tokens, mobile first y estilos por bloque de vista |
| [ADR-0028](0028-identidad-visual.md) | Identidad visual de Trazo |
| [ADR-0029](0029-graficos-propios.md) | Gráficos con componentes SVG propios |
| [ADR-0030](0030-dashboard-personalizable.md) | Dashboard personalizable guardado en el servidor |
| [ADR-0031](0031-estrategia-de-pruebas.md) | Estrategia de pruebas |
| [ADR-0032](0032-calidad-estatica.md) | Análisis estático, estilo y seguridad automatizada |
| [ADR-0033](0033-flujo-git.md) | Flujo de Git con cuatro ramas |
| [ADR-0034](0034-ci-cd.md) | CI/CD con GitHub Actions y promoción de la misma imagen |
| [ADR-0035](0035-documentacion.md) | Documentación en Markdown, con ADR y trazabilidad al código |
| [ADR-0036](0036-exportacion-csv.md) | Exportación en CSV |
| [ADR-0037](0037-idempotencia.md) | Idempotency-Key en la creación de incidentes |
| [ADR-0038](0038-versiones-del-stack.md) | Versiones del stack |
| [ADR-0039](0039-quasar-como-plugin-de-vite.md) | Quasar integrado como plugin de Vite |
| [ADR-0040](0040-axios.md) | Axios como base del cliente HTTP |
| [ADR-0041](0041-ci-con-el-stack-de-docker.md) | El CI prueba el backend dentro del stack de Docker |
| [ADR-0042](0042-sesion-y-bloqueo.md) | Duración de la sesión y bloqueo progresivo por huella del correo |
| [ADR-0043](0043-reglas-de-acceso-en-el-dominio.md) | Reglas de acceso a incidentes en una sola clase de dominio |
| [ADR-0044](0044-listado-de-incidentes.md) | Listado de incidentes filtrado en SQL con la misma regla de visibilidad |
| [ADR-0045](0045-listado-en-el-front.md) | Listado en el front con los filtros en la URL y una presentación por vista |
| [ADR-0046](0046-pruebas-e2e.md) | Pruebas E2E en las cuatro vistas, con axe bloqueante y sesión por la API |
| [ADR-0047](0047-metricas-del-dashboard.md) | Métricas del dashboard con la visibilidad del listado y caché invalidada por cambios |
| [ADR-0048](0048-diseno-del-dashboard-por-persona.md) | Diseño del dashboard guardado widget por widget, con los ocultos en su lugar |
| [ADR-0049](0049-sesion-sin-conexion.md) | La sesión solo se cierra cuando la API la rechaza |
| [ADR-0050](0050-editor-del-dashboard.md) | Editor del dashboard con grid-layout-plus, sin sus estilos inyectados y con alternativa de teclado |
| [ADR-0051](0051-mfa-en-la-practica.md) | MFA en la práctica: token de un ingreso a medias, sin atajo local y avisos por correo |
| [ADR-0052](0052-recuperacion-y-cambio-de-contrasena.md) | Recuperación y cambio de contraseña: pase de un solo uso, HMAC, Have I Been Pwned y segundo factor |
| [ADR-0053](0053-administracion-de-usuarios.md) | Administración de usuarios: jerarquía por nivel, permisos por persona y reautenticación |
| [ADR-0054](0054-matriz-de-permisos-no-destructiva.md) | La matriz de permisos se siembra solo para roles y permisos nuevos |
| [ADR-0055](0055-edicion-de-roles.md) | Edición de roles: rol de la cima bloqueado, nivel único, roles de sistema y sin correo masivo |
| [ADR-0056](0056-auditoria-global.md) | Auditoría global: dos registros con permisos separados, alcance por jerarquía y paginación por cursor |
| [ADR-0057](0057-tiempo-real-en-la-practica.md) | Tiempo real en la práctica: canales por alcance, avisos sin datos y Reverb detrás del gateway |
| [ADR-0058](0058-exportacion-en-la-practica.md) | Exportación en la práctica: CSV para Excel en español, tope de filas y registro en la auditoría |
| [ADR-0059](0059-sistema-en-la-practica.md) | Sistema en la práctica: salud medida desde la app, eventos agrupados y acciones con reautenticación |
| [ADR-0060](0060-pasada-de-calidad.md) | Pasada de calidad: CSP estricta, CORS cerrado, contrato OpenAPI, ZAP y cobertura exigida |
| [ADR-0061](0061-entrega-verificada-desde-cero.md) | Entrega verificada desde cero: clon limpio, rutas largas en Windows, volumen de `node_modules` y carga diferida estricta |
| [ADR-0062](0062-correos-cifrados-en-la-cola.md) | Correos cifrados en la cola: el contenido de cada correo pendiente o fallido es ilegible sin la clave de la aplicación |
| [ADR-0063](0063-pantalla-que-no-se-descarga.md) | Pantalla que no se descarga: aviso global con "Recargar", mensaje propio y ningún código de soporte inexistente |
| [ADR-0064](0064-respaldo-diario-de-mysql.md) | Respaldo diario de MySQL: servicio propio, volcado consistente, cifrado y firmado, siete días de historia |
| [ADR-0065](0065-documentacion-de-funciones.md) | Documentación de funciones: phpDocumentor en su imagen oficial y TypeDoc, generadas bajo demanda y en el CI |
| [ADR-0066](0066-llave-de-la-app-sin-entrypoint.md) | La configuración lee la `APP_KEY` del volumen local cuando no llega por el entorno |
| [ADR-0067](0067-pantallas-precompiladas-en-desarrollo.md) | Vite precompila el layout y las pantallas al arrancar, para que la primera visita no espere |
| [ADR-0068](0068-modo-final-por-defecto.md) | `docker compose up -d` levanta el modo final (rápido); el modo desarrollo pasa a un archivo opcional |
| [ADR-0069](0069-cambio-de-rol-con-permisos-exactos.md) | Al cambiar de rol, la persona queda con los permisos exactos del rol nuevo; el registro guarda las excepciones que tenía |
| [ADR-0070](0070-contrasenas-predecibles.md) | Se rechazan las contraseñas repetitivas, secuenciales o con datos de la persona o la marca; la clave demo pasa a `Cometa.Demo.2026` |
| [ADR-0071](0071-nota-de-estado-y-reapertura.md) | Resuelto y Cancelado exigen una nota; lo cerrado o cancelado solo se reabre con motivo y la aprobación de quien supervisa |
| [ADR-0072](0072-asignarse-al-crear.md) | Quien puede tomar incidentes marca "Asignármelo" al crear; solo puede elegirse a sí mismo |
| [ADR-0073](0073-largo-del-titulo.md) | El título de un incidente va de 5 a 80 caracteres, con contador en el formulario |
| [ADR-0074](0074-codigo-de-incidente-por-ano.md) | El código del incidente pasa a prefijo, año y consecutivo del año (TZ-26-0142) |
| [ADR-0075](0075-menus-que-se-abren-donde-caben.md) | Los menús miden el espacio antes de abrirse: abajo, arriba, al lado o bajando la página |
| [ADR-0076](0076-conversacion-del-incidente.md) | Cada incidente tiene una conversación tipo chat; se borra el propio durante 5 minutos |
| [ADR-0077](0077-centro-de-notificaciones.md) | Campana con Conversaciones y Actividad para los avisos de cada persona; el perfil pasa al pie del menú lateral |
| [ADR-0078](0078-mis-incidentes.md) | "Mis incidentes": tablero con lo asignado, en Kanban (arrastrando para cambiar de estado), Lista, Calendario o Mi día |
| [ADR-0079](0079-menu-en-dock.md) | El menú lateral y el encabezado dan paso a un dock abajo y centrado en las cuatro vistas; los avisos, arriba a la derecha |
| [ADR-0080](0080-lista-agrupada-con-plazo.md) | El listado de incidentes pasa a una lista agrupada con el avance del plazo; la tabla queda como segunda vista |
| [ADR-0081](0081-dashboard-de-riesgo.md) | El dashboard pasa a pulso con cumplimiento del plazo, mapa de riesgo, flujo de estados y balance del backlog; los widgets anteriores siguen disponibles |
| [ADR-0082](0082-formulario-guiado.md) | El formulario del incidente guía: prioridad en tarjetas con su plazo, atajos de vencimiento, incidentes parecidos y vista previa |
| [ADR-0083](0083-detalle-con-camino-y-actividad.md) | El detalle muestra el camino de estados, el plazo y el siguiente paso; historial y conversación en una sola actividad |
| [ADR-0084](0084-detalle-con-historial-y-panel-de-chat.md) | Detalle con las 5 entradas recientes junto a la ficha, y el chat y toda la actividad en un panel con pestañas |
| [ADR-0085](0085-avisos-de-coordinacion.md) | Avisos de coordinación (nuevo sin asignar, crítico, reapertura pendiente y vencido) por permiso y una sola vez |
| [ADR-0086](0086-nombres-y-apellidos.md) | Nombres y apellidos por separado, con el nombre completo calculado por la base y las listas ordenadas por apellido |
| [ADR-0087](0087-cuentas-por-rol-desde-el-entorno.md) | Cuentas por rol (evaluación y propias) creadas desde el entorno del servidor, también en producción |
| [ADR-0088](0088-despliegue-detras-del-proxy-existente.md) | En producción Trazo no publica puertos: el Caddy que ya existe en el servidor le pasa su subdominio por una red compartida |
| [ADR-0089](0089-incidentes-de-muestra-en-el-sitio-de-evaluacion.md) | El sitio desplegado para la evaluación arranca con incidentes de muestra, solo con una variable explícita |
| [ADR-0090](0090-una-validacion-por-ruta.md) | Una clase de validación por ruta, sin reglas que cambien según la ruta, para que el contrato OpenAPI diga lo real |
| [ADR-0091](0091-nombres-de-migracion-con-una-sola-fecha.md) | Nombres de migración con una sola fecha y bases existentes puestas al día solas antes de migrar |
