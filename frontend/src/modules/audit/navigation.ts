// Entrada del menú de la auditoría: aparece con cualquiera de sus dos permisos. Documentado en docs/frontend/audit.md
import type { NavItem } from '@/app/navigation'

const items: NavItem[] = [
  {
    label: 'Auditoría',
    icon: 'audit',
    to: { name: 'audit' },
    order: 45,
    permission: ['audit.incidents.view', 'audit.admin.view'],
  },
]

export default items
