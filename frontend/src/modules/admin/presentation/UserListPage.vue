<!-- Listado de usuarios. Portátil y monitor: tabla. Tablet y celular: tarjetas. Los filtros viven en la URL y elegir
     una persona abre su ficha. "Nueva persona" solo aparece con el permiso de crear. Documentado en docs/frontend/admin.md -->
<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useSessionStore } from '@/modules/auth/application/useSessionStore'
import AppButton from '@/shared/ui/AppButton.vue'
import AppPagination from '@/shared/ui/AppPagination.vue'
import AppEmptyState from '@/shared/ui/states/AppEmptyState.vue'
import AppErrorState from '@/shared/ui/states/AppErrorState.vue'
import AppSkeleton from '@/shared/ui/states/AppSkeleton.vue'
import { useViewport } from '@/shared/ui/useViewport'
import { useAccessCatalogStore } from '../application/useAccessCatalogStore'
import { useUserList } from '../application/useUserList'
import {
  activeUserFilterCount,
  DEFAULT_USER_CRITERIA,
  USER_PER_PAGE_OPTIONS,
  type UserSortField,
} from '../domain/listing'
import UserCards from './list/UserCards.vue'
import UserTable from './list/UserTable.vue'
import UserToolbar from './list/UserToolbar.vue'

const router = useRouter()
const session = useSessionStore()
const catalog = useAccessCatalogStore()
const { isLaptopUp } = useViewport()

const list = useUserList()
const { criteria, items, total, lastPage, loading, error } = list

const filtered = computed(() => activeUserFilterCount(criteria.value) > 0 || criteria.value.search !== '')

onMounted(() => {
  if (session.user) void catalog.load(session.user.id)
})

function open(id: string): void {
  void router.push({ name: 'user-detail', params: { id } })
}

function sort(field: UserSortField, descending: boolean): void {
  list.update({ sort: field, descending })
}

function clearFilters(): void {
  list.update({ ...DEFAULT_USER_CRITERIA, sort: criteria.value.sort, descending: criteria.value.descending })
}
</script>

<template>
  <div class="user-list">
    <header class="user-list__header">
      <h1 class="user-list__title">Usuarios</h1>
      <AppButton v-if="session.can('users.create')" icon="plus" @click="router.push({ name: 'user-create' })">
        Nueva persona
      </AppButton>
    </header>

    <UserToolbar :criteria="criteria" :roles="catalog.roles" @change="list.update" />

    <section class="user-list__results" :aria-busy="loading" aria-label="Resultados">
      <AppErrorState v-if="error" :error="error" @retry="list.load" />
      <AppSkeleton v-else-if="loading && items.length === 0" :lines="8" />
      <AppEmptyState
        v-else-if="items.length === 0 && filtered"
        title="No hay personas con estos filtros"
        description="Prueba con otros filtros o con otra búsqueda."
      >
        <AppButton variant="secondary" @click="clearFilters">Limpiar filtros</AppButton>
      </AppEmptyState>
      <AppEmptyState v-else-if="items.length === 0" title="Todavía no hay personas" />

      <template v-else>
        <UserTable v-if="isLaptopUp" :items="items" :criteria="criteria" @open="open" @sort="sort" />
        <UserCards v-else :items="items" @open="open" />

        <AppPagination
          :page="criteria.page"
          :last-page="lastPage"
          :total="total"
          :per-page="criteria.perPage"
          :per-page-options="USER_PER_PAGE_OPTIONS"
          @update:page="list.update({ page: $event })"
          @update:per-page="list.update({ perPage: $event })"
        />
      </template>
    </section>

    <!-- Aviso para lectores de pantalla mientras llega la página nueva -->
    <p v-if="error === null && loading && items.length > 0" class="user-list__hidden" role="status">
      Actualizando resultados…
    </p>
  </div>
</template>

<style scoped lang="scss">
@use '@/design/tokens' as *;

.user-list,
.user-list__results {
  display: flex;
  flex-direction: column;
  gap: $space-4;
}

.user-list__header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: $space-3;
}

.user-list__title {
  margin: 0;
  font-size: $font-size-xl;
  font-weight: $font-weight-semibold;
  line-height: $line-height-tight;
}

// Mientras llega la página nueva, la actual se atenúa en lugar de desaparecer
.user-list__results[aria-busy='true'] {
  opacity: 0.6;
  transition: opacity $duration-base;
}

.user-list__hidden {
  @include visually-hidden;
}

@include laptop {
  .user-list__title {
    font-size: $font-size-2xl;
  }
}
</style>
