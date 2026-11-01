// Repositorio de la administración: todas las llamadas a /users, /roles y /permissions pasan por aquí, y la
// respuesta se traduce a los tipos del dominio. Documentado en docs/frontend/admin.md
import { http } from '@/shared/http'
import type { UserListCriteria } from '../domain/listing'
import type {
  PermissionOption,
  ProfileDraft,
  RoleOption,
  RoleRef,
  UserActions,
  UserDetail,
  UserDraft,
  UserSummary,
} from '../domain/user'

type Envelope<T> = { data: T }

interface SummaryDto {
  id: string
  name: string
  email: string
  is_active: boolean
  role: RoleRef | null
  mfa_enabled: boolean
  last_login_at: string | null
  created_at: string
}

interface DetailDto extends SummaryDto {
  permissions: { effective: string[]; role: string[]; granted: string[]; denied: string[] }
  pending_assigned_incidents: number
  is_self: boolean
  actions: {
    update: boolean
    activate: boolean
    deactivate: boolean
    assign_role: boolean
    grant_permissions: boolean
    reset_mfa: boolean
    reset_access: boolean
  }
}

interface ListResponse {
  data: SummaryDto[]
  meta: { total: number; last_page: number }
}

export interface UserPage {
  items: UserSummary[]
  total: number
  lastPage: number
}

const path = (id: string) => `/users/${encodeURIComponent(id)}`

function toSummary(dto: SummaryDto): UserSummary {
  return {
    id: dto.id,
    name: dto.name,
    email: dto.email,
    isActive: dto.is_active,
    role: dto.role,
    mfaEnabled: dto.mfa_enabled,
    lastLoginAt: dto.last_login_at,
    createdAt: dto.created_at,
  }
}

function toActions(dto: DetailDto['actions']): UserActions {
  return {
    update: dto.update,
    activate: dto.activate,
    deactivate: dto.deactivate,
    assignRole: dto.assign_role,
    grantPermissions: dto.grant_permissions,
    resetMfa: dto.reset_mfa,
    resetAccess: dto.reset_access,
  }
}

function toDetail(dto: DetailDto): UserDetail {
  return {
    ...toSummary(dto),
    permissions: dto.permissions,
    pendingAssignedIncidents: dto.pending_assigned_incidents,
    isSelf: dto.is_self,
    actions: toActions(dto.actions),
  }
}

export function toListParams(criteria: UserListCriteria): Record<string, string | number> {
  const params: Record<string, string | number> = {
    page: criteria.page,
    per_page: criteria.perPage,
    sort: `${criteria.descending ? '-' : ''}${criteria.sort}`,
  }
  if (criteria.search.trim()) params.search = criteria.search.trim()
  if (criteria.roles.length > 0) params['filter[role]'] = criteria.roles.join(',')
  if (criteria.status) params['filter[status]'] = criteria.status
  if (criteria.mfa) params['filter[mfa]'] = criteria.mfa
  return params
}

// El código de quien administra solo viaja si se pidió
export const withCode = <T extends object>(body: T, code?: string) => (code ? { ...body, mfa_code: code } : body)

export const usersApi = {
  async list(criteria: UserListCriteria, signal?: AbortSignal): Promise<UserPage> {
    const { data } = await http.get<ListResponse>('/users', { params: toListParams(criteria), signal })
    return { items: data.data.map(toSummary), total: data.meta.total, lastPage: data.meta.last_page }
  },

  async get(id: string): Promise<UserDetail> {
    const { data } = await http.get<Envelope<DetailDto>>(path(id))
    return toDetail(data.data)
  },

  // La clave identifica este intento de creación: si se reenvía, la API no crea otra cuenta
  async create(draft: UserDraft, idempotencyKey: string, code?: string): Promise<UserDetail> {
    const body = withCode({ name: draft.name, email: draft.email, role: draft.role }, code)
    const { data } = await http.post<Envelope<DetailDto>>('/users', body, {
      headers: { 'Idempotency-Key': idempotencyKey },
    })
    return toDetail(data.data)
  },

  async updateProfile(id: string, draft: ProfileDraft): Promise<UserDetail> {
    const { data } = await http.patch<Envelope<DetailDto>>(path(id), draft)
    return toDetail(data.data)
  },

  async changeRole(id: string, role: string, code?: string): Promise<UserDetail> {
    const { data } = await http.put<Envelope<DetailDto>>(`${path(id)}/roles`, withCode({ role }, code))
    return toDetail(data.data)
  },

  async changePermissions(id: string, permissions: string[], code?: string): Promise<UserDetail> {
    const { data } = await http.put<Envelope<DetailDto>>(`${path(id)}/permissions`, withCode({ permissions }, code))
    return toDetail(data.data)
  },

  async setActive(id: string, active: boolean): Promise<UserDetail> {
    const { data } = await http.post<Envelope<DetailDto>>(`${path(id)}/${active ? 'activate' : 'deactivate'}`)
    return toDetail(data.data)
  },

  async resetMfa(id: string, code?: string): Promise<UserDetail> {
    const { data } = await http.delete<Envelope<DetailDto>>(`${path(id)}/mfa`, { data: withCode({}, code) })
    return toDetail(data.data)
  },

  async resetAccess(id: string, code?: string): Promise<UserDetail> {
    const { data } = await http.post<Envelope<DetailDto>>(`${path(id)}/access-reset`, withCode({}, code))
    return toDetail(data.data)
  },

  async roles(): Promise<RoleOption[]> {
    const { data } = await http.get<Envelope<RoleOption[]>>('/roles')
    return data.data
  },

  async permissions(): Promise<PermissionOption[]> {
    const { data } = await http.get<Envelope<PermissionOption[]>>('/permissions')
    return data.data
  },
}
