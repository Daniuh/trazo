// Personas tal como las entiende la administración en el front. Qué se puede hacer con cada una lo decide la API
// (campo actions); aquí solo se describe la forma de los datos. Documentado en docs/frontend/admin.md

export interface RoleRef {
  code: string
  name: string
  level: number
}

export interface UserSummary {
  id: string
  name: string
  email: string
  isActive: boolean
  role: RoleRef | null
  mfaEnabled: boolean
  lastLoginAt: string | null
  createdAt: string
}

export interface UserActions {
  update: boolean
  activate: boolean
  deactivate: boolean
  assignRole: boolean
  grantPermissions: boolean
  resetMfa: boolean
  resetAccess: boolean
}

export interface UserPermissions {
  // Los que tiene: los de su rol, más los concedidos, menos los quitados
  effective: string[]
  role: string[]
  granted: string[]
  denied: string[]
}

export interface UserDetail extends UserSummary {
  permissions: UserPermissions
  pendingAssignedIncidents: number
  isSelf: boolean
  actions: UserActions
}

// Rol para los formularios: si quien administra lo puede dar y qué permisos trae
export interface RoleOption extends RoleRef {
  assignable: boolean
  permissions: string[]
}

export interface PermissionOption {
  code: string
  name: string
  module: string
  // Darlo o quitarlo pide el código de quien administra
  sensitive: boolean
  // Quien administra lo tiene, así que lo puede conceder
  grantable: boolean
}

export interface UserDraft {
  name: string
  email: string
  role: string | null
}

export interface ProfileDraft {
  name: string
  email: string
}
