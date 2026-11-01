<!-- Filtros del listado de usuarios: rol (varios), estado y verificación en dos pasos. En portátil y monitor van en la
     barra como selectores; en celular y tablet, dentro del panel de filtros. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed } from 'vue'
import AppSelect from '@/shared/ui/form/AppSelect.vue'
import type { MfaFilter, UserListCriteria, UserStatusFilter } from '../../domain/listing'
import type { RoleOption } from '../../domain/user'
import { MFA_CHOICES, STATUS_CHOICES } from '../userTexts'

const props = defineProps<{ criteria: UserListCriteria; roles: RoleOption[] }>()
const emit = defineEmits<{ change: [changes: Partial<UserListCriteria>] }>()

// Los filtros de una sola opción se quitan eligiendo "Todas"
const ALL = 'all'
const ALL_OPTION = { label: 'Todas', value: ALL }

const roleOptions = computed(() => props.roles.map((role) => ({ label: role.name, value: role.code })))

const roles = computed({
  get: () => props.criteria.roles,
  set: (value: string | string[] | null) => emit('change', { roles: Array.isArray(value) ? value : [] }),
})
const status = computed({
  get: () => props.criteria.status ?? ALL,
  set: (value: string | string[] | null) =>
    emit('change', { status: value === ALL ? null : ((value as UserStatusFilter | null) ?? null) }),
})
const mfa = computed({
  get: () => props.criteria.mfa ?? ALL,
  set: (value: string | string[] | null) =>
    emit('change', { mfa: value === ALL ? null : ((value as MfaFilter | null) ?? null) }),
})
</script>

<template>
  <div class="user-filter-fields">
    <AppSelect v-model="roles" label="Rol" :options="roleOptions" multiple placeholder="Todos" />
    <AppSelect v-model="status" label="Estado" :options="[ALL_OPTION, ...STATUS_CHOICES]" />
    <AppSelect v-model="mfa" label="Verificación en dos pasos" :options="[ALL_OPTION, ...MFA_CHOICES]" />
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.user-filter-fields {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

@include laptop {
  .user-filter-fields {
    flex-direction: row;
    gap: $space-3;

    > * {
      width: $filter-field-width;
    }
  }
}
</style>
