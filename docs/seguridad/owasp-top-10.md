---
title: OWASP Top 10 (2025)
description: Cómo cubre Trazo cada riesgo del OWASP Top 10 de 2025, con el control que lo atiende y la prueba que lo demuestra.
audience: tecnico
area: seguridad
order: 2
---

# OWASP Top 10 (2025)

Para cada categoría: qué hace Trazo y dónde está la prueba que lo demuestra. El escaneo con [ZAP](zap.md) completa la revisión desde fuera.

## A01 — Control de acceso roto

- **El back es la única autoridad.** Permisos granulares (`módulo.acción[.alcance]`), revisados en los casos de uso. Las reglas preguntan por permisos, nunca por nombres de rol.
- **Visibilidad por persona.** Un incidente que no se puede ver responde 404, igual que uno que no existe. El listado, la exportación, el dashboard y el tiempo real usan la misma regla, traducida a SQL.
- **Jerarquía.** Solo se administra hacia abajo; nadie se cambia a sí mismo rol, permisos, estado ni acceso; siempre queda un Super admin activo.
- **Peticiones de administración:** revisan el permiso antes de validar, para no revelar si un correo existe.
- **Canales de tiempo real:** firmados por la API con el token de la sesión.
- **CORS cerrado.**
- **Pruebas:**
  - Paridad de visibilidad entre el detalle y el SQL, persona por persona (`ListIncidentsTest`).
  - Exportación igual al listado (`ExportIncidentsTest`).
  - Matriz de permisos, `UserAccessTest`, `RoleAdminTest`, `RealtimeTest` y `CorsTest`.
  - 403 en cada ruta de sistema para el Administrador (`SystemTest`).

## A02 — Configuración de seguridad incorrecta

- Cabeceras de seguridad, CSP estricta en producción, HSTS, `server_tokens off` y CORS cerrado ([Cabeceras](cabeceras.md)).
- Contenedores sin root y sin capacidades de Linux (`no-new-privileges`, `cap_drop: ALL`), versiones fijas, solo el puerto del gateway publicado, MySQL y Redis en una red interna sin salida.
- `APP_DEBUG` apagado en producción; errores sin detalles técnicos (RFC 9457 con código propio).
- Rutas de desarrollo fuera de producción: la guía visual solo existe en desarrollo; los comandos `trazo:reset-limits` y `trazo:dev-token` ni se registran en producción.
- **Pruebas:** `ApiErrorFormatTest` comprueba que un 500 no revela nada. ZAP pasivo y activo. La E2E de accesibilidad falla ante cualquier violación de CSP.

## A03 — Fallas en la cadena de suministro

- Dependencias con versión fija y archivos de bloqueo (`composer.lock`, `package-lock.json`).
- `composer audit` y `npm audit` bloquean el CI (las dependencias de desarrollo del front solo avisan).
- Dependabot semanal.
- Acciones de GitHub fijadas por hash, con permisos de solo lectura.
- Sin scripts de CDN: fuentes autoalojadas, íconos propios y la interfaz de Scramble apagada por cargar scripts externos.
- Gitleaks en el pre-commit y en el CI.

## A04 — Fallas criptográficas

- Contraseñas con Argon2id.
- Secreto del MFA cifrado con la clave de la app.
- Códigos de recuperación con SHA-256.
- Códigos de recuperación de contraseña con HMAC-SHA256.
- Correos en la cola cifrados con la clave de la app: el código de recuperación, los nombres y las direcciones nunca quedan legibles en Redis ni en `failed_jobs` (lo exige una prueba de arquitectura).
- Tokens Sanctum guardados con hash.
- Cifrado en reposo de InnoDB.
- TLS en producción, con HSTS.
- La `APP_KEY` de las pruebas se genera al azar en cada ejecución: el repositorio es público.

## A05 — Inyección

- **SQL:** consultas por el constructor de Laravel con parámetros. La búsqueda de texto quita los operadores del modo booleano antes de armar la expresión, y el orden sale de una lista blanca.
- **XSS:** Vue escapa todo, `v-html` está prohibido (ESLint) y la CSP no permite scripts en línea.
- **Fórmulas en CSV:** una celda que empieza por `=`, `+`, `-` o `@` sale como texto (`CsvDownload::cell`).
- **Validación estricta de entrada** con un código por regla.
- **Pruebas:** `SearchIncidentsTest` (operadores de búsqueda), `ExportIncidentsTest` (fórmulas) y el escaneo activo de ZAP.

