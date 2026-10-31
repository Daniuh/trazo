---
title: "ADR-0031: Estrategia de pruebas"
description: "Estrategia de pruebas"
audience: tecnico
area: decisiones
order: 41
---

# ADR-0031: Estrategia de pruebas

- **Estado:** Aceptada
- **Fecha:** 2026-10-21

## Contexto
Se valoran los tests con Pest o PHPUnit, el manejo de errores, las validaciones y que todo sea ejecutable. Además, las reglas del proyecto (capas, permisos, responsive y tokens) deben verificarse de forma automática.

## Alternativas evaluadas
- PHPUnit o Pest en el backend.
- SQLite o MySQL para las pruebas.
- Solo pruebas unitarias, o una pirámide de pruebas completa.

## Decisión
**Backend (Pest), contra MySQL real:**
- Pruebas unitarias de dominio.
- Pruebas de API.
- Pruebas de autorización, que cubren toda la matriz de permisos.
- Pruebas de arquitectura, que verifican que se respeten las capas.
- Pruebas de N+1, que limitan la cantidad de consultas.

**Frontend:**
- Vitest para las pruebas unitarias y de componentes.
- Playwright para los flujos críticos en las 4 vistas, con axe-core para la accesibilidad.

**Cobertura:** al menos 90 % en dominio y aplicación, y 80 % en general.

## Alternativas descartadas
- **PHPUnit:** igual de válido, pero más verboso. Además, Pest incluye pruebas de arquitectura.
- **SQLite:** FULLTEXT, CHECK y las FK se comportan distinto que en MySQL, y las pruebas darían una falsa confianza.

## Consecuencias
- Las reglas del proyecto dejan de depender de la disciplina de cada persona: si alguien rompe una, una prueba falla.
