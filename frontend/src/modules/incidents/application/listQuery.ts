// Traduce los criterios del listado a la URL y de vuelta: así un enlace o una recarga conservan la búsqueda.
// Lo que no se reconoce en la URL se ignora y vuelve al valor por defecto. Documentado en docs/frontend/incidents.md
import type { LocationQuery, LocationQueryRaw } from 'vue-router'
import { compactQuery, queryList, queryOne, queryPositive } from '@/shared/routing/queryValues'
import { DEFAULT_CRITERIA, PER_PAGE_OPTIONS, SORT_FIELDS, type ListCriteria, type SortField } from '../domain/listing'

export function fromQuery(query: LocationQuery): ListCriteria {
  const sortRaw = queryOne(query.sort) ?? ''
  const sort = sortRaw.replace(/^-/, '') as SortField
  const perPage = queryPositive(query.per_page, DEFAULT_CRITERIA.perPage)

  return {
    page: queryPositive(query.page, 1),
    perPage: (PER_PAGE_OPTIONS as readonly number[]).includes(perPage) ? perPage : DEFAULT_CRITERIA.perPage,
    sort: SORT_FIELDS.includes(sort) ? sort : DEFAULT_CRITERIA.sort,
    descending: SORT_FIELDS.includes(sort) ? sortRaw.startsWith('-') : DEFAULT_CRITERIA.descending,
    search: queryOne(query.search) ?? '',
    statuses: queryList(query.status),
    priorities: queryList(query.priority),
    assignees: queryList(query.assignee),
    creator: queryOne(query.creator),
    createdFrom: queryOne(query.created_from),
    createdTo: queryOne(query.created_to),
    dueFrom: queryOne(query.due_from),
    dueTo: queryOne(query.due_to),
    overdue: queryOne(query.overdue) === '1',
    deleted: queryOne(query.deleted) === '1',
  }
}

// Solo viaja lo que difiere del valor por defecto, para que la URL quede corta y legible
export function toQuery(criteria: ListCriteria): LocationQueryRaw {
  const sorted = criteria.sort !== DEFAULT_CRITERIA.sort || criteria.descending !== DEFAULT_CRITERIA.descending

  return compactQuery({
    page: criteria.page > 1 ? String(criteria.page) : null,
    per_page: criteria.perPage !== DEFAULT_CRITERIA.perPage ? String(criteria.perPage) : null,
    sort: sorted ? `${criteria.descending ? '-' : ''}${criteria.sort}` : null,
    search: criteria.search.trim(),
    status: criteria.statuses.join(','),
    priority: criteria.priorities.join(','),
    assignee: criteria.assignees.join(','),
    creator: criteria.creator,
    created_from: criteria.createdFrom,
    created_to: criteria.createdTo,
    due_from: criteria.dueFrom,
    due_to: criteria.dueTo,
    overdue: criteria.overdue ? '1' : null,
    deleted: criteria.deleted ? '1' : null,
  })
}
