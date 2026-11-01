---
title: Arquitectura
description: Visión general del sistema, sus capas y sus flujos principales.
audience: tecnico
area: arquitectura
order: 1
---

# Arquitectura

Describe cómo está construido Trazo: una SPA en Vue que consume una API REST en Laravel, ambas detrás de un gateway Nginx. Incluye los diagramas del sistema, la organización por capas y los flujos técnicos más importantes.

| Página | Contenido |
|---|---|
| [Vista general](vista-general.md) | Diagramas C4 de contexto y de contenedores, el camino de una petición y la red de Docker |
| [Componentes](componentes.md) | Diagramas C4 de los módulos de la API y de la SPA, y cómo se comunican |
| [Capas del backend](capas.md) | Qué contiene cada capa de un módulo y qué puede usar |
| [Flujos principales](flujos.md) | Ingreso con verificación en dos pasos, cambio de estado en tiempo real y exportación |
