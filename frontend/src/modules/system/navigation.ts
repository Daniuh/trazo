// Entrada del menú de sistema: aparece con el permiso de salud o el de eventos. Documentado en docs/frontend/system.md
import type { NavItem } from '@/app/navigation'

const items: NavItem[] = [
  {
    label: 'Sistema',
    icon: 'server',
    to: { name: 'system' },
    order: 60,
    permission: ['system.health.view', 'system.logs.view'],
  },
]

export default items
