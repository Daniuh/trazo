// Traduce los criterios del listado de usuarios a la URL y de vuelta: un enlace o una recarga conservan la búsqueda.
// Documentado en docs/frontend/admin.md
import type { LocationQuery, LocationQueryRaw } from 'vue-router'
import { compactQuery, queryChoice, queryList, queryOne, queryPositive } from '@/shared/routing/queryValues'
import {
  DEFAULT_USER_CRITERIA,
  MFA_STATES,
  USER_PER_PAGE_OPTIONS,
  USER_SORT_FIELDS,
  USER_STATUSES,
  type UserListCriteria,
} from '../domain/listing'

export function fromUserQuery(query: LocationQuery): UserListCriteria {
  const sortRaw = queryOne(query.sort) ?? ''
  const sort = queryChoice(sortRaw.replace(/^-/, ''), USER_SORT_FIELDS, DEFAULT_USER_CRITERIA.sort)
  const known = sort === sortRaw.replace(/^-/, '')
  const perPage = queryPositive(query.per_page, DEFAULT_USER_CRITERIA.perPage)
  const status = queryOne(query.status)
  const mfa = queryOne(query.mfa)

  return {
    page: queryPositive(query.page, 1),
    perPage: (USER_PER_PAGE_OPTIONS as readonly number[]).includes(perPage) ? perPage : DEFAULT_USER_CRITERIA.perPage,
    sort,
    descending: known ? sortRaw.startsWith('-') : DEFAULT_USER_CRITERIA.descending,
    search: queryOne(query.search) ?? '',
    roles: queryList(query.role),
    status: status === null ? null : queryChoice(status, USER_STATUSES, 'active'),
    mfa: mfa === null ? null : queryChoice(mfa, MFA_STATES, 'enabled'),
  }
}

// Solo viaja lo que difiere del valor por defecto
export function toUserQuery(criteria: UserListCriteria): LocationQueryRaw {
  const sorted =
    criteria.sort !== DEFAULT_USER_CRITERIA.sort || criteria.descending !== DEFAULT_USER_CRITERIA.descending

  return compactQuery({
    page: criteria.page > 1 ? String(criteria.page) : null,
    per_page: criteria.perPage !== DEFAULT_USER_CRITERIA.perPage ? String(criteria.perPage) : null,
    sort: sorted ? `${criteria.descending ? '-' : ''}${criteria.sort}` : null,
    search: criteria.search.trim(),
    role: criteria.roles.join(','),
    status: criteria.status,
    mfa: criteria.mfa,
  })
}
