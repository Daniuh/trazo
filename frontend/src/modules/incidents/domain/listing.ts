// Criterios del listado: página, orden, búsqueda y filtros. La API valida todo; aquí solo viven los valores
// posibles y los que se usan por defecto. Documentado en docs/frontend/incidents.md
import type { Incident } from './incident'

export type IncidentSummary = Omit<Incident, 'description' | 'nextStatuses'>

export const SORT_FIELDS = ['created_at', 'due_date', 'updated_at', 'priority', 'status', 'code'] as const
export type SortField = (typeof SORT_FIELDS)[number]

export const PER_PAGE_OPTIONS = [20, 50, 100] as const

// "Sin asignar" dentro del filtro de asignados
export const UNASSIGNED = 'none'

export const MIN_SEARCH_LENGTH = 3

export interface ListCriteria {
  page: number
  perPage: number
  sort: SortField
  descending: boolean
  search: string
  statuses: string[]
  priorities: string[]
  assignees: string[]
  creator: string | null
  createdFrom: string | null
  createdTo: string | null
  dueFrom: string | null
  dueTo: string | null
  overdue: boolean
  deleted: boolean
}

export const DEFAULT_CRITERIA: ListCriteria = {
  page: 1,
  perPage: 20,
  sort: 'created_at',
  descending: true,
  search: '',
  statuses: [],
  priorities: [],
  assignees: [],
  creator: null,
  createdFrom: null,
  createdTo: null,
  dueFrom: null,
  dueTo: null,
  overdue: false,
  deleted: false,
}

export interface ListPage {
  items: IncidentSummary[]
  total: number
  lastPage: number
}

// Cuántos filtros hay activos (para el botón "Filtros (n)"); búsqueda, orden y página no cuentan
export function activeFilterCount(criteria: ListCriteria): number {
  const lists = [criteria.statuses, criteria.priorities, criteria.assignees].filter((list) => list.length > 0).length
  const ranges = [criteria.createdFrom ?? criteria.createdTo, criteria.dueFrom ?? criteria.dueTo].filter(Boolean).length
  const flags = [criteria.creator, criteria.overdue, criteria.deleted].filter(Boolean).length
  return lists + ranges + flags
}

// La búsqueda necesita una palabra de 3 letras o un código (INC-123), igual que en la API
export function isSearchable(text: string): boolean {
  const value = text.trim()
  return (
    value === '' ||
    /^INC-?\d{1,10}$/i.test(value) ||
    new RegExp(`[\\p{L}\\p{N}]{${MIN_SEARCH_LENGTH},}`, 'u').test(value)
  )
}
