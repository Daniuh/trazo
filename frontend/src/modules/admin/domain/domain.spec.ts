// Reglas puras de la administración en el front: agrupar permisos y marcar lo que difiere del rol.
import { describe, expect, it } from 'vitest'
import { activeUserFilterCount, DEFAULT_USER_CRITERIA } from './listing'
import {
  canToggle,
  differenceCount,
  differenceFromRole,
  groupByModule,
  moduleSelection,
  roleDifferences,
  toggleModule,
} from './permissions'
import { draftChanged, draftOf, type Role } from './role'
import type { PermissionOption } from './user'

const option = (code: string, grantable = true): PermissionOption => ({
  code,
  name: code,
  module: code.split('.')[0] as string,
  sensitive: false,
  grantable,
})

describe('permisos', () => {
  it('agrupa por módulo en el orden conocido y deja al final los módulos nuevos', () => {
    const groups = groupByModule([option('users.view'), option('reports.view'), option('incidents.create')])

    expect(groups.map((group) => group.label)).toEqual(['Incidentes', 'Usuarios', 'reports'])
  })

  it('marca lo concedido fuera del rol y lo quitado del rol', () => {
    const role = ['incidents.create', 'incidents.take']
    const selected = ['incidents.create', 'audit.admin.view']

    expect(differenceFromRole('audit.admin.view', selected, role)).toBe('added')
    expect(differenceFromRole('incidents.take', selected, role)).toBe('removed')
    expect(differenceFromRole('incidents.create', selected, role)).toBeNull()
    expect(differenceCount(selected, role)).toBe(2)
  })

  it('lista las diferencias con el rol en el orden del catálogo', () => {
    const catalog = [option('audit.admin.view'), option('incidents.take'), option('incidents.create')]

    expect(
      roleDifferences(['incidents.create', 'audit.admin.view'], ['incidents.create', 'incidents.take'], catalog),
    ).toEqual([
      { code: 'incidents.take', name: 'incidents.take', kind: 'removed' },
      { code: 'audit.admin.view', name: 'audit.admin.view', kind: 'added' },
    ])
  })

  it('resume cada módulo con su conteo y el estado de la casilla "Todos"', () => {
    const items = [option('users.view'), option('users.create'), option('users.mfa.reset', false)]

    expect(moduleSelection(items, [])).toEqual({ selected: 0, total: 3, state: 'none', blocked: true })
    expect(moduleSelection(items, ['users.view'])).toMatchObject({ selected: 1, state: 'some' })
    expect(moduleSelection(items, ['users.view', 'users.create', 'users.mfa.reset'])).toEqual({
      selected: 3,
      total: 3,
      state: 'all',
      blocked: false,
    })
  })

  it('marcar un módulo suma solo lo que se puede conceder; desmarcarlo quita todo el módulo', () => {
    const items = [option('users.view'), option('users.create'), option('users.mfa.reset', false)]

    expect(toggleModule(items, ['incidents.create'], true)).toEqual(['incidents.create', 'users.view', 'users.create'])
    expect(toggleModule(items, ['incidents.create', 'users.view', 'users.mfa.reset'], false)).toEqual([
      'incidents.create',
    ])
  })

  it('un permiso que quien administra no tiene solo se puede quitar, no conceder', () => {
    expect(canToggle(option('incidents.view.any', false), [])).toBe(false)
    expect(canToggle(option('incidents.view.any', false), ['incidents.view.any'])).toBe(true)
    expect(canToggle(option('audit.admin.view'), [])).toBe(true)
  })
})

describe('listado', () => {
  it('cuenta los filtros activos sin la búsqueda ni el orden', () => {
    expect(activeUserFilterCount(DEFAULT_USER_CRITERIA)).toBe(0)
    expect(
      activeUserFilterCount({ ...DEFAULT_USER_CRITERIA, search: 'ana', roles: ['agent'], status: 'inactive' }),
    ).toBe(2)
  })
})

describe('roles', () => {
  const role: Role = {
    code: 'agent',
    name: 'Agente',
    level: 40,
    isSystem: true,
    permissions: ['incidents.take', 'incidents.create'],
    mfa: { required: true, graceLogins: 1 },
    members: 3,
    assignable: true,
    actions: { update: true, delete: false },
  }

  it('el borrador parte del rol guardado y detecta cambios reales (sin importar el orden de los permisos)', () => {
    const draft = draftOf(role)

    expect(draftChanged(draft, role)).toBe(false)
    expect(draftChanged({ ...draft, permissions: ['incidents.create', 'incidents.take'] }, role)).toBe(false)
    expect(draftChanged({ ...draft, level: ' 45 ' }, role)).toBe(true)
    expect(draftChanged({ ...draft, mfa: { required: true, graceLogins: 0 } }, role)).toBe(true)
  })
})
