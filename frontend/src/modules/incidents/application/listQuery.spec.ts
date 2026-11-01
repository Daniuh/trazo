// La URL del listado: ida y vuelta sin perder nada, valores inválidos ignorados y URL corta.
import { describe, expect, it } from 'vitest'
import { activeFilterCount, DEFAULT_CRITERIA, isSearchable, type ListCriteria } from '../domain/listing'
import { toParams } from '../infrastructure/listApi'
import { fromQuery, toQuery } from './listQuery'

const full: ListCriteria = {
  view: 'table',
  groupBy: 'status',
  page: 3,
  perPage: 50,
  sort: 'due_date',
  descending: false,
  search: 'impresora',
  statuses: ['open', 'in_progress'],
  priorities: ['high'],
  assignees: ['none', '01m5fr6tv8fq2b3bqkbdc26zjh'],
  creator: '01m5fr6vav75qwhr3qgm3jph2s',
  createdFrom: '2026-10-01T05:00:00.000Z',
  createdTo: null,
  dueFrom: null,
  dueTo: '2026-10-31T04:59:59.999Z',
  overdue: true,
  deleted: false,
  reopenRequested: true,
}

describe('listQuery', () => {
  it('ida y vuelta por la URL conserva todos los criterios', () => {
    const query = Object.fromEntries(Object.entries(toQuery(full)).map(([key, value]) => [key, String(value)]))

    expect(fromQuery(query)).toEqual(full)
  })

  it('con los valores por defecto la URL queda vacía', () => {
    expect(toQuery(DEFAULT_CRITERIA)).toEqual({})
  })

  it('ignora valores que no reconoce', () => {
    const criteria = fromQuery({ page: '-2', per_page: '7', sort: 'password', overdue: 'si' })

    expect(criteria).toMatchObject({ page: 1, perPage: 20, sort: 'created_at', descending: true, overdue: false })
  })

  it('arma los parámetros de la API con filtros separados por comas', () => {
    expect(toParams(full)).toMatchObject({
      page: 3,
      per_page: 50,
      sort: 'due_date',
      'filter[status]': 'open,in_progress',
      'filter[assignee]': 'none,01m5fr6tv8fq2b3bqkbdc26zjh',
      'filter[overdue]': 1,
      'filter[reopen_requested]': 1,
    })
    expect(toParams(DEFAULT_CRITERIA)).not.toHaveProperty('filter[deleted]')
  })

  it('pide el grupo solo en la lista y lo lleva a la URL en español', () => {
    expect(toParams(DEFAULT_CRITERIA)).toMatchObject({ group_by: 'due' })
    expect(toParams({ ...DEFAULT_CRITERIA, groupBy: 'none' })).not.toHaveProperty('group_by')
    // La tabla sigue el orden de sus columnas, sin grupo
    expect(toParams(full)).not.toHaveProperty('group_by')
    expect(toQuery(full)).toMatchObject({ vista: 'tabla', agrupar: 'estado' })
    expect(fromQuery({ vista: 'otra', agrupar: 'color' })).toMatchObject({ view: 'list', groupBy: 'due' })
  })

  it('cuenta los filtros activos sin contar búsqueda, orden ni página', () => {
    expect(activeFilterCount(DEFAULT_CRITERIA)).toBe(0)
    expect(activeFilterCount(full)).toBe(8)
  })

  it('acepta búsquedas de 3 letras o un código', () => {
    expect(isSearchable('ab')).toBe(false)
    expect(isSearchable('vpn')).toBe(true)
    expect(isSearchable('26-42')).toBe(true)
    expect(isSearchable('TZ-26-0142')).toBe(true)
    expect(isSearchable('')).toBe(true)
  })
})
