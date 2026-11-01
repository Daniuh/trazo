// Crear una cuenta: borrador, revisión visual, errores de la API por campo y una clave de idempotencia por intento
// (si el mismo envío se repite, la API no crea otra). Documentado en docs/frontend/admin.md
import { reactive, ref } from 'vue'
import { fieldMessageFor, fieldMessagesFor, messageFor } from '@/shared/errors'
import { needsCode, toApiError } from '@/shared/http'
import type { UserDetail, UserDraft } from '../domain/user'
import { usersApi } from '../infrastructure/usersApi'
import { profileErrors } from './profileRules'

export function useUserCreate() {
  const draft = reactive<UserDraft>({ name: '', email: '', role: null })
  const fieldErrors = ref<Partial<Record<keyof UserDraft, string>>>({})
  const formError = ref<string | null>(null)
  let attempt: { payload: string; key: string } | null = null

  function validate(): boolean {
    const errors: Partial<Record<keyof UserDraft, string>> = profileErrors(draft)
    if (!draft.role) errors.role = fieldMessageFor({ code: 'REQUIRED' })
    fieldErrors.value = errors
    return Object.keys(errors).length === 0
  }

  // El mismo contenido conserva la clave; si cambia algo, es otro intento
  function idempotencyKey(): string {
    const payload = JSON.stringify(draft)
    if (attempt?.payload !== payload) attempt = { payload, key: crypto.randomUUID() }
    return attempt.key
  }

  /**
   * Crea la cuenta. Si la API pide el código de quien administra, el error sigue hacia la pantalla para pedirlo.
   * Los demás errores quedan en el formulario y devuelve null.
   */
  async function submit(code?: string): Promise<UserDetail | null> {
    formError.value = null
    try {
      return await usersApi.create(draft, idempotencyKey(), code)
    } catch (raw) {
      if (needsCode(raw) || code) throw raw
      const failure = toApiError(raw)
      fieldErrors.value = fieldMessagesFor(failure)
      if (Object.keys(fieldErrors.value).length === 0) formError.value = messageFor(failure)
      return null
    }
  }

  return { draft, fieldErrors, formError, validate, submit }
}
