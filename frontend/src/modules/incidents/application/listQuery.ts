// Traduce los criterios del listado a la URL y de vuelta: así un enlace o una recarga conservan la búsqueda.
// Lo que no se reconoce en la URL se ignora y vuelve al valor por defecto. Documentado en docs/frontend/incidents.md
import type { LocationQuery, LocationQueryRaw } from 'vue-router'
import { DEFAULT_CRITERIA, PER_PAGE_OPTIONS, SORT_FIELDS, type ListCriteria, type SortField } from '../domain/listing'

const one = (value: unknown): string | null => {
  const raw = Array.isArray(value) ? value[0] : value
  return typeof raw === 'string' && raw !== '' ? raw : null
}

const list = (value: unknown): string[] => one(value)?.split(',').filter(Boolean) ?? []

const positive = (value: unknown, fallback: number): number => {
  const parsed = Number(one(value))
  return Number.isInteger(parsed) && parsed > 0 ? parsed : fallback
}

export function fromQuery(query: LocationQuery): ListCriteria {
  const sortRaw = one(query.sort) ?? ''
  const sort = sortRaw.replace(/^-/, '') as SortField
  const perPage = positive(query.per_page, DEFAULT_CRITERIA.perPage)

  return {
    page: positive(query.page, 1),
    perPage: (PER_PAGE_OPTIONS as readonly number[]).includes(perPage) ? perPage : DEFAULT_CRITERIA.perPage,
    sort: SORT_FIELDS.includes(sort) ? sort : DEFAULT_CRITERIA.sort,
    descending: SORT_FIELDS.includes(sort) ? sortRaw.startsWith('-') : DEFAULT_CRITERIA.descending,
    search: one(query.search) ?? '',
    statuses: list(query.status),
    priorities: list(query.priority),
    assignees: list(query.assignee),
    creator: one(query.creator),
    createdFrom: one(query.created_from),
    createdTo: one(query.created_to),
    dueFrom: one(query.due_from),
    dueTo: one(query.due_to),
    overdue: one(query.overdue) === '1',
    deleted: one(query.deleted) === '1',
  }
}

// Solo viaja lo que difiere del valor por defecto, para que la URL quede corta y legible
export function toQuery(criteria: ListCriteria): LocationQueryRaw {
  const query: LocationQueryRaw = {}
  const set = (key: string, value: string | null | undefined) => {
    if (value) query[key] = value
  }

  if (criteria.page > 1) set('page', String(criteria.page))
  if (criteria.perPage !== DEFAULT_CRITERIA.perPage) set('per_page', String(criteria.perPage))
  if (criteria.sort !== DEFAULT_CRITERIA.sort || criteria.descending !== DEFAULT_CRITERIA.descending) {
    set('sort', `${criteria.descending ? '-' : ''}${criteria.sort}`)
  }
  set('search', criteria.search.trim())
  set('status', criteria.statuses.join(','))
  set('priority', criteria.priorities.join(','))
  set('assignee', criteria.assignees.join(','))
  set('creator', criteria.creator)
  set('created_from', criteria.createdFrom)
  set('created_to', criteria.createdTo)
  set('due_from', criteria.dueFrom)
  set('due_to', criteria.dueTo)
  if (criteria.overdue) set('overdue', '1')
  if (criteria.deleted) set('deleted', '1')
  return query
}
