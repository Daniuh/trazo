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
  requestReopen: boolean
  reopen: boolean
  decideReopen: boolean
  // Escribir en la conversación
  comment: boolean
}

// Estado al que se puede llevar el incidente; algunos exigen explicar el cambio
export interface NextStatus extends CatalogItem {
  requiresNote: boolean
}

// Solicitud de reapertura que espera una decisión
export interface ReopenRequest {
  requestedBy: PersonRef
  reason: string
  createdAt: string
}

// Grupo de vencimiento, calculado por la API con el día del negocio
export const DUE_BUCKETS = ['overdue', 'today', 'week', 'later', 'finished'] as const
export type DueBucket = (typeof DUE_BUCKETS)[number]

// Cómo va con su plazo (lo calcula la API): avance de 0 a 100 en los pendientes; a tiempo o no en los terminados
export interface Deadline {
  bucket: DueBucket
  timeUsed: number | null
  resolvedOnTime: boolean | null
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
  deadline: Deadline
  // Hay una solicitud de reapertura esperando respuesta (el listado solo trae esto)
  reopenRequested: boolean
  resolvedAt: string | null
  createdAt: string
  updatedAt: string
  deletedAt: string | null
  version: number
  actions: IncidentActions
  nextStatuses: NextStatus[]
  reopenRequest: ReopenRequest | null
  // Mensajes de la conversación (solo lo trae el detalle; null en el listado)
  commentsCount: number | null
}

// Un mensaje de la conversación. Si se borró, el texto viene vacío ("Mensaje eliminado")
export interface Comment {
  id: string
  author: PersonRef
  body: string | null
  mine: boolean
  createdAt: string
  deletedAt: string | null
  canDelete: boolean
}

// Hasta cuántos caracteres admite un mensaje (la API lo valida igual)
export const COMMENT_MAX = 2000

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
  // Lo que escribió la persona: la solución, el motivo de cancelar o de reabrir
  note: string | null
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
  requiresNote: boolean
  next: string[]
}

export interface Catalogs {
  priorities: PriorityOption[]
  statuses: StatusOption[]
}

// La API manda (ADR-0073); aquí solo para avisar antes de enviar y mostrar el contador
export const TITLE_MIN = 5
export const TITLE_MAX = 80
export const DESCRIPTION_MAX = 5000
export const NOTE_MAX = 1000
