---
title: De una rama a producción (CI)
description: El camino de un cambio desde la rama de trabajo hasta main, con los hooks locales, los trabajos del CI y las fusiones entre dev, stage y main.
audience: tecnico
area: flujos
module: operacion
order: 4
---

# De una rama a producción (CI)

Hay cuatro niveles de ramas: la de trabajo (`feature/`, `fix/`, `docs/`, `chore/`), `dev` para pruebas, `stage` para preproducción y `main` para producción. Nada llega a `dev`, `stage` ni `main` sin pasar el CI, y las correcciones urgentes siguen el mismo camino.

```flujo
carriles: [Persona, Hooks locales, GitHub, CI, Ramas]
pasos:
  - id: commit
    carril: Persona
    tipo: inicio
    texto: Hace un commit en su rama de trabajo
    detalle: 'Ejemplo: `feature/back-incidents-crud`, creada desde `dev`.'
    va: hooks
  - id: hooks
    carril: Hooks locales
    tipo: decision
    texto: ¿Pasan los hooks de Lefthook?
    detalle: 'Antes del commit, sobre lo que cambió: Pint (dentro del contenedor), ESLint y Prettier, Stylelint y búsqueda de secretos con Gitleaks. Al escribir el mensaje: Conventional Commits (tipo y ámbito en inglés, descripción en español, máximo 100 caracteres).'
    si: pr
    no: corrige
  - id: corrige
    carril: Persona
    tipo: error
    texto: Corrige y vuelve a intentar
  - id: pr
    carril: GitHub
    texto: Abre un PR hacia dev
    detalle: Con la plantilla de PR, que incluye la casilla de documentación.
    va: ci
  - id: ci
    carril: CI
    tipo: decision
    texto: ¿Pasan todos los trabajos?
    detalle: 'Título del PR, enlaces entre documentación y código, documentación de funciones (TypeDoc y phpDocumentor), frontend (tipos, lint, estilos, formato, pruebas con cobertura y build), backend (Pint, Larastan nivel 8 y Pest con cobertura dentro del stack de Docker), E2E con Playwright y axe en 4 vistas, imágenes de producción y seguridad (Gitleaks y auditoría de dependencias). Acciones fijadas por hash y permisos de solo lectura.'
    si: dev
    no: bloquea
  - id: bloquea
    carril: GitHub
    tipo: error
    texto: El PR no se puede fusionar
  - id: dev
    carril: Ramas
    texto: Squash a dev
    detalle: El título del PR se vuelve el mensaje del commit.
    va: stage
  - id: stage
    carril: Ramas
    texto: Merge de dev a stage
    detalle: Con merge commit, después de que el CI de `dev` pase.
    va: main
  - id: main
    carril: Ramas
    texto: Merge de stage a main
    va: despliega
  - id: despliega
    carril: Persona
    tipo: fin
    texto: Actualiza el servidor
    detalle: Ver "Despliegue y actualización". La versión de `main` sigue SemVer.
```

Dependabot propone actualizaciones cada semana hacia `dev`; en las imágenes de Docker solo propone parches dentro de la versión fijada.

## Dónde está en el código

- [`ci.yml`](../../../.github/workflows/ci.yml): los trabajos del CI.
- [`lefthook.yml`](../../../lefthook.yml), [`check-commit-msg.mjs`](../../../scripts/git/check-commit-msg.mjs) y [`scan-secrets.mjs`](../../../scripts/git/scan-secrets.mjs): hooks locales.
- [`check-links.mjs`](../../../scripts/docs/check-links.mjs): enlaces entre documentación y código.
- Explicación: [Flujo de trabajo](../../operacion/flujo-de-trabajo.md) e [Integración continua](../../operacion/integracion-continua.md).
