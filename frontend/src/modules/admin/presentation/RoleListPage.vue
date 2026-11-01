<!-- Listado de roles, de mayor a menor nivel: nombre, nivel, personas, permisos y política de MFA. Portátil y monitor:
     tabla; tablet y celular: tarjetas. Elegir un rol abre su ficha. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import AppBadge from '@/shared/ui/AppBadge.vue'
import AppButton from '@/shared/ui/AppButton.vue'
import AppDataTable from '@/shared/ui/AppDataTable.vue'
import type { DataColumn } from '@/shared/ui/dataTable'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { useRoleList } from '../application/useRoles'
import { membersText, mfaPolicyText } from './userTexts'

const router = useRouter()
const session = useSessionStore()
const { isLaptopUp } = useViewport()
const { roles, loading, error, load } = useRoleList()

const COLUMNS: DataColumn[] = [
  { key: 'name', label: 'Rol' },
  { key: 'code', label: 'Código', emphasis: 'secondary' },
  { key: 'level', label: 'Nivel' },
  { key: 'members', label: 'Personas' },
  { key: 'permissions', label: 'Permisos' },
  { key: 'mfa', label: 'Dos pasos' },
]

const open = (code: string) => router.push({ name: 'role-detail', params: { code } })

onMounted(load)
</script>

<template>
  <div class="role-list">
    <header class="role-list__header">
      <h1 class="role-list__title">Roles</h1>
      <AppButton v-if="session.can('roles.manage')" icon="plus" @click="router.push({ name: 'role-create' })">
        Nuevo rol
      </AppButton>
    </header>

    <AppErrorState v-if="error" :error="error" @retry="load" />
    <AppSkeleton v-else-if="loading && roles.length === 0" :lines="6" />

    <AppDataTable
      v-else-if="isLaptopUp"
      :columns="COLUMNS"
      :rows="roles"
      :row-key="(role) => role.code"
      :row-label="(role) => role.name"
      @open="open"
    >
      <template #cell-name="{ row }">
        <!-- La etiqueta va al final de la celda, para que quede en la misma línea vertical en todas las filas -->
        <span class="role-list__name-cell">
          <span class="role-list__name">{{ row.name }}</span>
          <AppBadge v-if="row.isSystem" tone="neutral">Base</AppBadge>
        </span>
      </template>
      <template #cell-members="{ row }">{{ row.members }}</template>
      <template #cell-permissions="{ row }">{{ row.permissions.length }}</template>
      <template #cell-mfa="{ row }">{{ mfaPolicyText(row.mfa) }}</template>
    </AppDataTable>

    <ul v-else class="role-list__cards">
      <li v-for="role in roles" :key="role.code">
        <article
          class="role-list__card"
          tabindex="0"
          :aria-label="role.name"
          @click="open(role.code)"
          @keydown.enter="open(role.code)"
        >
          <div class="role-list__card-head">
            <h2 class="role-list__card-name">{{ role.name }}</h2>
            <AppBadge v-if="role.isSystem" tone="neutral">Base</AppBadge>
          </div>
          <p class="role-list__meta">
            Nivel {{ role.level }} · {{ membersText(role.members) }} · {{ role.permissions.length }} permisos
          </p>
          <p class="role-list__meta">Verificación en dos pasos: {{ mfaPolicyText(role.mfa) }}</p>
        </article>
      </li>
    </ul>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.role-list {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.role-list__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
}

.role-list__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

.role-list__name-cell {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: $space-2;
}

.role-list__name {
  overflow: hidden;
  font-weight: $font-weight-medium;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.role-list__cards {
  display: flex;
  flex-direction: column;
  gap: $space-3;
  margin: 0;
  padding: 0;
  list-style: none;
}

.role-list__card {
  display: flex;
  flex-direction: column;
  gap: $space-2;
  padding: $space-4;
  border: 1px solid $color-border;
  border-radius: $radius-card;
  background: $color-surface;
  cursor: pointer;

  &:hover {
    border-color: $color-border-strong;
  }
}

.role-list__card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: $space-2;
}

.role-list__card-name {
  margin: 0;
  font-size: $font-size-md;
  font-weight: $font-weight-semibold;
}

.role-list__meta {
  margin: 0;
  color: $color-text-secondary;
  font-size: $font-size-xs;
}

@include laptop {
  .role-list__title {
    font-size: $font-size-2xl;
  }
}
</style>
