// Criterios del listado: vista, grupo, página, orden, búsqueda y filtros. La API valida todo; aquí solo viven los valores
// posibles y los que se usan por defecto. Documentado en docs/frontend/incidents.md
import { DEFAULT_GROUP, type GroupBy } from './grouping'
import type { Incident } from './incident'

export type IncidentSummary = Omit<Incident, 'description' | 'nextStatuses'>

export const SORT_FIELDS = ['created_at', 'due_date', 'updated_at', 'priority', 'status', 'code'] as const
export type SortField = (typeof SORT_FIELDS)[number]

export const PER_PAGE_OPTIONS = [20, 50, 100] as const

// "Sin asignar" dentro del filtro de asignados
export const UNASSIGNED = 'none'

export const MIN_SEARCH_LENGTH = 3

// Lista agrupada (por defecto) o la tabla de siempre
export const LIST_VIEWS = ['list', 'table'] as const
export type ListView = (typeof LIST_VIEWS)[number]

export interface ListCriteria {
  view: ListView
  // Solo cuenta en la lista; la tabla sigue el orden de sus columnas
  groupBy: GroupBy
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
  // Solo los que tienen una solicitud de reapertura esperando respuesta
  reopenRequested: boolean
}

export const DEFAULT_CRITERIA: ListCriteria = {
  view: 'list',
  groupBy: DEFAULT_GROUP,
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
  reopenRequested: false,
}

export interface ListPage {
  items: IncidentSummary[]
  total: number
  lastPage: number
  // Total de cada grupo de la página, con los mismos filtros
  groups: Record<string, number>
}

// El grupo que se pide a la API: ninguno en la tabla
export function effectiveGroup(criteria: ListCriteria): GroupBy {
  return criteria.view === 'list' ? criteria.groupBy : 'none'
}

// Cuántos filtros hay activos (para el botón "Filtros (n)"); búsqueda, orden y página no cuentan
export function activeFilterCount(criteria: ListCriteria): number {
  const lists = [criteria.statuses, criteria.priorities, criteria.assignees].filter((list) => list.length > 0).length
  const ranges = [criteria.createdFrom ?? criteria.createdTo, criteria.dueFrom ?? criteria.dueTo].filter(Boolean).length
  const flags = [criteria.creator, criteria.overdue, criteria.deleted, criteria.reopenRequested].filter(Boolean).length
  return lists + ranges + flags
}

// La búsqueda necesita una palabra de 3 letras o un código con su año (TZ-26-0142, 26-142), igual que en la API
export function isSearchable(text: string): boolean {
  const value = text.trim()
  return (
    value === '' ||
    /^(?:[A-Z]{2,4}-?)?\d{2}-\d{1,7}$/i.test(value) ||
    new RegExp(`[\\p{L}\\p{N}]{${MIN_SEARCH_LENGTH},}`, 'u').test(value)
  )
}
