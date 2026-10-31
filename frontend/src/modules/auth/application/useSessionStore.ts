// Estado de la sesión: quién está conectado y qué puede ver. El token vive en tokenStore (memoria y sessionStorage).
// Documentado en docs/frontend/auth.md
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { ApiError, toApiError, tokenStore } from '@/shared/http'
import { hasPermission, type SessionUser } from '../domain/session'
import { authApi } from '../infrastructure/authApi'

// Resultado de recuperar la sesión al recargar: con sesión, sin sesión o sin poder comprobarlo
export type RestoreResult = 'signed-in' | 'signed-out' | 'unreachable'

const UNAUTHENTICATED = 'UNAUTHENTICATED'

export const useSessionStore = defineStore('session', () => {
  const user = ref<SessionUser | null>(null)
  const isAuthenticated = computed(() => user.value !== null)
  // Por qué no se pudo comprobar la sesión (sin conexión, mantenimiento, lentitud)
  const connectionError = ref<ApiError | null>(null)

  function clear(): void {
    tokenStore.clear()
    user.value = null
  }

  async function login(email: string, password: string): Promise<void> {
    const session = await authApi.login(email, password)
    tokenStore.set(session.token)
    user.value = session.user
  }

  // Al recargar la página queda el token pero no el usuario: se pide de nuevo a la API.
  // Solo la API puede decir que la sesión ya no vale; una falla de red o un mantenimiento no la borran.
  async function restore(): Promise<RestoreResult> {
    if (user.value) return 'signed-in'
    if (!tokenStore.get()) return 'signed-out'

    try {
      user.value = await authApi.me()
      connectionError.value = null
      return 'signed-in'
    } catch (raw) {
      const failure = toApiError(raw)
      if (failure.code === UNAUTHENTICATED) {
        clear()
        return 'signed-out'
      }
      connectionError.value = failure
      return 'unreachable'
    }
  }

  async function logout(): Promise<void> {
    try {
      await authApi.logout()
    } catch {
      // Si la API no responde, el token igual se descarta en el navegador y vence solo
    } finally {
      clear()
    }
  }

  const can = (permission: string) => hasPermission(user.value, permission)

  return { user, isAuthenticated, connectionError, login, restore, logout, clear, can }
})
