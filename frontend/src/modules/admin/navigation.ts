// Entradas del menú de la administración: Usuarios para quien puede verlos y Roles para quien los edita.
// Documentado en docs/frontend/admin.md
import type { NavItem } from '@/app/navigation'

const items: NavItem[] = [
  { label: 'Usuarios', icon: 'users', to: { name: 'users' }, order: 50, permission: 'users.view' },
  { label: 'Roles', icon: 'shield', to: { name: 'roles' }, order: 51, permission: 'roles.manage' },
]

export default items
