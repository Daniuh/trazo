// Repositorio de sesión: habla con /auth de la API y traduce sus respuestas al modelo del front.
// Documentado en docs/frontend/auth.md
import { http } from '@/shared/http'
import type { SessionUser } from '../domain/session'

interface SessionUserDto {
  id: string
  name: string
  email: string
  roles: { code: string; name: string }[]
  permissions: string[]
}

interface LoginDto {
  token: string
  user: SessionUserDto
}

export interface StartedSession {
  token: string
  user: SessionUser
}

const toSessionUser = (dto: SessionUserDto): SessionUser => ({
  id: dto.id,
  name: dto.name,
  email: dto.email,
  roles: dto.roles.map((role) => ({ code: role.code, name: role.name })),
  permissions: [...dto.permissions],
})

export const authApi = {
  async login(email: string, password: string): Promise<StartedSession> {
    const { data } = await http.post<{ data: LoginDto }>('/auth/login', { email, password })
    return { token: data.data.token, user: toSessionUser(data.data.user) }
  },

  async logout(): Promise<void> {
    await http.post('/auth/logout')
  },

  async me(): Promise<SessionUser> {
    const { data } = await http.get<{ data: SessionUserDto }>('/auth/me')
    return toSessionUser(data.data)
  },
}
