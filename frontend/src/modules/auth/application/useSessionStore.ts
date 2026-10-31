// Estado de la sesión: quién está conectado y qué puede ver. El token vive en tokenStore (memoria y sessionStorage).
// Documentado en docs/frontend/auth.md
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { tokenStore } from '@/shared/http'
import { hasPermission, type SessionUser } from '../domain/session'
import { authApi } from '../infrastructure/authApi'

export const useSessionStore = defineStore('session', () => {
  const user = ref<SessionUser | null>(null)
  const isAuthenticated = computed(() => user.value !== null)

  function clear(): void {
    tokenStore.clear()
    user.value = null
  }

  async function login(email: string, password: string): Promise<void> {
    const session = await authApi.login(email, password)
    tokenStore.set(session.token)
    user.value = session.user
  }

  // Al recargar la página queda el token pero no el usuario: se pide de nuevo a la API
  async function restore(): Promise<boolean> {
    if (user.value) return true
    if (!tokenStore.get()) return false

    try {
      user.value = await authApi.me()
      return true
    } catch {
      clear()
      return false
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

  return { user, isAuthenticated, login, restore, logout, clear, can }
})
