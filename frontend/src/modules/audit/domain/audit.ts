// Tipos de la auditoría: registros de cada pestaña, catálogo de la pantalla y criterios de búsqueda.
// Qué se puede abrir desde cada registro lo decide la API ("links"). Documentado en docs/frontend/audit.md

export type AuditTab = 'incidents' | 'admin'

export interface CodeName {
  code: string
  name: string
}

export interface PersonRef {
  id: string
  name: string
}

export type Change = { from?: unknown; to?: unknown }

interface AuditEntryBase {
  id: number
  createdAt: string
  type: CodeName
  user: PersonRef
  ipAddress: string | null
  userAgent: string | null
}

export interface IncidentAuditEntry extends AuditEntryBase {
  tab: 'incidents'
  incident: { id: string; code: string; title: string; deleted: boolean }
  changes: Record<string, Change> | null
  links: { incident: boolean }
}

export interface AdminAuditEntry extends AuditEntryBase {
  tab: 'admin'
  targetUser: PersonRef | null
  targetRole: (CodeName & { deleted: boolean }) | null
  details: Record<string, unknown> | null
  links: { user: boolean; role: boolean }
}

export type AuditEntry = IncidentAuditEntry | AdminAuditEntry

export interface AuditCatalog {
  incidentTypes: CodeName[] | null
  adminTypes: CodeName[] | null
  roles: CodeName[]
  permissions: CodeName[]
  people: PersonRef[]
}

// Rango que la API aplicó de verdad (sin fechas, los últimos 30 días)
export interface AuditPeriod {
  from: string
  to: string
}

export interface AuditCriteria {
  from: string | null
  to: string | null
  actor: string | null
  types: string[]
  // Incidentes: código INC-000123. Administración: persona afectada (ULID)
  target: string | null
  role: string | null
}

export const EMPTY_CRITERIA: AuditCriteria = { from: null, to: null, actor: null, types: [], target: null, role: null }

export const PER_PAGE = 25

// Filtros activos, para "Filtros (n)" en celular y tablet
export function activeAuditFilterCount(criteria: AuditCriteria): number {
  return [
    criteria.from || criteria.to,
    criteria.actor,
    criteria.types.length > 0,
    criteria.target,
    criteria.role,
  ].filter(Boolean).length
}
