// Entradas del menú del módulo de incidentes. Todos los roles ven al menos sus propios incidentes.
// Documentado en docs/frontend/incidents.md
import type { NavItem } from '@/app/navigation'

const items: NavItem[] = [
  { label: 'Incidentes', icon: 'incidents', to: { name: 'incidents' }, order: 10, permission: 'incidents.view.own' },
]

export default items
