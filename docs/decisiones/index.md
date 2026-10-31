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
