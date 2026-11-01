---
title: Consultar la auditoría
description: Las dos pestañas de la auditoría global, quién ve cada una, el alcance por jerarquía y la paginación por cursor.
audience: tecnico
area: flujos
module: audit
order: 1
---

# Consultar la auditoría

La pantalla "Auditoría" tiene dos pestañas que nunca se mezclan en una misma consulta:

- **Incidentes** (`audit.incidents.view`, Supervisor y Super admin): todo el historial de `incident_activities`, incluidos los incidentes eliminados.
- **Administración** (`audit.admin.view`, Administrador y Super admin): `admin_actions`, es decir, cuentas, roles, exportaciones y acciones de sistema.

Es de solo lectura: nadie puede editar ni borrar un registro.

```flujo
carriles: [Persona, Navegador, API, Base de datos]
pasos:
  - id: abre
    carril: Persona
    tipo: inicio
    texto: Abre Auditoría y filtra
    detalle: 'Rango de fechas (30 días por defecto), quién hizo el cambio, tipo, incidente (`26-0142`) o persona o rol afectado. Los filtros quedan en la dirección. "Ver su actividad" en la ficha de una persona abre esta pantalla ya filtrada.'
    va: pestana
  - id: pestana
    carril: Navegador
    tipo: decision
    texto: ¿Qué pestaña puede ver?
    detalle: 'Si entra a `/auditoria` sin elegir, va a la primera que su permiso le deja ver.'
    va:
      - { a: incidentes, etiqueta: Incidentes }
      - { a: admin, etiqueta: Administración }
  - id: incidentes
    carril: API
    tipo: decision
    texto: ¿Tiene audit.incidents.view?
    si: periodo
    no: e403
  - id: e403
    carril: API
    tipo: error
    texto: Responde 403
  - id: admin
    carril: API
    tipo: decision
    texto: ¿Tiene audit.admin.view?
    si: alcance
    no: e403b
  - id: e403b
    carril: API
    tipo: error
    texto: Responde 403
  - id: alcance
    carril: API
    texto: Aplica el alcance por jerarquía
    detalle: 'El Super admin ve todo. Los demás ven sus propias acciones y las que tocan a una persona o un rol de un nivel menor al suyo (nivel de hoy).'
    va: periodo
  - id: periodo
    carril: API
    texto: Fija el rango de fechas
    detalle: 'Sin fechas, los últimos 30 días; con solo el final, los 30 anteriores; con solo el inicio, hasta ahora. Ninguna consulta recorre toda la tabla sin querer. El rango aplicado vuelve en `meta`.'
    va: consulta
  - id: consulta
    carril: Base de datos
    texto: Lee una página por cursor
    detalle: 'Cursor = fecha + id (en base64), y una fila de más para saber si hay otra página, sin contar el total. Medido sobre unas 523.000 filas: menos de 2 ms.'
    va: muestra
  - id: muestra
    carril: Navegador
    tipo: fin
    texto: Muestra filas y "Cargar más"
    detalle: 'Fecha, quién, qué, sobre qué y resumen del cambio. El detalle muestra todo, más IP y navegador, con enlaces al incidente o a la ficha si quien mira puede abrirlos.'
```

## Qué ve la persona en cada error

| Código | HTTP | Cuándo | Mensaje en pantalla |
|---|---|---|---|
| `INVALID_CURSOR` | 422 | El cursor de "Cargar más" está dañado | No pudimos seguir cargando la lista. Vuelve a cargarla desde el principio. |
| `INCIDENT_CODE_FORMAT` | 422 | El filtro de incidente no tiene forma de código | Escribe el código del incidente con su año, por ejemplo 26-0142. |
| `FORBIDDEN_ACTION` | 403 | Sin el permiso de esa pestaña | No tienes permiso para hacer esta acción. Si crees que deberías tenerlo, habla con un administrador. |

## Dónde está en el código

- [`ListIncidentAudit`](../../../backend/app/Modules/Audit/Application/ListIncidentAudit.php) y [`ListAdminAudit`](../../../backend/app/Modules/Audit/Application/ListAdminAudit.php).
- [`AuditAccess`](../../../backend/app/Modules/Audit/Domain/AuditAccess.php), [`AdminAuditScope`](../../../backend/app/Modules/Audit/Domain/AdminAuditScope.php) y [`AuditPeriod`](../../../backend/app/Modules/Audit/Domain/AuditPeriod.php).
- [`AuditWindow`](../../../backend/app/Modules/Audit/Infrastructure/AuditWindow.php), [`DatabaseIncidentAudit`](../../../backend/app/Modules/Audit/Infrastructure/DatabaseIncidentAudit.php) y [`DatabaseAdminAudit`](../../../backend/app/Modules/Audit/Infrastructure/DatabaseAdminAudit.php).
- [`useAudit`](../../../frontend/src/modules/audit/application/useAudit.ts) y [`AuditPage`](../../../frontend/src/modules/audit/presentation/AuditPage.vue).
- Explicación: [Módulo Audit](../../backend/audit.md).
