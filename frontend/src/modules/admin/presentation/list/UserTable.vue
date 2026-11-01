<!-- Tabla de usuarios (portátil y monitor), sobre AppDataTable: nombre, correo, rol, estado, verificación en dos pasos
     y último ingreso. Se ordena tocando el encabezado; elegir una fila abre su ficha. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppDataTable from '@/shared/ui/AppDataTable.vue'
import AppIcon from '@/shared/ui/AppIcon.vue'
import type { DataColumn } from '@/shared/ui/dataTable'
import type { UserListCriteria, UserSortField } from '../../domain/listing'
import type { UserSummary } from '../../domain/user'
import { lastLoginText, mfaLabel, statusLabel, statusTone } from '../userTexts'

defineProps<{ items: UserSummary[]; criteria: UserListCriteria }>()
const emit = defineEmits<{ open: [id: string]; sort: [field: UserSortField, descending: boolean] }>()

const COLUMNS: DataColumn[] = [
  { key: 'name', label: 'Nombre', sortable: true },
  { key: 'email', label: 'Correo', sortable: true, emphasis: 'grow' },
  { key: 'role', label: 'Rol' },
  { key: 'status', label: 'Estado' },
  { key: 'mfa', label: 'Dos pasos' },
  { key: 'last_login_at', label: 'Último ingreso', sortable: true },
]
</script>

<template>
  <AppDataTable
    :columns="COLUMNS"
    :rows="items"
    :row-key="(user) => user.id"
    :row-label="(user) => user.name"
    :sort="criteria.sort"
    :descending="criteria.descending"
    @open="emit('open', $event)"
    @sort="(field, descending) => emit('sort', field as UserSortField, descending)"
  >
    <template #cell-name="{ row }">
      <span class="user-table__name">{{ row.name }}</span>
    </template>
    <template #cell-role="{ row }">{{ row.role?.name ?? 'Sin rol' }}</template>
    <template #cell-status="{ row }">
      <AppBadge :tone="statusTone(row)">{{ statusLabel(row) }}</AppBadge>
    </template>
    <template #cell-mfa="{ row }">
      <AppIcon
        :name="row.mfaEnabled ? 'check' : 'close'"
        :label="mfaLabel(row)"
        :class="row.mfaEnabled ? 'user-table__mfa-on' : 'user-table__muted'"
      />
    </template>
    <template #cell-last_login_at="{ row }">
      <span :class="{ 'user-table__muted': !row.lastLoginAt }">{{ lastLoginText(row) }}</span>
    </template>
  </AppDataTable>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.user-table__name {
  font-weight: $font-weight-medium;
}

.user-table__muted {
  color: $color-text-secondary;
}

.user-table__mfa-on {
  color: $color-success;
}
</style>
