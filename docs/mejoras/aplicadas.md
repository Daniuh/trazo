---
title: Mejoras aplicadas
description: Lo que se construyó por encima de lo solicitado en la prueba y por qué.
audience: todos
area: mejoras
order: 2
---

# Mejoras aplicadas

Este anexo reúne lo que Trazo incorpora más allá de lo solicitado. Ninguna de estas mejoras altera los requisitos obligatorios: los complementan. Cada una enlaza a la decisión que la justifica.

## Seguridad

| Mejora | Por qué | Decisión |
|---|---|---|
| MFA con aplicación de autenticación, política por rol y periodo de gracia | Protege las cuentas aunque una contraseña se filtre | [ADR-0017](../decisiones/0017-mfa-totp.md) |
| Recuperación de contraseña por correo + código del autenticador | Recuperación autónoma sin abrir la puerta a la toma de cuentas | [ADR-0018](../decisiones/0018-recuperacion-contrasena.md) |
| Argon2id, contraseñas de 12+ caracteres y bloqueo progresivo | Estándar actual frente a fuerza bruta y contraseñas débiles | [ADR-0016](../decisiones/0016-contrasenas.md) |
| Cifrado en reposo y secretos fuera del código | Protección ante robo de disco o respaldos | [ADR-0019](../decisiones/0019-cifrado.md) |
| Permisos granulares y cobertura del OWASP Top 10 | Control de acceso fino y verificable | [ADR-0021](../decisiones/0021-permisos-granulares.md) |
| Identificadores públicos ULID | Evita que se adivinen o enumeren registros | [ADR-0010](../decisiones/0010-ulid.md) |
| Correos cifrados en la cola | Ni el código de recuperación ni los datos de las personas quedan legibles en Redis o en los trabajos fallidos | [ADR-0062](../decisiones/0062-correos-cifrados-en-la-cola.md) |

## Datos y rendimiento

| Mejora | Por qué | Decisión |
|---|---|---|
| Catálogos y flujo de estados como datos | Cambiar estados o transiciones sin migraciones | [ADR-0008](../decisiones/0008-catalogos.md) |
| Borrado lógico y auditoría inmutable | Nunca se pierde la trazabilidad | [ADR-0011](../decisiones/0011-borrado-y-restricciones.md) |
| Bloqueo optimista | Ningún cambio se pisa sin aviso | [ADR-0012](../decisiones/0012-bloqueo-optimista.md) |
| Código legible `INC-000123` y fecha de resolución | Más claro para las personas; habilita métricas de tiempo | [ADR-0010](../decisiones/0010-ulid.md) |
| Seeder con volumen configurable e índices validados sobre 100.000+ registros | Demuestra el rendimiento con datos reales | [ADR-0014](../decisiones/0014-crecimiento.md) |
| `Idempotency-Key` al crear | Sin duplicados por doble clic o reintentos | [ADR-0037](../decisiones/0037-idempotencia.md) |
| Carga diferida estricta fuera de producción | Un N+1 nuevo falla en la suite, no en manos del usuario | [ADR-0061](../decisiones/0061-entrega-verificada-desde-cero.md) |

## Producto y experiencia

| Mejora | Por qué | Decisión |
|---|---|---|
| Dashboard personalizable | Cada usuario organiza lo que necesita ver, también con teclado | [ADR-0030](../decisiones/0030-dashboard-personalizable.md), [ADR-0050](../decisiones/0050-editor-del-dashboard.md) |
| Vista previa lateral en monitor | Revisar muchos incidentes sin salir del listado | — |
| Logs del sistema sin ruido y acciones de sistema seguras | Operación diaria sin acceso al servidor | [ADR-0022](../decisiones/0022-logs-relevantes.md), [ADR-0023](../decisiones/0023-acciones-de-sistema.md) |
| Identidad visual propia y accesibilidad WCAG 2.2 AA | Producto profesional y usable por cualquier persona | [ADR-0028](../decisiones/0028-identidad-visual.md) |
| Aviso con "Recargar" cuando una pantalla no se descarga | Tras publicar una versión nueva, nadie queda con un menú que no responde | [ADR-0063](../decisiones/0063-pantalla-que-no-se-descarga.md) |

## Operación y calidad

| Mejora | Por qué | Decisión |
|---|---|---|
| CI/CD con promoción de la misma imagen | Producción ejecuta exactamente lo que se probó | [ADR-0034](../decisiones/0034-ci-cd.md) |
| CI sobre el mismo stack de Docker, Dependabot y Gitleaks antes de cada commit | El CI prueba lo mismo que se ejecuta, y ningún secreto llega al historial público | [ADR-0041](../decisiones/0041-ci-con-el-stack-de-docker.md) |
| Pruebas de arquitectura, autorización, N+1 y E2E en 4 vistas | Las reglas del proyecto se verifican solas | [ADR-0031](../decisiones/0031-estrategia-de-pruebas.md) |
| Escaneo OWASP ZAP como perfil opcional de Docker | Cualquiera puede auditar la seguridad | [ADR-0032](../decisiones/0032-calidad-estatica.md) |
| Respaldo diario cifrado de MySQL, con firma y 7 días de historia | Recuperación ante fallos sin dejar datos en texto plano | [ADR-0064](../decisiones/0064-respaldo-diario-de-mysql.md) |
| Límites de recursos por contenedor | Convivencia con otros servicios en el mismo servidor | [ADR-0026](../decisiones/0026-despliegue.md) |
| Documentación de funciones (phpDocumentor y TypeDoc) revisada en el CI | Un comentario roto se detecta en el PR | [ADR-0065](../decisiones/0065-documentacion-de-funciones.md) |
| OpenAPI generada desde el código | La referencia de la API nunca se desactualiza | [ADR-0006](../decisiones/0006-contrato-api.md) |
