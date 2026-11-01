// Reglas puras de la administración en el front: agrupar permisos y marcar lo que difiere del rol.
import { describe, expect, it } from 'vitest'
import { activeUserFilterCount, DEFAULT_USER_CRITERIA } from './listing'
import { canToggle, differenceCount, differenceFromRole, groupByModule } from './permissions'
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
    const selected = ['incidents.create', 'audit.view']

    expect(differenceFromRole('audit.view', selected, role)).toBe('added')
    expect(differenceFromRole('incidents.take', selected, role)).toBe('removed')
    expect(differenceFromRole('incidents.create', selected, role)).toBeNull()
    expect(differenceCount(selected, role)).toBe(2)
  })

  it('un permiso que quien administra no tiene solo se puede quitar, no conceder', () => {
    expect(canToggle(option('incidents.view.any', false), [])).toBe(false)
    expect(canToggle(option('incidents.view.any', false), ['incidents.view.any'])).toBe(true)
    expect(canToggle(option('audit.view'), [])).toBe(true)
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
