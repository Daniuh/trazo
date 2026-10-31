// Menú de la aplicación. Cada módulo aporta sus entradas en modules/<módulo>/navigation.ts.
// El filtrado por permisos se suma con la autenticación. Documentado en docs/frontend/index.md
import type { RouteLocationRaw } from 'vue-router'
import type { IconName } from '@/design/icons/icons'

export interface NavItem {
  label: string
  icon: IconName
  to: RouteLocationRaw
  order: number
  permission?: string
}

type ModuleNavigation = { default: NavItem[] }

const moduleItems = Object.values(
  import.meta.glob<ModuleNavigation>('../modules/*/navigation.ts', { eager: true }),
).flatMap((module) => module.default)

const appItems: NavItem[] = [
  { label: 'Inicio', icon: 'home', to: { name: 'home' }, order: 0 },
  // La guía visual solo existe en desarrollo
  ...(import.meta.env.DEV
    ? [{ label: 'Guía visual', icon: 'eye' as const, to: { name: 'design-guide' }, order: 999 }]
    : []),
]

export const navigation: NavItem[] = [...appItems, ...moduleItems].sort((a, b) => a.order - b.order)
