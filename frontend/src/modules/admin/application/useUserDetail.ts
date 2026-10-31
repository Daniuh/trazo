// Ficha de una persona y los cambios que se le pueden hacer. Cada cambio responde con la ficha actualizada, que
// reemplaza a la anterior. Los que pueden pedir el código de quien administra reciben un "code" opcional.
// Documentado en docs/frontend/admin.md
import { ref, shallowRef } from 'vue'
import { toApiError, type ApiError } from '@/shared/http'
import type { ProfileDraft, UserDetail } from '../domain/user'
import { usersApi } from '../infrastructure/usersApi'

export function useUserDetail(id: string) {
  const user = shallowRef<UserDetail | null>(null)
  const loading = ref(false)
  const error = ref<ApiError | null>(null)

  async function load(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      user.value = await usersApi.get(id)
    } catch (raw) {
      error.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  // Los errores siguen hacia la pantalla, que decide si pide el código o muestra el mensaje
  async function apply(change: Promise<UserDetail>): Promise<UserDetail> {
    user.value = await change
    return user.value
  }

  return {
    user,
    loading,
    error,
    load,
    updateProfile: (draft: ProfileDraft) => apply(usersApi.updateProfile(id, draft)),
    changeRole: (role: string, code?: string) => apply(usersApi.changeRole(id, role, code)),
    changePermissions: (permissions: string[], code?: string) =>
      apply(usersApi.changePermissions(id, permissions, code)),
    setActive: (active: boolean) => apply(usersApi.setActive(id, active)),
    resetMfa: (code?: string) => apply(usersApi.resetMfa(id, code)),
    resetAccess: (code?: string) => apply(usersApi.resetAccess(id, code)),
  }
}

// La API pide el código de quien administra para este cambio
export function needsCode(raw: unknown): boolean {
  return toApiError(raw).code === 'REAUTHENTICATION_REQUIRED'
}
