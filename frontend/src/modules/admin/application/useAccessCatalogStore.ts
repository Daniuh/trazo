// Roles y permisos para los formularios de la administración (Pinia). Se cargan una vez por persona con sesión:
// qué roles y permisos puede dar depende de quién administra, y la API vuelve a validar todo al guardar.
// Documentado en docs/frontend/admin.md
import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { toApiError, type ApiError } from '@/shared/http'
import type { PermissionOption, RoleOption } from '../domain/user'
import { usersApi } from '../infrastructure/usersApi'

export const useAccessCatalogStore = defineStore('access-catalog', () => {
  const roles = ref<RoleOption[]>([])
  const permissions = ref<PermissionOption[]>([])
  const error = ref<ApiError | null>(null)
  const owner = ref<string | null>(null)
  let pending: Promise<void> | null = null

  // Si cambió la persona con sesión, se vuelve a pedir
  async function load(ownerId: string): Promise<void> {
    if (owner.value === ownerId && roles.value.length > 0) return
    pending ??= (async () => {
      try {
        error.value = null
        const [nextRoles, nextPermissions] = await Promise.all([usersApi.roles(), usersApi.permissions()])
        roles.value = nextRoles
        permissions.value = nextPermissions
        owner.value = ownerId
      } catch (raw) {
        error.value = toApiError(raw)
      } finally {
        pending = null
      }
    })()
    await pending
  }

  const role = (code: string | null | undefined) => roles.value.find((item) => item.code === code) ?? null
  const assignableRoles = computed(() => roles.value.filter((item) => item.assignable))

  return { roles, permissions, error, load, role, assignableRoles }
})
