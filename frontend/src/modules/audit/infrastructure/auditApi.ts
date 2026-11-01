// Repositorio de la auditoría: catálogo de la pantalla y las dos listas por cursor (/audit/incidents y /audit/admin),
// con la traducción de la respuesta. Documentado en docs/frontend/audit.md
import { http } from '@/shared/http'
import type { CursorPage } from '@/shared/listing/useCursorList'
import {
  PER_PAGE,
  type AdminAuditEntry,
  type AuditCatalog,
  type AuditCriteria,
  type AuditPeriod,
  type AuditTab,
  type Change,
  type CodeName,
  type IncidentAuditEntry,
  type PersonRef,
} from '../domain/audit'

type Envelope<T> = { data: T }

interface EntryDto {
  id: number
  created_at: string
  type: CodeName
  user: PersonRef
  ip_address: string | null
  user_agent: string | null
}

interface IncidentEntryDto extends EntryDto {
  incident: IncidentAuditEntry['incident']
  changes: Record<string, Change> | null
  note: string | null
  links: { incident: boolean }
}

interface AdminEntryDto extends EntryDto {
  target_user: PersonRef | null
  target_role: AdminAuditEntry['targetRole']
  details: Record<string, unknown> | null
  links: { user: boolean; role: boolean }
}

interface ListResponse<D> {
  data: D[]
  meta: { next_cursor: string | null; from: string; to: string }
}

interface CatalogDto {
  incident_types: CodeName[] | null
  admin_types: CodeName[] | null
  roles: CodeName[]
  permissions: CodeName[]
  people: PersonRef[]
}

const base = (dto: EntryDto) => ({
  id: dto.id,
  createdAt: dto.created_at,
  type: dto.type,
  user: dto.user,
  ipAddress: dto.ip_address,
  userAgent: dto.user_agent,
})

export function toParams(
  tab: AuditTab,
  criteria: AuditCriteria,
  cursor: string | null,
): Record<string, string | number> {
  const params: Record<string, string | number> = { per_page: PER_PAGE }
  const add = (key: string, value: string | null) => {
    if (value) params[key] = value
  }

  add('cursor', cursor)
  add('filter[from]', criteria.from)
  add('filter[to]', criteria.to)
  add('filter[actor]', criteria.actor)
  add('filter[type]', criteria.types.join(','))
  if (tab === 'incidents') {
    add('filter[incident]', criteria.target)
  } else {
    add('filter[user]', criteria.target)
    add('filter[role]', criteria.role)
  }
  return params
}

async function list<D, T>(
  tab: AuditTab,
  criteria: AuditCriteria,
  cursor: string | null,
  signal: AbortSignal,
  map: (dto: D) => T,
): Promise<CursorPage<T, AuditPeriod>> {
  const path = tab === 'incidents' ? '/audit/incidents' : '/audit/admin'
  const { data } = await http.get<ListResponse<D>>(path, { params: toParams(tab, criteria, cursor), signal })
  return { items: data.data.map(map), next: data.meta.next_cursor, meta: { from: data.meta.from, to: data.meta.to } }
}

export const auditApi = {
  async catalog(): Promise<AuditCatalog> {
    const { data } = await http.get<Envelope<CatalogDto>>('/audit/catalog')
    return {
      incidentTypes: data.data.incident_types,
      adminTypes: data.data.admin_types,
      roles: data.data.roles,
      permissions: data.data.permissions,
      people: data.data.people,
    }
  },

  incidents(criteria: AuditCriteria, cursor: string | null, signal: AbortSignal) {
    return list<IncidentEntryDto, IncidentAuditEntry>('incidents', criteria, cursor, signal, (dto) => ({
      ...base(dto),
      tab: 'incidents',
      incident: dto.incident,
      changes: dto.changes,
      note: dto.note,
      links: dto.links,
    }))
  },

  admin(criteria: AuditCriteria, cursor: string | null, signal: AbortSignal) {
    return list<AdminEntryDto, AdminAuditEntry>('admin', criteria, cursor, signal, (dto) => ({
      ...base(dto),
      tab: 'admin',
      targetUser: dto.target_user,
      targetRole: dto.target_role,
      details: dto.details,
      links: dto.links,
    }))
  },
}
