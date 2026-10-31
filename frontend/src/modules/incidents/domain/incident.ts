// Modelo de incidentes tal como lo entiende el front. Las reglas viven en la API: aquí solo hay datos
// y lo que la API dice que esta persona puede hacer (actions). Documentado en docs/frontend/incidents.md

export interface CatalogItem {
  code: string
  name: string
}

export interface PersonRef {
  id: string
  name: string
}

export interface IncidentActions {
  update: boolean
  changeStatus: boolean
  take: boolean
  assign: boolean
  delete: boolean
  restore: boolean
}

export interface Incident {
  id: string
  code: string
  title: string
  description: string
  priority: CatalogItem & { level: number }
  status: CatalogItem & { isFinal: boolean }
  creator: PersonRef
  assignee: PersonRef | null
  dueDate: string
  isOverdue: boolean
  resolvedAt: string | null
  createdAt: string
  updatedAt: string
  deletedAt: string | null
  version: number
  actions: IncidentActions
  nextStatuses: CatalogItem[]
}

// Lo que se envía al crear o editar
export interface IncidentDraft {
  title: string
  description: string
  priority: string
  // ISO en UTC; null deja que la API lo calcule (al crear) o lo conserve (al editar)
  dueDate: string | null
  assignee: string | null
}

export interface Activity {
  type: string
  user: PersonRef
  changes: Record<string, { from: unknown; to: unknown }> | null
  createdAt: string
}

export interface PriorityOption extends CatalogItem {
  level: number
  resolutionMinutes: number
}

export interface StatusOption extends CatalogItem {
  isInitial: boolean
  isPending: boolean
  isFinal: boolean
  next: string[]
}

export interface Catalogs {
  priorities: PriorityOption[]
  statuses: StatusOption[]
}

export const TITLE_MAX = 150
export const DESCRIPTION_MAX = 5000
