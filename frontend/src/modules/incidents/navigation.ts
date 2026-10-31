// Entradas del menú del módulo de incidentes. Documentado en docs/frontend/incidents.md
import type { NavItem } from '@/app/navigation'

const items: NavItem[] = [
  {
    label: 'Nuevo incidente',
    icon: 'plus',
    to: { name: 'incident-create' },
    order: 20,
    permission: 'incidents.create',
  },
]

export default items
