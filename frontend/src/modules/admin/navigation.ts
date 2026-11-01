// Entrada del menú de la administración: solo para quien puede ver usuarios. Documentado en docs/frontend/admin.md
import type { NavItem } from '@/app/navigation'

const items: NavItem[] = [
  { label: 'Usuarios', icon: 'users', to: { name: 'users' }, order: 50, permission: 'users.view' },
]

export default items
