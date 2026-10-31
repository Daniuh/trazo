// Tipo de las opciones de un menú (AppActionMenu).
// Documentado en docs/frontend/sistema-de-diseno/componentes.md
import type { IconName } from '@/design/icons/icons'

export interface MenuItem {
  key: string
  label: string
  icon?: IconName
  danger?: boolean
}
