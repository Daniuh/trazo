---
title: Flujo de trabajo con Git
description: Ramas, commits, Pull Requests y hooks del repositorio.
audience: tecnico
area: operacion
module: git
order: 2
---

# Flujo de trabajo con Git

## Ramas

| Rama | Uso | Despliegue |
|---|---|---|
| `feature/`, `fix/`, `docs/`, `chore/` + área y tema | Trabajo de cada tarea | No |
| `dev` | Integración y QA | No |
| `stage` | Preproducción | Sí |
| `main` | Producción | Sí |

```mermaid
flowchart LR
  W[feature/... fix/... docs/... chore/...] -- PR + squash --> D[dev]
  D -- PR + merge --> S[stage]
  S -- PR + merge --> M[main]
```

- Todo cambio llega por Pull Request; no hay push directo a `dev`, `stage` ni `main`.
- Ningún PR se fusiona si el CI falla, y no se saltan etapas. Las correcciones urgentes siguen el mismo camino.
- Ejemplos de nombres: `feature/back-incidents-crud`, `docs/front-design-tokens`.

## Commits

Se usa [Conventional Commits](https://www.conventionalcommits.org/es/): tipo y ámbito en inglés, descripción en español.

```
feat(incidents): agregar filtro por rango de vencimiento
fix(auth): corregir bloqueo progresivo tras reinicio de contraseña
docs(adr): registrar decisión de tablas catálogo
```

Tipos permitidos: `feat`, `fix`, `docs`, `style`, `refactor`, `perf`, `test`, `build`, `ci`, `chore`, `revert`.

## Hooks locales

Al instalar las dependencias de la raíz (`npm install`) se activan los hooks de [Lefthook](https://lefthook.dev). Hoy validan el formato del mensaje de commit; cada app suma sus revisiones de formato y lint a medida que se construye.

## Versiones

Cada llegada a `main` se etiqueta con [SemVer](https://semver.org/lang/es/) y el CHANGELOG se genera a partir de los commits.

## Dónde está en el código

- [`lefthook.yml`](../../lefthook.yml): hooks configurados.
- [`scripts/git/check-commit-msg.mjs`](../../scripts/git/check-commit-msg.mjs): validación del mensaje de commit.
- [`.github/pull_request_template.md`](../../.github/pull_request_template.md): checklist de cada PR.
- Decisión: [ADR-0033](../decisiones/0033-flujo-git.md).
