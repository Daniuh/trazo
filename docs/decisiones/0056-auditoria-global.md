---
title: "ADR-0056: Auditoría global: dos registros con permisos separados, alcance por jerarquía y paginación por cursor"
description: "Auditoría global: dos registros con permisos separados, alcance por jerarquía y paginación por cursor"
audience: tecnico
area: decisiones
tags: [producto]
order: 66
---

# ADR-0056: Auditoría global: dos registros con permisos separados, alcance por jerarquía y paginación por cursor

- **Estado:** Aceptada
- **Fecha:** 2026-10-31

## Contexto
Trazo ya guardaba dos registros de solo escritura:

- `incident_activities`: cada cambio de un incidente.
- `admin_actions`: la administración de personas y roles.

Los dos guardan IP y navegador. Hasta ahora solo se veían en el historial de cada incidente, que no muestra esos dos datos. Faltaba la vista global, aprobada para quien supervisa y para la cima. Había que decidir:

- Si los dos registros van juntos o separados.
- Quién ve cada uno, porque un solo `audit.view` lo tenían Supervisor, Administrador y Super admin.
- Qué parte de la administración ve un administrador que no es la cima.
- Cómo se pagina la tabla que más crece.
- Qué rango de fechas se consulta por defecto.

## Alternativas evaluadas
Presentación:
1. **Dos pestañas, una por registro, cada una con su consulta.**
2. Una sola lista que une los dos registros con `UNION ALL`.

Permisos:
1. **Dividir `audit.view` en `audit.incidents.view` (Supervisor y Super admin) y `audit.admin.view` (Administrador y Super admin).**
2. Mantener `audit.view` para los tres.

Alcance de la administración:
1. **La cima ve todo; los demás ven lo propio y lo que toca a una persona o un rol de un nivel menor al suyo.**
2. Todos los que tienen el permiso ven todo.
3. Cada quien ve solo lo que hizo.

Paginación:
1. **Por cursor (fecha e id del último registro entregado), con "Cargar más".**
2. Por número de página, como los otros listados.

Rango por defecto:
1. **Últimos 30 días, decidido en el back y devuelto en la respuesta.**
2. Sin rango: todo el historial.

## Decisión
- **Dos pestañas.** Cada registro tiene columnas y filtros propios: incidente por su código en un caso, persona o rol afectado en el otro. Cada pestaña es una ruta y un endpoint (`GET /audit/incidents` y `GET /audit/admin`). Las acciones de sistema de una fase posterior entrarán en la de administración, porque usan la misma tabla.
- **Dos permisos.** Con `audit.view` el Administrador veía los cambios de todos los incidentes, aunque su rol solo ve los propios. El Supervisor veía quién cambió roles y permisos, que no es su función. La división se hace como la de `users.manage`: el seeder crea los dos permisos nuevos y una migración quita el viejo de roles, excepciones y catálogo. Al ser permisos granulares, cualquiera de los dos se puede conceder a una persona puntual.
- **Alcance por jerarquía.** Es la misma regla de "solo hacia abajo" de la administración. El nivel de una persona es el de su rol más alto hoy. Un rol eliminado se mide por el nivel guardado en el detalle. La regla vive en el dominio (`AdminAuditScope`) y en SQL (`DatabaseAdminAudit`), y una prueba compara las dos registro por registro.
- **Cursor.** El orden es (`created_at`, `id`) de mayor a menor. El cursor es opaco: la fecha y el id en base64. La siguiente tanda empieza justo después del último registro entregado, y se pide una fila de más para saber si queda otra. No se cuenta la tabla.
- **30 días por defecto.** Sin fechas, el back consulta los últimos 30 días. Con solo el final, los 30 anteriores a él. Con solo el inicio, hasta ahora. La respuesta trae el rango aplicado y la pantalla lo muestra.

Los índices nuevos solo se agregan:

- `incident_activities (created_at)` y `(activity_type_id, created_at)`.
- `admin_actions (created_at)`.

InnoDB agrega el id al final de cada índice secundario, así que `(created_at)` ya ordena por el cursor completo.

