---
title: Mejoras propuestas
description: Lo evaluado y dejado documentado para una siguiente etapa de Trazo.
audience: todos
area: mejoras
order: 3
---

# Mejoras propuestas

Estas mejoras se evaluaron y quedan documentadas para una etapa posterior. No se incluyen en esta versión porque implican tecnologías adicionales, amplían mucho el alcance o solo se justifican con un volumen mayor.

## Seguridad y acceso

| Propuesta | Valor | Por qué no ahora |
|---|---|---|
| Passkeys (huella o rostro) | Acceso sin contraseña y resistente a páginas falsas | Amplía mucho el alcance; la estructura de factores MFA ya lo permite |
| Aprobación push en el celular | Confirmar el acceso con un toque | Requiere una aplicación móvil propia |
| Sesión con cookie httpOnly | Mayor resistencia al robo de sesión por XSS | La prueba evalúa el manejo explícito de token |
| Historial inmutable a nivel de base de datos | Ni un acceso directo a la base podría alterar la auditoría | Es configuración de infraestructura productiva |

## Datos y escala

| Propuesta | Valor | Por qué no ahora |
|---|---|---|
| Réplica de lectura | Separa la carga de listados y dashboard | El volumen actual no lo requiere; el código ya está preparado |
| Tabla resumen del dashboard | Indicadores instantáneos con millones de registros | Igual que la anterior |
| Archivado de registros antiguos | Mantiene ágiles las tablas principales | Igual que la anterior |
| Motor de búsqueda externo (Meilisearch) | Búsqueda tolerante a errores y más rápida a gran escala | Otra pieza de infraestructura |
| Paginación por cursor | Rendimiento constante en páginas muy profundas | La interfaz de tabla espera páginas numeradas |
| Copia de respaldos fuera del servidor | Sobrevivir a una falla total del servidor | El respaldo local cubre el alcance actual |

## Producto

| Propuesta | Valor |
|---|---|
| Comentarios en incidentes | Conversación sobre cada caso |
| Exportación `.xlsx` nativa | Formato con estilos para Excel |
| Exportaciones grandes en segundo plano | Superar el tope de 50.000 filas: el archivo se arma en la cola y se avisa cuando está listo ([ADR-0058](../decisiones/0058-exportacion-en-la-practica.md)) |
| Exportar la auditoría | Llevar los registros a otra herramienta; requiere decidir qué hacer con la IP y el navegador |
| Modo oscuro | Los tokens ya lo permiten |
| Internacionalización | Los mensajes ya viven en un catálogo único |
| Atajos de teclado | Mayor velocidad para usuarios frecuentes |
| PWA y modo sin conexión | Uso con conectividad intermitente |
| Webhooks | Integración con sistemas externos |
| GraphQL | Consultas flexibles para otros clientes |

## Ingeniería

| Propuesta | Valor |
|---|---|
| Ramas `hotfix/*` | Correcciones urgentes sin pasar por todo el flujo |
| Avisos de eventos críticos | Un correo o una marca en el menú cuando aparece un evento crítico, sin tener que entrar a Sistema ([ADR-0059](../decisiones/0059-sistema-en-la-practica.md)) |
| Monitoreo externo (Sentry, Grafana) | Trazas completas de los errores, métricas en el tiempo y alertas fuera de la app |
| Despliegue sin caída (blue/green) | Actualizaciones sin interrupción |
| Commits firmados | Garantía de autoría de cada cambio |
| Pruebas de mutación | Verifican que las pruebas detectan errores reales |
| Pruebas de carga (k6) | Comportamiento con cientos de usuarios simultáneos |
| Regresión visual | Detecta cambios visuales no intencionales |
| Kubernetes | Orquestación para varios servidores |
| Microservicios o BFF | Solo si el producto crece a varios equipos |
