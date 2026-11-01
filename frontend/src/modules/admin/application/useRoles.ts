// Roles para su administración: el listado y la ficha de uno, con crear, editar y eliminar. Los formularios de
// personas usan el catálogo (useAccessCatalogStore), que se invalida después de cada cambio.
// Documentado en docs/frontend/admin.md
import { ref, shallowRef } from 'vue'
import { fieldMessageFor, fieldMessagesFor, messageFor } from '@/shared/errors'
import { toApiError, type ApiError } from '@/shared/http'
import { draftOf, EMPTY_ROLE_DRAFT, type Role, type RoleDraft } from '../domain/role'
import { rolesApi } from '../infrastructure/rolesApi'
import { useAccessCatalogStore } from './useAccessCatalogStore'
import { needsCode } from '@/shared/http'

export function useRoleList() {
  const roles = shallowRef<Role[]>([])
  const loading = ref(false)
  const error = ref<ApiError | null>(null)

  async function load(): Promise<void> {
    loading.value = true
    error.value = null
    try {
      roles.value = await rolesApi.list()
    } catch (raw) {
      error.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  return { roles, loading, error, load }
}

// Errores de la API que corresponden a un campo y no a todo el formulario
const FIELD_CODES: Record<string, keyof RoleDraft> = { ROLE_LEVEL_NOT_ALLOWED: 'level' }

/**
 * Formulario de un rol: nuevo (code null) o existente. Si la API pide el código de quien administra, el error sigue
 * hacia la pantalla para pedirlo; los demás quedan en el formulario.
 */
export function useRoleForm(code: string | null) {
  const catalog = useAccessCatalogStore()
  const role = shallowRef<Role | null>(null)
  const draft = ref<RoleDraft>({ ...EMPTY_ROLE_DRAFT, mfa: { ...EMPTY_ROLE_DRAFT.mfa } })
  const loading = ref(false)
  const loadError = ref<ApiError | null>(null)
  const fieldErrors = ref<Partial<Record<keyof RoleDraft, string>>>({})
  const formError = ref<string | null>(null)
  let attempt: { payload: string; key: string } | null = null

  async function load(): Promise<void> {
    if (!code) return
    loading.value = true
    loadError.value = null
    try {
      role.value = await rolesApi.get(code)
      draft.value = draftOf(role.value)
    } catch (raw) {
      loadError.value = toApiError(raw)
    } finally {
      loading.value = false
    }
  }

  // Revisión visual: lo que claramente falta; la API valida el resto (unicidad, rango, jerarquía)
  function validate(): boolean {
    const errors: Partial<Record<keyof RoleDraft, string>> = {}
    if (!code && !draft.value.code.trim()) errors.code = fieldMessageFor({ code: 'REQUIRED' })
    if (!draft.value.name.trim()) errors.name = fieldMessageFor({ code: 'REQUIRED' })
    if (!draft.value.level.trim()) errors.level = fieldMessageFor({ code: 'REQUIRED' })
    else if (!/^\d+$/.test(draft.value.level.trim())) errors.level = fieldMessageFor({ code: 'MUST_BE_INTEGER' })
    fieldErrors.value = errors
    return Object.keys(errors).length === 0
  }

  // El mismo contenido conserva la clave; si cambia algo, es otro intento
  function idempotencyKey(): string {
    const payload = JSON.stringify(draft.value)
    if (attempt?.payload !== payload) attempt = { payload, key: crypto.randomUUID() }
    return attempt.key
  }

  async function save(mfaCode?: string): Promise<Role | null> {
    formError.value = null
    try {
      const saved = code
        ? await rolesApi.update(code, draft.value, mfaCode)
        : await rolesApi.create(draft.value, idempotencyKey(), mfaCode)
      role.value = saved
      catalog.invalidate()
      return saved
    } catch (raw) {
      if (needsCode(raw) || mfaCode) throw raw
      showError(toApiError(raw))
      return null
    }
  }

  async function remove(): Promise<ApiError | null> {
    if (!code) return null
    try {
      await rolesApi.remove(code)
      catalog.invalidate()
      return null
    } catch (raw) {
      return toApiError(raw)
    }
  }

  function showError(failure: ApiError): void {
    const field = FIELD_CODES[failure.code]
    fieldErrors.value = field ? { [field]: messageFor(failure) } : fieldMessagesFor(failure)
    if (Object.keys(fieldErrors.value).length === 0) formError.value = messageFor(failure)
  }

  return { role, draft, loading, loadError, fieldErrors, formError, load, validate, save, remove, showError }
}