Medido sobre 100.000 incidentes (523.088 filas de historial), con el mejor de 3 intentos:

| Consulta | Índice | Tiempo |
|---|---|---|
| Sin filtros, 30 días y 365 días | `(created_at)` | < 1 ms |
| Por persona | `(user_id, created_at)` | < 1 ms |
| Por un tipo | `(activity_type_id, created_at)` | < 1 ms |
| Por dos tipos | `(created_at)` | 1,6 ms |
| Por incidente | `(incident_id, created_at)` | 1 ms |
| Con el cursor después de 40.000 filas | `(created_at)` | < 1 ms |

En todas, MySQL recorre el índice hacia atrás y se detiene al juntar la tanda, sin ordenar en memoria.

## Alternativas descartadas
- **Una sola lista con `UNION ALL`:** obliga a ordenar y paginar la unión de dos tablas, una de ellas con cientos de miles de filas. Además mezcla columnas que no se parecen y deja filtros sin sentido en la mitad de las filas.
- **Mantener `audit.view`:** deja los dos huecos de visibilidad descritos arriba.
- **Que todos los que tienen el permiso vean toda la administración:** un Administrador vería lo que el Super admin hace sobre otros administradores, por encima de su nivel.
- **Que cada quien vea solo lo propio:** un Administrador no podría revisar qué se hizo sobre las personas que administra.
- **Paginación por número de página:** para llegar a la página 500, MySQL tiene que recorrer y descartar todo lo anterior. Contar el total también recorre el rango completo. En una tabla que solo crece, las dos cosas se vuelven más lentas cada día. Además, los registros nuevos corren las páginas mientras alguien las revisa.
- **Sin rango por defecto:** la primera consulta recorrería todo el historial, cuando lo que casi siempre se busca es lo reciente.

## Consecuencias
- Cada rol ve solo la auditoría que le corresponde, y el permiso se puede dar a una persona puntual.
- La lista responde igual de rápido con 600 o con 500.000 registros. No hay salto a una página concreta: se avanza con "Cargar más" o se acota con los filtros.
- El alcance de la administración usa el nivel de hoy. Si alguien sube de rol, los registros sobre esa persona dejan de verse para quien ahora queda a su nivel o por debajo, y pasan a ser de quien está por encima.
- El permiso viejo `audit.view` desaparece. Su nombre puede quedar en el detalle de un registro antiguo de "permisos cambiados"; la pantalla lo muestra con su código.
- La IP y el navegador se ven en la auditoría, como estaba previsto, y siguen fuera del historial del incidente.
- Quedan fuera: los eventos de seguridad (van a los logs del sistema), exportar la auditoría (se decide con la exportación de incidentes) y el archivado de registros viejos ([ADR-0014](0014-crecimiento.md)).

## Dónde está en el código
- Back: [`backend/app/Modules/Audit/`](../../backend/app/Modules/Audit/). Las reglas están en [`AuditAccess`](../../backend/app/Modules/Audit/Domain/AuditAccess.php) y [`AdminAuditScope`](../../backend/app/Modules/Audit/Domain/AdminAuditScope.php), y las consultas en [`DatabaseIncidentAudit`](../../backend/app/Modules/Audit/Infrastructure/DatabaseIncidentAudit.php) y [`DatabaseAdminAudit`](../../backend/app/Modules/Audit/Infrastructure/DatabaseAdminAudit.php).
- Migraciones: [`000031` (divide el permiso)](../../backend/database/migrations/2026_11_01_000031_split_audit_view_permission.php) y [`000032` (índices)](../../backend/database/migrations/2026_11_01_000032_add_audit_indexes.php).
- Front: [`frontend/src/modules/audit/`](../../frontend/src/modules/audit/) y [`useCursorList`](../../frontend/src/shared/listing/useCursorList.ts).
- Pruebas: [`AuditTest.php`](../../backend/tests/Feature/Audit/AuditTest.php), [`AuditDomainTest.php`](../../backend/tests/Unit/Audit/AuditDomainTest.php) y [`e2e/audit.spec.ts`](../../frontend/e2e/audit.spec.ts).
