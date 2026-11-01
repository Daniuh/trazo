---
title: Avisos de coordinación
description: Los avisos para quienes reparten el trabajo, por permiso y no por rol, cuando nace un incidente sin asignar o crítico y cuando piden una reapertura.
audience: tecnico
area: flujos
module: notifications
order: 5
---

# Avisos de coordinación

Algunos hechos importan a quienes reparten el trabajo, no solo a los participantes del incidente. Quiénes son se decide por permiso, no por rol, y solo cuentan las cuentas activas:

- **Quienes pueden asignar** (`incidents.assign`): reciben "nuevo sin asignar", "crítico creado" y "vencido".
- **Quienes pueden aprobar reaperturas** (`incidents.reopen.approve`): reciben "reapertura pendiente".

Los agentes no reciben "nuevo sin asignar". Cada persona recibe un solo aviso por hecho, el más importante para ella: crítico gana a asignado, y asignado gana a sin asignar.

```flujo
carriles: [API, Cola, Base de datos, Coordinación]
pasos:
  - id: creado
    carril: API
    tipo: inicio
    texto: Se registra "created" o "reopen_requested"
    va: busca
  - id: busca
    carril: Base de datos
    texto: Busca a quienes coordinan
    detalle: '`CoordinatorDirectory` con la regla de permisos efectivos en SQL (`PermissionSql`): rol + concedidos − quitados, solo cuentas activas. Solo se consulta para estos dos registros.'
    va: tipo
  - id: tipo
    carril: Cola
    tipo: decision
    texto: ¿Qué hecho es?
    va:
      - { a: critico, etiqueta: Creado }
      - { a: reapertura, etiqueta: Reapertura }
  - id: critico
    carril: Cola
    tipo: decision
    texto: ¿Prioridad crítica?
    si: avcritico
    no: sinasignar
  - id: avcritico
    carril: Coordinación
    tipo: fin
    texto: '"Crítico creado" a quienes asignan y al asignado'
  - id: sinasignar
    carril: Cola
    tipo: decision
    texto: ¿Quedó sin asignar?
    si: avsin
    no: avasig
  - id: avsin
    carril: Coordinación
    tipo: fin
    texto: '"Nuevo sin asignar" a quienes asignan'
  - id: avasig
    carril: Coordinación
    tipo: fin
    texto: '"Asignado" solo a quien lo recibe'
  - id: reapertura
    carril: Coordinación
    tipo: fin
    texto: '"Reapertura pendiente" a quienes aprueban'
```

En todos los casos se excluye a quien hizo el cambio: quien crea un crítico y además puede asignar no se avisa a sí mismo. Los avisos de vencidos siguen su propio camino: [Vencidos cada 5 minutos](vencidos.md).

## Dónde está en el código

- [`Recipients`](../../../backend/app/Modules/Notifications/Domain/Recipients.php) (`forCreation`) y [`Coordinators`](../../../backend/app/Modules/Notifications/Domain/Coordinators.php).
- [`DatabaseCoordinatorDirectory`](../../../backend/app/Modules/Notifications/Infrastructure/DatabaseCoordinatorDirectory.php) y [`PermissionSql`](../../../backend/app/Modules/Users/Infrastructure/PermissionSql.php).
- [`NotifyParticipants`](../../../backend/app/Modules/Notifications/Application/NotifyParticipants.php).
- Explicación: [Módulo Notifications](../../backend/notifications.md).
