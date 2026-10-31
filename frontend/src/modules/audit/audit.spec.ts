// Pruebas de la auditoría en el front: criterios en la URL por pestaña y textos de cada cambio.
// Documentado en docs/frontend/audit.md
import { describe, expect, it } from 'vitest'
import { fromAuditQuery, toAuditQuery } from './application/auditQuery'
import {
  activeAuditFilterCount,
  EMPTY_CRITERIA,
  type AdminAuditEntry,
  type AuditCatalog,
  type IncidentAuditEntry,
} from './domain/audit'
import { changeRows, changeSummary, targetText } from './presentation/auditTexts'

const catalog: AuditCatalog = {
  incidentTypes: null,
  adminTypes: [],
  roles: [
    { code: 'agent', name: 'Agente' },
    { code: 'supervisor', name: 'Supervisor' },
  ],
  permissions: [{ code: 'incidents.export', name: 'Exportar incidentes' }],
  people: [],
}

const base = {
  id: 1,
  createdAt: '2026-10-24T15:00:00+00:00',
  user: { id: 'u1', name: 'Laura Gómez' },
  ipAddress: '203.0.113.7',
  userAgent: null,
}

function incidentEntry(changes: IncidentAuditEntry['changes']): IncidentAuditEntry {
  return {
    ...base,
    tab: 'incidents',
    type: { code: 'updated', name: 'Editado' },
    incident: { id: 'i1', code: 'TZ-26-0123', title: 'Impresora', deleted: false },
    changes,
    note: null,
    links: { incident: true },
  }
}

function adminEntry(code: string, details: AdminAuditEntry['details'], extra: Partial<AdminAuditEntry> = {}) {
  return {
    ...base,
    tab: 'admin',
    type: { code, name: code },
    targetUser: null,
    targetRole: null,
    details,
    links: { user: false, role: false },
    ...extra,
  } satisfies AdminAuditEntry
}

describe('criterios en la URL', () => {
  it('el filtro de "sobre qué" usa la clave de cada pestaña y el rol solo existe en administración', () => {
    const criteria = { ...EMPTY_CRITERIA, actor: 'u1', types: ['created', 'updated'], target: '26-7', role: 'agent' }

    expect(toAuditQuery(criteria, 'incidents')).toEqual({ actor: 'u1', type: 'created,updated', incident: '26-7' })
    expect(toAuditQuery(criteria, 'admin')).toEqual({
      actor: 'u1',
      type: 'created,updated',
      user: '26-7',
      role: 'agent',
    })
    expect(fromAuditQuery({ incident: '26-7', role: 'agent' }, 'incidents')).toMatchObject({
      target: '26-7',
      role: null,
    })
    expect(fromAuditQuery({ user: 'u2', role: 'agent' }, 'admin')).toMatchObject({ target: 'u2', role: 'agent' })
  })

  it('cuenta el rango como un solo filtro', () => {
    expect(activeAuditFilterCount({ ...EMPTY_CRITERIA, from: 'a', to: 'b', types: ['x'] })).toBe(2)
    expect(activeAuditFilterCount(EMPTY_CRITERIA)).toBe(0)
  })
})

describe('textos de los cambios', () => {
  it('un cambio de incidente se lee campo por campo', () => {
    const entry = incidentEntry({
      status: { from: { code: 'open', name: 'Abierto' }, to: { code: 'in_progress', name: 'En progreso' } },
      assignee: { from: { id: 'u2', name: 'Ana' }, to: null },
    })

    expect(changeRows(entry, null)).toEqual([
      { label: 'Estado', value: 'Abierto → En progreso' },
      { label: 'Asignado a', value: 'Ana → Sin asignar' },
    ])
    expect(changeSummary(entry, null)).toBe('Estado: Abierto → En progreso (+1)')
    expect(targetText(entry)).toBe('TZ-26-0123 · Impresora')
  })

  it('la administración muestra nombres de roles y permisos, y el código si ya no existen', () => {
    expect(changeRows(adminEntry('user_role_changed', { role: { from: 'agent', to: 'supervisor' } }), catalog)).toEqual(
      [{ label: 'Rol', value: 'Agente → Supervisor' }],
    )
    // Al cambiar de rol, las excepciones que tenía la persona
    expect(
      changeRows(
        adminEntry('user_role_changed', {
          role: { from: 'agent', to: 'supervisor' },
          cleared: { granted: ['incidents.export'], denied: ['incidents.take'] },
        }),
        catalog,
      ),
    ).toEqual([
      { label: 'Rol', value: 'Agente → Supervisor' },
      { label: 'Permisos adicionales que se quitaron', value: 'Exportar incidentes' },
      { label: 'Permisos del rol que se devolvieron', value: 'incidents.take' },
    ])
    expect(
      changeRows(
        adminEntry('user_permissions_changed', { added: ['incidents.export', 'audit.view'], removed: [] }),
        catalog,
      ),
    ).toEqual([{ label: 'Permisos concedidos', value: 'Exportar incidentes, audit.view' }])
  })

  it('un rol editado muestra solo lo que cambió, con su política de verificación', () => {
    const entry = adminEntry('role_updated', {
      code: 'agent',
      name: null,
      level: { from: 40, to: 45 },
      mfa: { from: { required: true, grace_logins: 1 }, to: { required: false, grace_logins: 0 } },
    })

    expect(changeRows(entry, catalog).map((row) => row.label)).toEqual(['Nivel', 'Verificación en dos pasos'])
    expect(changeRows(entry, catalog)[0]?.value).toBe('40 → 45')
  })

  it('un rol eliminado se nombra con lo que quedó guardado', () => {
    const entry = adminEntry(
      'role_deleted',
      { code: 'soporte', name: 'Soporte', level: 50 },
      { targetRole: { code: 'soporte', name: 'Soporte', deleted: true } },
    )

    expect(targetText(entry)).toBe('Rol Soporte (eliminado)')
    expect(changeRows(entry, catalog)).toEqual([
      { label: 'Código', value: 'soporte' },
      { label: 'Nombre', value: 'Soporte' },
      { label: 'Nivel', value: '50' },
    ])
  })
})

describe('exportaciones', () => {
  const withPeople = { ...catalog, people: [{ id: 'p1', name: 'Laura Gómez' }] }

  it('muestra cuántas filas salieron y con qué filtros', () => {
    const entry = adminEntry('incidents_exported', {
      rows: 1234,
      criteria: {
        search: 'impresora',
        status: [{ code: 'open', name: 'Abierto' }],
        assignee: ['p1'],
        unassigned: true,
        overdue: true,
      },
    })

    expect(changeRows(entry, withPeople)).toEqual([
      { label: 'Filas exportadas', value: '1.234' },
      { label: 'Búsqueda', value: '«impresora»' },
      { label: 'Estado', value: 'Abierto' },
      { label: 'Asignado a', value: 'Laura Gómez, Sin asignar' },
      { label: 'Solo vencidos', value: 'Sí' },
    ])
    expect(changeSummary(entry, withPeople)).toBe('Filas exportadas: 1.234 (+4)')
    expect(targetText(entry)).toBe('Incidentes')
  })

  it('aclara cuando se exportó sin filtros', () => {
    expect(changeRows(adminEntry('incidents_exported', { rows: 3, criteria: [] }), catalog)).toEqual([
      { label: 'Filas exportadas', value: '3' },
      { label: 'Filtros', value: 'Ninguno: todo lo que la persona podía ver' },
    ])
  })
})
