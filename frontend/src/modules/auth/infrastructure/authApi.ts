// Repositorio de sesión: habla con /auth de la API (ingreso, MFA y contraseña) y traduce sus respuestas al modelo del front.
// Los pedidos del segundo paso del ingreso llevan su propio token, que todavía no es el de la sesión.
// Documentado en docs/frontend/auth.md
import { http } from '@/shared/http'
import type { LoginStep, SessionUser } from '../domain/session'

interface SessionUserDto {
  id: string
  name: string
  email: string
  roles: { code: string; name: string }[]
  permissions: string[]
  mfa: { enabled: boolean; required: boolean; recovery_codes_left: number }
}

interface SessionDto {
  status: LoginStep
  token: string
  expires_at: string
  user: SessionUserDto | null
}

export interface LoginResult {
  step: LoginStep
  token: string
  // Solo cuando el paso abre la sesión
  user: SessionUser | null
}

export interface MfaSetupData {
  secret: string
  uri: string
}

export interface ConfirmedMfa {
  recoveryCodes: string[]
  // La sesión completa, si se configuró durante el ingreso
  session: LoginResult | null
}

// Pase de un solo uso que entrega el código del correo, y si la cuenta pide además el segundo factor
export interface ResetTicket {
  ticket: string
  mfaRequired: boolean
}

export interface PasswordResetInput {
  email: string
  ticket: string
  password: string
  confirmation: string
  mfaCode?: string
  recoveryCode?: string
}

const toSessionUser = (dto: SessionUserDto): SessionUser => ({
  id: dto.id,
  name: dto.name,
  email: dto.email,
  roles: dto.roles.map((role) => ({ code: role.code, name: role.name })),
  permissions: [...dto.permissions],
  mfa: { enabled: dto.mfa.enabled, required: dto.mfa.required, recoveryCodesLeft: dto.mfa.recovery_codes_left },
})

const toLogin = (dto: SessionDto): LoginResult => ({
  step: dto.status,
  token: dto.token,
  user: dto.user ? toSessionUser(dto.user) : null,
})

// Sin token propio, el cliente HTTP usa el de la sesión
const withToken = (token?: string) => (token ? { headers: { Authorization: `Bearer ${token}` } } : {})

export const authApi = {
  async login(email: string, password: string): Promise<LoginResult> {
    const { data } = await http.post<{ data: SessionDto }>('/auth/login', { email, password })
    return toLogin(data.data)
  },

  async logout(): Promise<void> {
    await http.post('/auth/logout')
  },

  async me(): Promise<SessionUser> {
    const { data } = await http.get<{ data: SessionUserDto }>('/auth/me')
    return toSessionUser(data.data)
  },

  async verifyMfa(token: string, answer: { code: string } | { recoveryCode: string }): Promise<LoginResult> {
    const body = 'code' in answer ? { code: answer.code } : { recovery_code: answer.recoveryCode }
    const { data } = await http.post<{ data: SessionDto }>('/auth/mfa/verify', body, withToken(token))
    return toLogin(data.data)
  },

  async startMfaSetup(token?: string): Promise<MfaSetupData> {
    const { data } = await http.post<{ data: MfaSetupData }>('/auth/mfa/setup', {}, withToken(token))
    return data.data
  },

  async confirmMfaSetup(code: string, token?: string): Promise<ConfirmedMfa> {
    const { data } = await http.post<{ data: { recovery_codes: string[]; session: SessionDto | null } }>(
      '/auth/mfa/setup/confirm',
      { code },
      withToken(token),
    )
    return { recoveryCodes: data.data.recovery_codes, session: data.data.session ? toLogin(data.data.session) : null }
  },

  async regenerateRecoveryCodes(code: string): Promise<string[]> {
    const { data } = await http.post<{ data: { recovery_codes: string[] } }>('/auth/mfa/recovery-codes', { code })
    return data.data.recovery_codes
  },

  async disableMfa(code: string): Promise<void> {
    await http.delete('/auth/mfa', { data: { code } })
  },

  async forgotPassword(email: string): Promise<void> {
    await http.post('/auth/password/forgot', { email })
  },

  async verifyResetCode(email: string, code: string): Promise<ResetTicket> {
    const { data } = await http.post<{ data: { ticket: string; mfa_required: boolean } }>(
      '/auth/password/verify-code',
      { email, code },
    )
    return { ticket: data.data.ticket, mfaRequired: data.data.mfa_required }
  },

  async resetPassword(reset: PasswordResetInput): Promise<void> {
    await http.post('/auth/password/reset', {
      email: reset.email,
      ticket: reset.ticket,
      password: reset.password,
      password_confirmation: reset.confirmation,
      mfa_code: reset.mfaCode || undefined,
      recovery_code: reset.recoveryCode || undefined,
    })
  },

  async changePassword(current: string, password: string, confirmation: string): Promise<void> {
    await http.put('/auth/password', {
      current_password: current,
      password,
      password_confirmation: confirmation,
    })
  },
}
