// Roles tal como los entiende la administración en el front. Qué se puede hacer con cada uno lo decide la API
// (campo actions). Documentado en docs/frontend/admin.md

export interface RoleMfaPolicy {
  required: boolean
  graceLogins: number
}

export interface Role {
  code: string
  name: string
  level: number
  // Uno de los roles base: no se elimina
  isSystem: boolean
  permissions: string[]
  mfa: RoleMfaPolicy
  members: number
  // Quien consulta lo puede dar a una persona
  assignable: boolean
  actions: { update: boolean; delete: boolean }
}

export interface RoleDraft {
  code: string
  name: string
  // Texto mientras se escribe; la API valida el número
  level: string
  permissions: string[]
  mfa: RoleMfaPolicy
}

// Ingresos en que se puede posponer configurar el MFA (igual que en la API)
export const MAX_GRACE_LOGINS = 5

// El rol de la cima tiene siempre todos los permisos: el front solo lo muestra
export const ROOT_LEVEL = 100

export function draftOf(role: Role): RoleDraft {
  return {
    code: role.code,
    name: role.name,
    level: String(role.level),
    permissions: [...role.permissions],
    mfa: { ...role.mfa },
  }
}

export const EMPTY_ROLE_DRAFT: RoleDraft = {
  code: '',
  name: '',
  level: '',
  permissions: [],
  mfa: { required: true, graceLogins: 1 },
}

// Hay algo distinto de lo guardado (para no pedir confirmación ni guardar en vano)
export function draftChanged(draft: RoleDraft, role: Role): boolean {
  const saved = draftOf(role)
  return (
    draft.name.trim() !== saved.name ||
    draft.level.trim() !== saved.level ||
    draft.mfa.required !== saved.mfa.required ||
    draft.mfa.graceLogins !== saved.mfa.graceLogins ||
    [...draft.permissions].sort().join() !== [...saved.permissions].sort().join()
  )
}
