---
title: Flujos principales
description: Secuencias del ingreso con verificación en dos pasos, del cambio de estado con aviso en tiempo real y de la exportación.
audience: tecnico
area: arquitectura
order: 5
---

# Flujos principales

Tres recorridos que cruzan varias piezas del sistema. El detalle de cada endpoint está en el documento de su módulo.

## Ingreso con verificación en dos pasos

```mermaid
sequenceDiagram
  autonumber
  actor P as Persona
  participant S as SPA
  participant A as API (Auth)
  participant R as Redis
  participant D as MySQL

  P->>S: Correo y contraseña
  S->>A: POST /auth/login
  A->>R: ¿Correo bloqueado? (huella SHA-256 del correo)
  A->>D: Busca la cuenta y compara con Argon2id (hash de relleno si no existe)
  alt Credenciales incorrectas
    A->>R: Suma un intento (5→1 min, 10→5 min, 15→15 min, 20+→1 h)
    A-->>S: 401 INVALID_CREDENTIALS (no revela si el correo existe)
  else Tiene MFA activo
    A-->>S: 200 status=mfa_verify + token a medias (habilidad "mfa", 5 min)
    P->>S: Código de la app de autenticación
    S->>A: POST /auth/mfa/verify con el token a medias
    A->>D: Valida el TOTP (±1 ventana) y que ese paso no se haya usado
    A->>D: Revoca el token a medias y emite el de sesión
    A-->>S: 200 status=complete + token de sesión
  else Sin MFA y le queda el ingreso de gracia
    A-->>S: 200 status=setup_suggested + token de sesión
  else Sin MFA y sin gracia
    A-->>S: 200 status=mfa_setup + token a medias
  end
  S->>S: Guarda el token de sesión (el token a medias solo vive en memoria)
```

- El token a medias solo sirve para las rutas `auth.mfa.challenge.*` y para `logout`. El callback de Sanctum lo rechaza en cualquier otra ruta.
- Cinco códigos fallidos en un mismo ingreso revocan el token a medias (`MFA_TOO_MANY_ATTEMPTS`) y dejan un evento del sistema.
- La sesión dura 8 horas como máximo y se cierra tras 1 hora sin uso ([ADR-0042](../decisiones/0042-sesion-y-bloqueo.md), [ADR-0051](../decisiones/0051-mfa-en-la-practica.md)).

## Cambio de estado con aviso en tiempo real

```mermaid
sequenceDiagram
  autonumber
  actor P as Quien cambia
  participant S as SPA de quien cambia
  participant A as API (Incidents)
  participant D as MySQL
  participant Q as Redis (cola)
  participant W as worker
  participant V as Reverb
  participant O as SPA de otra persona

  P->>S: Cambiar estado → En progreso
  S->>A: PUT /incidents/{id}/status {status, version} + X-Socket-ID
  A->>A: IncidentAccess: ¿puede? ¿la transición está permitida?
  A->>D: UPDATE … WHERE id = ? AND version = ? (bloqueo optimista)
  alt Otra persona ya lo cambió
    A-->>S: 409 VERSION_CONFLICT
  else Cambio aplicado
    A->>D: Historial en incident_activities (misma transacción)
    A-->>S: 200 con el incidente, sus actions y next_statuses
    A->>Q: Después del commit: IncidentChanged → aviso por canal
    Q->>W: Toma el trabajo
    W->>V: incident.changed {id, change} a incidents.all, al personal de quien creó y de quien está asignado
    V-->>O: Aviso sin datos (a quien hizo el cambio no le llega: toOthers)
    O->>A: GET /incidents/{id}
    alt Ya no lo puede ver
      A-->>O: 404 y la pantalla lo quita
    else Lo puede ver
      A-->>O: 200 y la pantalla se actualiza
    end
  end
```

- Los avisos no llevan datos: solo el id y el tipo de cambio. Cada pantalla vuelve a pedir el recurso a la API, que aplica los permisos de quien mira ([ADR-0057](../decisiones/0057-tiempo-real-en-la-practica.md)).
- El mismo `IncidentChanged` sube la generación de la caché del dashboard, así que las cifras nuevas se calculan en la siguiente consulta.
- Si Reverb o el worker están caídos, el cambio igual se guarda. La otra persona ve el dato actualizado al recargar, y `system:check` deja un evento del sistema.

## Exportación a CSV

```mermaid
sequenceDiagram
  autonumber
  actor P as Supervisor
  participant S as SPA
  participant A as API (Incidents)
  participant D as MySQL

  P->>S: Exportar (con los filtros que tiene el listado)
  S->>A: GET /incidents/export?filter[...]&sort=...
  A->>A: Permiso incidents.export y límite de 5 por minuto
  A->>D: COUNT con la misma visibilidad y filtros del listado
  alt Más de 50.000 filas
    A-->>S: 422 EXPORT_TOO_LARGE {total, max}
  else Dentro del tope
    A->>D: admin_actions: incidents_exported con filas y criterios
    A-->>S: CSV en streaming (lotes de 1.000, memoria estable)
    S->>P: Descarga incidentes-AAAA-MM-DD-HHMM.csv
  end
```

- El archivo sale listo para Excel en español: BOM, separador `;` y fechas en la zona horaria del negocio.
- Las celdas que empiezan con `=`, `+`, `-`, `@`, tabulador o salto de línea llevan un apóstrofo al inicio, para que la hoja de cálculo no las ejecute como fórmula ([ADR-0058](../decisiones/0058-exportacion-en-la-practica.md)).

## Dónde está en el código

- Ingreso: [`AuthController`](../../backend/app/Modules/Auth/Http/AuthController.php), [`MfaController`](../../backend/app/Modules/Auth/Http/MfaController.php) y [`VerifyMfa`](../../backend/app/Modules/Auth/Application/Mfa/VerifyMfa.php).
- Cambio de estado: [`IncidentWorkflowController`](../../backend/app/Modules/Incidents/Http/IncidentWorkflowController.php), [`ChangeIncidentStatus`](../../backend/app/Modules/Incidents/Application/ChangeIncidentStatus.php) y [`EloquentIncidentRepository`](../../backend/app/Modules/Incidents/Infrastructure/EloquentIncidentRepository.php).
- Tiempo real: [`backend/app/Modules/Realtime/`](../../backend/app/Modules/Realtime/) y [`frontend/src/modules/realtime/`](../../frontend/src/modules/realtime/).
- Exportación: [`IncidentController`](../../backend/app/Modules/Incidents/Http/IncidentController.php), [`IncidentCsv`](../../backend/app/Modules/Incidents/Http/IncidentCsv.php) y [`CsvDownload`](../../backend/app/Modules/Shared/Http/CsvDownload.php).
