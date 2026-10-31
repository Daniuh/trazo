// Usuario de la sesión tal como lo entiende el front. Los permisos los decide el backend; aquí solo se consultan
// para mostrar u ocultar opciones. Documentado en docs/frontend/auth.md

export interface SessionRole {
  code: string
  name: string
}

export interface SessionUser {
  id: string
  name: string
  email: string
  roles: SessionRole[]
  permissions: string[]
}

export function hasPermission(user: SessionUser | null, permission: string): boolean {
  return user?.permissions.includes(permission) ?? false
}
