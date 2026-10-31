// Menú de la aplicación. Cada módulo aporta sus entradas en modules/<módulo>/navigation.ts.
// Cada persona ve solo las entradas que sus permisos permiten. Documentado en docs/frontend/index.md
import type { RouteLocationRaw } from 'vue-router'
import type { IconName } from '@/design/icons/icons'

export interface NavItem {
  label: string
  icon: IconName
  to: RouteLocationRaw
  order: number
  permission?: string
  // Solo se marca activa en su ruta exacta (el inicio, que contiene a todas las demás)
  exact?: boolean
}

type ModuleNavigation = { default: NavItem[] }

const moduleItems = Object.values(
  import.meta.glob<ModuleNavigation>('../modules/*/navigation.ts', { eager: true }),
).flatMap((module) => module.default)

const appItems: NavItem[] = [
  { label: 'Inicio', icon: 'home', to: { name: 'home' }, order: 0, exact: true },
  // La guía visual solo existe en desarrollo
  ...(import.meta.env.DEV
    ? [{ label: 'Guía visual', icon: 'eye' as const, to: { name: 'design-guide' }, order: 999 }]
    : []),
]

export const navigation: NavItem[] = [...appItems, ...moduleItems].sort((a, b) => a.order - b.order)

// Ocultar una entrada es solo comodidad: la API rechaza igual lo que no corresponde
export function visibleNavigation(can: (permission: string) => boolean): NavItem[] {
  return navigation.filter((item) => !item.permission || can(item.permission))
}