## A06 — Diseño inseguro

- Reglas de negocio en el dominio, separadas de la infraestructura.
- Bloqueo optimista con `version`, para que un cambio no pise a otro.
- `Idempotency-Key` al crear.
- Límites de frecuencia: login y MFA, 5 por minuto; recuperación, 3 cada 15 minutos; exportación y acciones de sistema, 5 por minuto; API, 120 por minuto.
- Tope de 50.000 filas en la exportación.
- Mensajes del login que no revelan si el correo existe.

## A07 — Fallas de autenticación

- **Contraseñas:** de 12 a 128 caracteres; se rechazan las predecibles (repetidas, secuenciales o con datos de la persona o la marca) y las filtradas (lista local y Have I Been Pwned con k-anonimato).
- **Bloqueo progresivo** por correo, exista o no la cuenta, con hash de relleno para igualar tiempos.
- **MFA (TOTP)** para todos:
  - El token de un ingreso a medias solo sirve para el segundo paso.
  - Un código no se puede repetir.
  - Hay 8 códigos de recuperación y un límite de intentos.
- **Sesión:** máximo 8 horas y cierre tras 1 hora sin uso. Si se desactiva la cuenta, se cierran todas sus sesiones al instante.
- **Reautenticación** con el código de la aplicación antes de las acciones sensibles.
- **Pruebas:** `LockoutTest`, las pruebas de MFA, contraseña y sesión del módulo Auth, y `mfa.spec.ts` y `password.spec.ts` en la E2E.

## A08 — Fallas de integridad del software o de los datos

- Imágenes construidas en el CI desde el código revisado.
- El historial de incidentes y el registro de administración son de solo escritura, con claves foráneas reales y sin relaciones polimórficas.
- Bloqueo optimista e idempotencia.
- Los avisos de tiempo real no llevan datos: el front siempre vuelve a consultar la API, que decide qué puede ver cada uno.

## A09 — Fallas de registro y alertas

- **Auditoría** de cada cambio de incidente y de cada acción administrativa, de exportación y de sistema, con IP y navegador.
- **Eventos del sistema** agrupados por huella: errores 500, jobs fallidos, servicios caídos, bloqueos de ingreso, códigos MFA o de recuperación agotados, reautenticación bloqueada y accesos denegados repetidos. Se ven en Sistema, sin datos sensibles.
- Sin ruido: las reglas de negocio que se cumplen no van al log.
- **Pruebas:** `SystemTest` (fuentes de eventos) y `AuditTest`.

## A10 — Mal manejo de condiciones excepcionales

- **Un único punto traduce los errores** (`ApiExceptionRenderer`): RFC 9457 con código propio y nunca detalles técnicos. Un 500 queda como evento con su código de soporte.
- **Registrar un evento nunca hace fallar la petición.** Si Redis no responde, el mantenimiento se considera apagado.
- **El panel de salud mide cada pieza** sin lanzar errores.
- **En el front**, un 401 solo cierra la sesión con `UNAUTHENTICATED`; una red caída o un mantenimiento muestran su pantalla sin perder la sesión; cada error tiene su mensaje claro.
- **Pruebas:** `ApiErrorFormatTest`, `SystemTest` (error de base de datos sin la consulta) y las pruebas del cliente HTTP.

## Dónde está en el código

- Cabeceras y CSP: [`docker/nginx/snippets/security-headers.conf`](../../docker/nginx/snippets/security-headers.conf)
- Errores: [`ApiExceptionRenderer`](../../backend/app/Modules/Shared/Http/Errors/ApiExceptionRenderer.php)
- Reglas de acceso: [`IncidentAccess`](../../backend/app/Modules/Incidents/Domain/IncidentAccess.php), [`UserAccess`](../../backend/app/Modules/Users/Domain/UserAccess.php) y [`VisibilityScope`](../../backend/app/Modules/Incidents/Domain/VisibilityScope.php)
- Eventos: [Módulo System](../backend/system.md)
- Escaneo: [ZAP](zap.md)
