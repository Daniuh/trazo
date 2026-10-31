// Estado de la sesión: quién está conectado y qué puede ver. El token vive en tokenStore (memoria y sessionStorage).
// Un ingreso a medias (falta el MFA) guarda su token solo en memoria: si se recarga la página, se vuelve a ingresar.
// Documentado en docs/frontend/auth.md
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { ApiError, toApiError, tokenStore } from '@/shared/http'
import { hasPermission, isPendingStep, type LoginStep, type PendingStep, type SessionUser } from '../domain/session'
import { authApi, type LoginResult, type MfaSetupData } from '../infrastructure/authApi'

// Resultado de recuperar la sesión al recargar: con sesión, sin sesión o sin poder comprobarlo
export type RestoreResult = 'signed-in' | 'signed-out' | 'unreachable'

const UNAUTHENTICATED = 'UNAUTHENTICATED'

export const useSessionStore = defineStore('session', () => {
  const user = ref<SessionUser | null>(null)
  const isAuthenticated = computed(() => user.value !== null)
  // Por qué no se pudo comprobar la sesión (sin conexión, mantenimiento, lentitud)
  const connectionError = ref<ApiError | null>(null)
  // Ingreso a medias: contraseña correcta, falta verificar o configurar el MFA
  const pending = ref<{ token: string; step: PendingStep } | null>(null)

  function clear(): void {
    tokenStore.clear()
    user.value = null
    pending.value = null
  }

  function open(result: LoginResult): void {
    if (isPendingStep(result.step) || !result.user) {
      pending.value = { token: result.token, step: result.step as PendingStep }
      return
    }
    tokenStore.set(result.token)
    user.value = result.user
    pending.value = null
  }

  async function login(email: string, password: string): Promise<LoginStep> {
    const result = await authApi.login(email, password)
    open(result)
    return result.step
  }

  async function verifyMfa(answer: { code: string } | { recoveryCode: string }): Promise<void> {
    if (!pending.value) throw new ApiError('http', UNAUTHENTICATED)
    open(await authApi.verifyMfa(pending.value.token, answer))
  }

  // Durante el ingreso usa el token del ingreso a medias; con la sesión abierta, el de la sesión
  async function startMfaSetup(): Promise<MfaSetupData> {
    return authApi.startMfaSetup(pending.value?.step === 'mfa_setup' ? pending.value.token : undefined)
  }

  async function confirmMfaSetup(code: string): Promise<string[]> {
    const challenge = pending.value?.step === 'mfa_setup' ? pending.value.token : undefined
    const result = await authApi.confirmMfaSetup(code, challenge)
    if (result.session) open(result.session)
    else await refresh()
    return result.recoveryCodes
  }

  async function regenerateRecoveryCodes(code: string): Promise<string[]> {
    const codes = await authApi.regenerateRecoveryCodes(code)
    await refresh()
    return codes
  }

  async function disableMfa(code: string): Promise<void> {
    await authApi.disableMfa(code)
    await refresh()
  }

  async function refresh(): Promise<void> {
    user.value = await authApi.me()
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

  return {
    user,
    pending,
    isAuthenticated,
    connectionError,
    login,
    verifyMfa,
    startMfaSetup,
    confirmMfaSetup,
    regenerateRecoveryCodes,
    disableMfa,
    refresh,
    restore,
    logout,
    clear,
    can,
  }
})
