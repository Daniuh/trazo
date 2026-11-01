// Usuario de la sesión tal como lo entiende el front. Los permisos y el estado del MFA los decide el backend; aquí solo
// se consultan para mostrar u ocultar opciones. Documentado en docs/frontend/auth.md

export interface SessionRole {
  code: string
  name: string
}

export interface MfaState {
  enabled: boolean
  // Su rol lo exige: no puede desactivarlo
  required: boolean
  recoveryCodesLeft: number
}

export interface SessionUser {
  id: string
  name: string
  email: string
  roles: SessionRole[]
  permissions: string[]
  mfa: MfaState
}

// Qué sigue después de una contraseña correcta (lo decide la API)
export type LoginStep = 'complete' | 'setup_suggested' | 'mfa_verify' | 'mfa_setup'

// Pasos que todavía no abren la sesión: el token solo sirve para verificar o configurar el MFA
export type PendingStep = Extract<LoginStep, 'mfa_verify' | 'mfa_setup'>

export const isPendingStep = (step: LoginStep): step is PendingStep => step === 'mfa_verify' || step === 'mfa_setup'

export function hasPermission(user: SessionUser | null, permission: string): boolean {
  return user?.permissions.includes(permission) ?? false
}
