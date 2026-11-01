// Traduce las respuestas de la API (snake_case) al modelo del front y viceversa.
// Documentado en docs/frontend/incidents.md
import type { Activity, Catalogs, Comment, Incident, IncidentDraft } from '../domain/incident'

export interface IncidentDto {
  id: string
  code: string
  title: string
  description: string
  priority: { code: string; name: string; level: number }
  status: { code: string; name: string; is_final: boolean }
  creator: { id: string; name: string }
  assignee: { id: string; name: string } | null
  due_date: string
  is_overdue: boolean
  deadline: { bucket: Incident['deadline']['bucket']; time_used: number | null; resolved_on_time: boolean | null }
  reopen_requested: boolean
  resolved_at: string | null
  created_at: string
  updated_at: string
  deleted_at: string | null
  version: number
  actions: Record<
    | 'update'
    | 'change_status'
    | 'take'
    | 'assign'
    | 'delete'
    | 'restore'
    | 'request_reopen'
    | 'reopen'
    | 'decide_reopen'
    | 'comment',
    boolean
  >
  // Solo el detalle trae estos dos; el listado no
  next_statuses?: { code: string; name: string; requires_note: boolean }[]
  reopen_request?: { requested_by: { id: string; name: string }; reason: string; created_at: string } | null
  comments_count?: number
}

export interface CommentDto {
  id: string
  author: { id: string; name: string }
  body: string | null
  mine: boolean
  created_at: string
  deleted_at: string | null
  actions: { delete: boolean }
}

export interface ActivityDto {
  type: { code: string; name: string }
  user: { id: string; name: string }
  changes: Activity['changes']
  note: string | null
  created_at: string
}

export interface CatalogsDto {
  priorities: { code: string; name: string; level: number; default_resolution_minutes: number }[]
  statuses: {
    code: string
    name: string
    is_initial: boolean
    is_pending: boolean
    is_final: boolean
    requires_note: boolean
    next: string[]
  }[]
}

export const toIncident = (dto: IncidentDto): Incident => ({
  id: dto.id,
  code: dto.code,
  title: dto.title,
  description: dto.description,
  priority: { ...dto.priority },
  status: { code: dto.status.code, name: dto.status.name, isFinal: dto.status.is_final },
  creator: { ...dto.creator },
  assignee: dto.assignee ? { ...dto.assignee } : null,
  dueDate: dto.due_date,
  isOverdue: dto.is_overdue,
  deadline: {
    bucket: dto.deadline.bucket,
    timeUsed: dto.deadline.time_used,
    resolvedOnTime: dto.deadline.resolved_on_time,
  },
  reopenRequested: dto.reopen_requested,
  resolvedAt: dto.resolved_at,
  createdAt: dto.created_at,
  updatedAt: dto.updated_at,
  deletedAt: dto.deleted_at,
  version: dto.version,
  actions: {
    update: dto.actions.update,
    changeStatus: dto.actions.change_status,
    take: dto.actions.take,
    assign: dto.actions.assign,
    delete: dto.actions.delete,
    restore: dto.actions.restore,
    requestReopen: dto.actions.request_reopen,
    reopen: dto.actions.reopen,
    decideReopen: dto.actions.decide_reopen,
    comment: dto.actions.comment,
  },
  nextStatuses: (dto.next_statuses ?? []).map((status) => ({
    code: status.code,
    name: status.name,
    requiresNote: status.requires_note,
  })),
  reopenRequest: dto.reopen_request
    ? {
        requestedBy: { ...dto.reopen_request.requested_by },
        reason: dto.reopen_request.reason,
        createdAt: dto.reopen_request.created_at,
      }
    : null,
  commentsCount: dto.comments_count ?? null,
})

export const toComment = (dto: CommentDto): Comment => ({
  id: dto.id,
  author: { ...dto.author },
  body: dto.body,
  mine: dto.mine,
  createdAt: dto.created_at,
  deletedAt: dto.deleted_at,
  canDelete: dto.actions.delete,
})

export const toActivity = (dto: ActivityDto): Activity => ({
  type: dto.type.code,
  user: { ...dto.user },
  changes: dto.changes,
  note: dto.note,
  createdAt: dto.created_at,
})

export const toCatalogs = (dto: CatalogsDto): Catalogs => ({
  priorities: dto.priorities.map((p) => ({
    code: p.code,
    name: p.name,
    level: p.level,
    resolutionMinutes: p.default_resolution_minutes,
  })),
  statuses: dto.statuses.map((s) => ({
    code: s.code,
    name: s.name,
    isInitial: s.is_initial,
    isPending: s.is_pending,
    isFinal: s.is_final,
    requiresNote: s.requires_note,
    next: [...s.next],
  })),
})

// Al editar, el vencimiento solo viaja si cambió; la asignación tiene su propio endpoint
export function toPayload(draft: IncidentDraft, creating: boolean): Record<string, unknown> {
  const payload: Record<string, unknown> = {
    title: draft.title,
    description: draft.description,
    priority: draft.priority,
  }
  if (draft.dueDate) payload.due_date = draft.dueDate
  if (creating && draft.assignee) payload.assignee = draft.assignee
  return payload
}
