// Criterios del listado de usuarios: página, orden, búsqueda y filtros. La API valida todo; aquí solo viven los
// valores posibles y los de por defecto. Documentado en docs/frontend/admin.md

export const USER_SORT_FIELDS = ['name', 'email', 'created_at', 'last_login_at'] as const
export type UserSortField = (typeof USER_SORT_FIELDS)[number]

export const USER_STATUSES = ['active', 'inactive'] as const
export type UserStatusFilter = (typeof USER_STATUSES)[number]

export const MFA_STATES = ['enabled', 'disabled'] as const
export type MfaFilter = (typeof MFA_STATES)[number]

export const USER_PER_PAGE_OPTIONS = [20, 50, 100] as const

export interface UserListCriteria {
  page: number
  perPage: number
  sort: UserSortField
  descending: boolean
  search: string
  roles: string[]
  status: UserStatusFilter | null
  mfa: MfaFilter | null
}

export const DEFAULT_USER_CRITERIA: UserListCriteria = {
  page: 1,
  perPage: 20,
  sort: 'name',
  descending: false,
  search: '',
  roles: [],
  status: null,
  mfa: null,
}

// Cuántos filtros hay activos (para el botón "Filtros (n)"); búsqueda, orden y página no cuentan
export function activeUserFilterCount(criteria: UserListCriteria): number {
  return [criteria.roles.length > 0, criteria.status, criteria.mfa].filter(Boolean).length
}
