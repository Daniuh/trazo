// Repositorio de roles: las llamadas a /roles pasan por aquí y la respuesta se traduce al dominio.
// Documentado en docs/frontend/admin.md
import { http } from '@/shared/http'
import type { Role, RoleDraft } from '../domain/role'
import { withCode } from './usersApi'

type Envelope<T> = { data: T }

interface RoleDto {
  code: string
  name: string
  level: number
  is_system: boolean
  permissions: string[]
  mfa: { required: boolean; grace_logins: number }
  members: number
  assignable: boolean
  actions: { update: boolean; delete: boolean }
}

const path = (code: string) => `/roles/${encodeURIComponent(code)}`

function toRole(dto: RoleDto): Role {
  return {
    code: dto.code,
    name: dto.name,
    level: dto.level,
    isSystem: dto.is_system,
    permissions: dto.permissions,
    mfa: { required: dto.mfa.required, graceLogins: dto.mfa.grace_logins },
    members: dto.members,
    assignable: dto.assignable,
    actions: dto.actions,
  }
}

// El nivel viaja como número si se escribió uno; si no, tal cual, para que la API diga qué falta
function toPayload(draft: RoleDraft) {
  const level = draft.level.trim()
  return {
    name: draft.name.trim(),
    level: /^\d+$/.test(level) ? Number(level) : level,
    permissions: draft.permissions,
    mfa_required: draft.mfa.required,
    grace_logins: draft.mfa.graceLogins,
  }
}

export const rolesApi = {
  async list(): Promise<Role[]> {
    const { data } = await http.get<Envelope<RoleDto[]>>('/roles')
    return data.data.map(toRole)
  },

  async get(code: string): Promise<Role> {
    const { data } = await http.get<Envelope<RoleDto>>(path(code))
    return toRole(data.data)
  },

  // La clave identifica este intento de creación: si se reenvía, la API no crea otro rol
  async create(draft: RoleDraft, idempotencyKey: string, code?: string): Promise<Role> {
    const body = withCode({ code: draft.code.trim().toLowerCase(), ...toPayload(draft) }, code)
    const { data } = await http.post<Envelope<RoleDto>>('/roles', body, {
      headers: { 'Idempotency-Key': idempotencyKey },
    })
    return toRole(data.data)
  },

  async update(roleCode: string, draft: RoleDraft, code?: string): Promise<Role> {
    const { data } = await http.patch<Envelope<RoleDto>>(path(roleCode), withCode(toPayload(draft), code))
    return toRole(data.data)
  },

  async remove(code: string): Promise<void> {
    await http.delete(path(code))
  },
}
