// Textos y tonos de la administración: qué hace cada rol (para elegirlo), estado de la cuenta y último ingreso.
// Un rol nuevo sin descripción muestra cuántos permisos trae. Documentado en docs/frontend/admin.md
import { formatShortDateTime } from '@/shared/format/dates'
import type { Tone } from '@/shared/ui/tones'
import type { RoleOption, UserSummary } from '../domain/user'

const ROLE_DESCRIPTIONS: Record<string, string> = {
  requester: 'Reporta incidentes y sigue los suyos.',
  agent: 'Atiende los incidentes que toma o le asignan.',
  supervisor: 'Ve y gestiona todos los incidentes, y asigna el trabajo.',
  admin: 'Crea y administra a las personas por debajo de su nivel.',
  super_admin: 'Acceso total: administra a todos, restablece accesos y el sistema.',
}

export function roleDescription(role: RoleOption): string {
  return ROLE_DESCRIPTIONS[role.code] ?? `${role.permissions.length} permisos.`
}

export const statusLabel = (user: Pick<UserSummary, 'isActive'>) => (user.isActive ? 'Activa' : 'Desactivada')

export const statusTone = (user: Pick<UserSummary, 'isActive'>): Tone => (user.isActive ? 'green' : 'muted')

export const mfaLabel = (user: Pick<UserSummary, 'mfaEnabled'>) =>
  user.mfaEnabled ? 'Verificación en dos pasos activa' : 'Sin verificación en dos pasos'

export const lastLoginText = (user: Pick<UserSummary, 'lastLoginAt'>) =>
  user.lastLoginAt ? formatShortDateTime(user.lastLoginAt) : 'Nunca ha ingresado'

export const STATUS_CHOICES = [
  { label: 'Activas', value: 'active' },
  { label: 'Desactivadas', value: 'inactive' },
] as const

export const MFA_CHOICES = [
  { label: 'Con verificación en dos pasos', value: 'enabled' },
  { label: 'Sin verificación en dos pasos', value: 'disabled' },
] as const

// Valor "campo" o "-campo" (descendente), como en la API
export const USER_SORT_CHOICES: { label: string; value: string }[] = [
  { label: 'Nombre (A–Z)', value: 'name' },
  { label: 'Nombre (Z–A)', value: '-name' },
  { label: 'Correo', value: 'email' },
  { label: 'Ingresaron hace poco', value: '-last_login_at' },
  { label: 'Creadas hace poco', value: '-created_at' },
]
