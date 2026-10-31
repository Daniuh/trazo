// Permisos para mostrarlos agrupados por módulo y marcar cuáles difieren del rol de la persona. Lógica pura: qué se
// puede conceder lo dice la API (grantable) y la regla final la aplica el back. Documentado en docs/frontend/admin.md
import type { PermissionOption } from './user'

const MODULE_ORDER = ['incidents', 'dashboard', 'audit', 'users', 'roles', 'system'] as const

export const MODULE_LABELS: Record<string, string> = {
  incidents: 'Incidentes',
  dashboard: 'Resumen',
  audit: 'Auditoría',
  users: 'Usuarios',
  roles: 'Roles y permisos',
  system: 'Sistema',
}

export interface PermissionGroup {
  module: string
  label: string
  items: PermissionOption[]
}

// Agrupa en el orden de los módulos; un módulo nuevo aparece al final con su código como nombre
export function groupByModule(permissions: PermissionOption[]): PermissionGroup[] {
  const modules = [...new Set(permissions.map((permission) => permission.module))].sort((a, b) => {
    const rank = (module: string) => {
      const index = (MODULE_ORDER as readonly string[]).indexOf(module)
      return index === -1 ? MODULE_ORDER.length : index
    }
    return rank(a) - rank(b) || a.localeCompare(b)
  })

  return modules.map((module) => ({
    module,
    label: MODULE_LABELS[module] ?? module,
    items: permissions.filter((permission) => permission.module === module),
  }))
}

// "added": la persona lo tiene sin que su rol lo traiga; "removed": su rol lo trae pero se lo quitaron
export type PermissionDifference = 'added' | 'removed' | null

export function differenceFromRole(code: string, selected: string[], rolePermissions: string[]): PermissionDifference {
  const inRole = rolePermissions.includes(code)
  const has = selected.includes(code)
  if (has && !inRole) return 'added'
  if (!has && inRole) return 'removed'
  return null
}

// Cuántos difieren del rol, para el resumen de la tarjeta
export function differenceCount(selected: string[], rolePermissions: string[]): number {
  const all = new Set([...selected, ...rolePermissions])
  return [...all].filter((code) => differenceFromRole(code, selected, rolePermissions) !== null).length
}

// Un permiso se puede tocar si quien administra lo tiene o si se trata de quitarlo
export function canToggle(permission: PermissionOption, selected: string[]): boolean {
  return permission.grantable || selected.includes(permission.code)
}
