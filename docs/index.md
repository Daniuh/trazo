---
title: Documentación de Trazo
description: Punto de entrada a la documentación funcional y técnica de Trazo.
audience: todos
area: general
order: 1
---

# Documentación de Trazo

Trazo es un sistema de gestión de solicitudes e incidentes. Esta documentación está organizada por público: quien necesita entender el producto encuentra lo suyo sin pasar por detalles técnicos, y quien lo construye u opera encuentra el detalle completo.

## Para soporte y directivos

| Sección | Contenido |
|---|---|
| [Producto](producto/index.md) | Qué es Trazo, roles, ciclo de vida de un incidente y guías de uso |
| [Soporte](soporte/index.md) | Catálogo de errores y preguntas frecuentes |

## Para el equipo técnico

| Sección | Contenido |
|---|---|
| [Arquitectura](arquitectura/index.md) | Visión general, capas y flujos |
| [Backend](backend/index.md) | Módulos de la API |
| [Frontend](frontend/index.md) | Módulos de la SPA y sistema de diseño |
| [Base de datos](base-de-datos/index.md) | Modelo, diccionario de datos y crecimiento |
| [API](api/index.md) | Convenciones y referencia |
| [Seguridad](seguridad/index.md) | Autenticación, OWASP y logs |
| [Operación](operacion/index.md) | Instalación, Docker, despliegue y CI/CD |
| [Calidad](calidad/index.md) | Estrategia de pruebas |
| [Decisiones](decisiones/index.md) | Registro de decisiones (ADR) |
| [Mejoras](mejoras/index.md) | Lo agregado más allá de lo pedido y las propuestas a futuro |

## Convenciones de esta documentación

- Cada página empieza con metadatos (`title`, `description`, `audience`, `area`, `module`, `order`) que ordenan y filtran el contenido.
- Las páginas técnicas incluyen la sección **Dónde está en el código**, con enlaces a los archivos que implementan lo descrito.
- Los diagramas se escriben en Mermaid, junto al texto, para que se actualicen en el mismo cambio que el código.
- A nivel de función, la referencia vive en el propio código (PHPDoc y TSDoc) y se genera de forma automática.
